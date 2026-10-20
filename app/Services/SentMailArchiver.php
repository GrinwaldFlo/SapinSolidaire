<?php

namespace App\Services;

use App\Services\Imap\ImapClient;
use App\Services\Imap\ImapConnector;
use App\Services\Imap\ImapException;
use Closure;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stores a copy of sent emails in the IMAP mailbox (subfolder of the Sent folder).
 * Steps are logged to the dedicated "imap" channel (storage/logs/imap.log).
 * Failures are logged and never thrown: the email itself has already been sent,
 * so an exception would only make the queue retry and send it twice.
 */
class SentMailArchiver
{
    public function __construct(private ImapConnector $connector) {}

    public function isEnabled(): bool
    {
        return (bool) config('mail.imap_copy.enabled');
    }

    /**
     * @param  string  $description  Short identification of the email for the logs (recipient, subject).
     */
    public function archive(string $rawMessage, string $description = ''): void
    {
        if (! $this->isEnabled()) {
            if ($this->isDebug()) {
                $this->log()->debug("IMAP copy disabled (MAIL_IMAP_COPY_ENABLED=false), skipping {$description}");
            }

            return;
        }

        $config = config('mail.imap_copy');
        $client = null;

        $this->log()->info("Storing copy of sent email {$description}", [
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'username' => $config['username'],
            'password_set' => filled($config['password']),
        ]);

        try {
            $client = $this->connector->connect($this->tracer());
            $this->log()->info('IMAP login successful');

            $folder = $this->resolveFolder($client);
            $client->append($folder, $rawMessage);

            $this->log()->info("Email copy stored in IMAP folder \"{$folder}\"");
        } catch (Throwable $e) {
            $context = ['error' => $e->getMessage(), 'exception' => $e::class];

            $this->log()->error('Unable to store sent email copy on IMAP server', $context);
            Log::error('Unable to store sent email copy on IMAP server (see storage/logs/imap.log)', $context);
        } finally {
            $client?->logout();
        }
    }

    /**
     * Build the target folder path and create it when it does not exist yet.
     */
    private function resolveFolder(ImapClient $client): string
    {
        $sentFolder = $this->resolveSentFolder($client);
        $subfolder = trim((string) config('mail.imap_copy.subfolder'));

        if ($subfolder === '') {
            return $sentFolder;
        }

        $delimiter = $client->delimiter();
        $folder = $sentFolder.$delimiter.$subfolder;
        $this->log()->info("IMAP hierarchy delimiter is \"{$delimiter}\", target folder \"{$folder}\"");

        if ($client->folderExists($folder)) {
            return $folder;
        }

        $this->log()->info("IMAP folder \"{$folder}\" not found, creating it");
        $client->createFolder($folder);

        return $folder;
    }

    /**
     * Use the configured Sent folder, or fall back to the folder the server flags as "\Sent"
     * (servers often prefix folders, e.g. "INBOX.Sent").
     */
    private function resolveSentFolder(ImapClient $client): string
    {
        $configured = (string) config('mail.imap_copy.sent_folder');

        if ($client->folderExists($configured)) {
            return $configured;
        }

        $detected = $client->specialUseFolder('\\Sent');

        if ($detected === null) {
            throw new ImapException("Sent folder \"{$configured}\" not found and the server flags no folder as \\Sent; set MAIL_IMAP_SENT_FOLDER");
        }

        $this->log()->warning("Sent folder \"{$configured}\" not found, using \"{$detected}\" flagged as \\Sent by the server (set MAIL_IMAP_SENT_FOLDER={$detected})");

        return $detected;
    }

    private function isDebug(): bool
    {
        return (bool) config('mail.imap_copy.debug');
    }

    private function tracer(): ?Closure
    {
        if (! $this->isDebug()) {
            return null;
        }

        return fn (string $line) => $this->log()->debug($line);
    }

    private function log(): LoggerInterface
    {
        return Log::channel('imap');
    }
}
