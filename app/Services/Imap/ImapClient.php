<?php

namespace App\Services\Imap;

use Closure;

/**
 * Minimal IMAP4rev1 client covering what is needed to store copies of sent emails:
 * login, folder lookup/creation and APPEND. Works over any stream resource.
 */
class ImapClient
{
    private int $tagCounter = 0;

    /**
     * @param  resource  $stream  Connected stream; the server greeting is read on construction.
     * @param  (Closure(string): void)|null  $tracer  Receives every line exchanged (password masked).
     */
    public function __construct(private $stream, private ?Closure $tracer = null)
    {
        $greeting = $this->readLine();

        if (! str_starts_with($greeting, '* OK') && ! str_starts_with($greeting, '* PREAUTH')) {
            throw new ImapException("Unexpected IMAP greeting: {$greeting}");
        }
    }

    /**
     * Open a connection. Encryption: "ssl" (implicit TLS), "starttls" or "none".
     */
    public static function connect(string $host, int $port, string $encryption = 'ssl', int $timeout = 15, ?Closure $tracer = null): self
    {
        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $stream = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $error, $timeout);

        if ($stream === false) {
            throw new ImapException("Unable to connect to IMAP server {$host}:{$port} ({$error})");
        }

        stream_set_timeout($stream, $timeout);

        $client = new self($stream, $tracer);

        if ($encryption === 'starttls') {
            $client->startTls();
        }

        return $client;
    }

    public function startTls(): void
    {
        $this->command('STARTTLS');

        if (! stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new ImapException('Unable to enable TLS on IMAP connection');
        }
    }

    public function login(string $username, string $password): void
    {
        $this->command(
            'LOGIN '.$this->quote($username).' '.$this->quote($password),
            'LOGIN '.$this->quote($username).' "***"'
        );
    }

    /**
     * Hierarchy delimiter used by the server (e.g. "/" or ".").
     */
    public function delimiter(): string
    {
        foreach ($this->command('LIST "" ""') as $line) {
            $entry = $this->parseListLine($line);

            if ($entry !== null && $entry['delimiter'] !== null) {
                return $entry['delimiter'];
            }
        }

        return '/';
    }

    public function folderExists(string $folder): bool
    {
        foreach ($this->command('LIST "" '.$this->quoteFolder($folder)) as $line) {
            if ($this->parseListLine($line) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the folder flagged with a special-use attribute (RFC 6154), e.g. "\Sent".
     */
    public function specialUseFolder(string $attribute): ?string
    {
        foreach ($this->command('LIST "" "*"') as $line) {
            $entry = $this->parseListLine($line);

            if ($entry !== null && in_array(strtolower($attribute), array_map('strtolower', $entry['attributes']), true)) {
                return $entry['name'];
            }
        }

        return null;
    }

    public function createFolder(string $folder): void
    {
        $this->command('CREATE '.$this->quoteFolder($folder));
        $this->command('SUBSCRIBE '.$this->quoteFolder($folder));
    }

    /**
     * Append a raw RFC 822 message to a folder.
     *
     * @param  array<int, string>  $flags
     */
    public function append(string $folder, string $message, array $flags = ['\\Seen']): void
    {
        $message = preg_replace("/\r?\n/", "\r\n", $message);
        $tag = $this->nextTag();

        $this->write(sprintf(
            "%s APPEND %s (%s) {%d}\r\n",
            $tag,
            $this->quoteFolder($folder),
            implode(' ', $flags),
            strlen($message)
        ));

        $continuation = $this->readLine();

        if (! str_starts_with($continuation, '+')) {
            throw new ImapException("IMAP server refused APPEND: {$continuation}");
        }

        $this->write($message."\r\n", '['.strlen($message).' bytes of message data]');
        $this->readResponse($tag);
    }

    public function logout(): void
    {
        try {
            $this->command('LOGOUT');
        } catch (ImapException) {
            // Connection is being closed anyway.
        } finally {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
        }
    }

    /**
     * Send a tagged command and return its untagged response lines.
     *
     * @return array<int, string>
     */
    private function command(string $command, ?string $traceAs = null): array
    {
        $tag = $this->nextTag();
        $this->write("{$tag} {$command}\r\n", $traceAs !== null ? "{$tag} {$traceAs}" : null);

        return $this->readResponse($tag);
    }

    /**
     * @return array<int, string>
     */
    private function readResponse(string $tag): array
    {
        $untagged = [];

        while (true) {
            $line = $this->readLine();

            if (! str_starts_with($line, $tag.' ')) {
                $untagged[] = $line;

                continue;
            }

            $status = substr($line, strlen($tag) + 1);

            if (! str_starts_with($status, 'OK')) {
                throw new ImapException("IMAP command failed: {$status}");
            }

            return $untagged;
        }
    }

    /**
     * @return array{attributes: array<int, string>, delimiter: ?string, name: string}|null
     */
    private function parseListLine(string $line): ?array
    {
        if (! preg_match('/^\* LIST \(([^)]*)\) (NIL|"((?:[^"\\\\]|\\\\.)*)") (.*)$/i', $line, $matches)) {
            return null;
        }

        $name = $matches[4];

        if (preg_match('/^"(.*)"$/', $name, $quoted)) {
            $name = stripslashes($quoted[1]);
        }

        return [
            'attributes' => preg_split('/\s+/', trim($matches[1]), -1, PREG_SPLIT_NO_EMPTY),
            'delimiter' => strtoupper($matches[2]) === 'NIL' ? null : stripslashes($matches[3]),
            'name' => mb_convert_encoding($name, 'UTF-8', 'UTF7-IMAP'),
        ];
    }

    private function quoteFolder(string $folder): string
    {
        return $this->quote(mb_convert_encoding($folder, 'UTF7-IMAP', 'UTF-8'));
    }

    private function quote(string $value): string
    {
        if (preg_match("/[\r\n]/", $value)) {
            throw new ImapException('IMAP quoted strings cannot contain line breaks');
        }

        return '"'.addcslashes($value, '"\\').'"';
    }

    private function nextTag(): string
    {
        return sprintf('A%04d', ++$this->tagCounter);
    }

    /**
     * @param  string|null  $traceAs  Replacement text for the trace (hides secrets or large payloads).
     */
    private function write(string $data, ?string $traceAs = null): void
    {
        $this->trace('C: '.($traceAs ?? rtrim($data, "\r\n")));

        if (@fwrite($this->stream, $data) !== strlen($data)) {
            throw new ImapException('Unable to write to IMAP connection');
        }
    }

    private function readLine(): string
    {
        $line = fgets($this->stream);

        if ($line === false) {
            $meta = stream_get_meta_data($this->stream);

            throw new ImapException($meta['timed_out'] ? 'IMAP connection timed out' : 'IMAP connection closed');
        }

        $line = rtrim($line, "\r\n");
        $this->trace('S: '.$line);

        return $line;
    }

    private function trace(string $line): void
    {
        if ($this->tracer !== null) {
            ($this->tracer)($line);
        }
    }
}
