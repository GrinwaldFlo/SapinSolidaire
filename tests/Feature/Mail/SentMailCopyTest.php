<?php

use App\Listeners\StoreSentMailCopy;
use App\Mail\FinalRejectionMail;
use App\Services\Imap\ImapClient;
use App\Services\Imap\ImapConnector;
use App\Services\Imap\ImapException;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

/**
 * Create a local socket pair whose server side already holds the IMAP greeting and the given responses.
 *
 * @param  array<int, string>  $serverLines
 * @return array{0: resource, 1: resource} Client end and server end (keep the server end alive).
 */
function scriptedImapSockets(array $serverLines): array
{
    [$clientEnd, $serverEnd] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    fwrite($serverEnd, implode("\r\n", array_merge(['* OK IMAP ready'], $serverLines))."\r\n");
    stream_set_timeout($clientEnd, 1);

    return [$clientEnd, $serverEnd];
}

/**
 * @param  array<int, string>  $serverLines
 * @return array{0: ImapClient, 1: resource}
 */
function scriptedImapClient(array $serverLines): array
{
    [$clientEnd, $serverEnd] = scriptedImapSockets($serverLines);

    return [new ImapClient($clientEnd), $serverEnd];
}

/**
 * Bind a connector serving the scripted responses, and return the server end of the socket.
 *
 * @param  array<int, string>  $serverLines
 * @return resource
 */
function bindScriptedImapConnector(array $serverLines)
{
    [$clientEnd, $serverEnd] = scriptedImapSockets($serverLines);

    app()->instance(ImapConnector::class, new class($clientEnd) extends ImapConnector
    {
        /**
         * @param  resource  $stream
         */
        public function __construct(private $stream) {}

        public function connect(?Closure $tracer = null): ImapClient
        {
            return new ImapClient($this->stream, $tracer);
        }
    });

    return $serverEnd;
}

/**
 * @param  resource  $serverEnd
 */
function imapCommandsSent($serverEnd): string
{
    stream_set_blocking($serverEnd, false);

    return (string) stream_get_contents($serverEnd);
}

function enableImapCopy(string $subfolder = 'SapinSolidaire', bool $debug = false): string
{
    $logPath = sys_get_temp_dir().'/imap-test-'.uniqid().'.log';

    config([
        'mail.imap_copy.enabled' => true,
        'mail.imap_copy.debug' => $debug,
        'mail.imap_copy.username' => 'mailbox@example.com',
        'mail.imap_copy.password' => 'secret-password',
        'mail.imap_copy.sent_folder' => 'Sent',
        'mail.imap_copy.subfolder' => $subfolder,
        'logging.channels.imap.path' => $logPath,
    ]);

    return $logPath;
}

function sendTestMail(): void
{
    Mail::to('family@example.com')->send(new FinalRejectionMail('Dossier incomplet.'));
}

test('listener is registered on the message sent event', function () {
    Event::fake();

    Event::assertListening(MessageSent::class, StoreSentMailCopy::class);
});

test('sent emails are not copied when the option is disabled', function () {
    config(['mail.imap_copy.enabled' => false]);

    $connector = Mockery::mock(ImapConnector::class);
    $connector->shouldNotReceive('connect');
    app()->instance(ImapConnector::class, $connector);

    sendTestMail();
});

test('sent email is appended to a newly created subfolder of the sent folder', function () {
    $logPath = enableImapCopy();

    $server = bindScriptedImapConnector([
        '* LIST (\HasChildren) "." "Sent"',
        'A0001 OK LIST completed',
        '* LIST (\Noselect) "." ""',
        'A0002 OK LIST completed',
        'A0003 OK LIST completed',
        'A0004 OK CREATE completed',
        'A0005 OK SUBSCRIBE completed',
        '+ Ready for literal data',
        'A0006 OK APPEND completed',
        '* BYE Logging out',
        'A0007 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(imapCommandsSent($server))
        ->toContain("A0001 LIST \"\" \"Sent\"\r\n")
        ->toContain("A0003 LIST \"\" \"Sent.SapinSolidaire\"\r\n")
        ->toContain("A0004 CREATE \"Sent.SapinSolidaire\"\r\n")
        ->toContain("A0005 SUBSCRIBE \"Sent.SapinSolidaire\"\r\n")
        ->toMatch('/A0006 APPEND "Sent\.SapinSolidaire" \(\\\\Seen\) \{\d+\}\r\n/')
        ->toContain('To: family@example.com')
        ->toContain("A0007 LOGOUT\r\n");

    expect(file_get_contents($logPath))
        ->toContain('Storing copy of sent email to family@example.com')
        ->toContain('IMAP folder "Sent.SapinSolidaire" not found, creating it')
        ->toContain('Email copy stored in IMAP folder "Sent.SapinSolidaire"');
});

test('existing subfolder is reused without being created again', function () {
    enableImapCopy();

    $server = bindScriptedImapConnector([
        '* LIST (\HasChildren) "/" "Sent"',
        'A0001 OK LIST completed',
        '* LIST (\Noselect) "/" ""',
        'A0002 OK LIST completed',
        '* LIST (\HasNoChildren) "/" "Sent/SapinSolidaire"',
        'A0003 OK LIST completed',
        '+ Ready',
        'A0004 OK APPEND completed',
        'A0005 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(imapCommandsSent($server))
        ->not->toContain('CREATE')
        ->toMatch('/A0004 APPEND "Sent\/SapinSolidaire" /');
});

test('empty subfolder stores copies directly in the sent folder', function () {
    enableImapCopy('');

    $server = bindScriptedImapConnector([
        '* LIST (\HasNoChildren) "/" "Sent"',
        'A0001 OK LIST completed',
        '+ Ready',
        'A0002 OK APPEND completed',
        'A0003 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(imapCommandsSent($server))->toMatch('/A0002 APPEND "Sent" /');
});

test('missing sent folder falls back to the folder flagged as sent by the server', function () {
    $logPath = enableImapCopy();

    $server = bindScriptedImapConnector([
        'A0001 OK LIST completed',
        '* LIST (\HasChildren) "." INBOX',
        '* LIST (\HasNoChildren \Drafts) "." INBOX.Drafts',
        '* LIST (\HasNoChildren \UnMarked \Sent) "." INBOX.Sent',
        'A0002 OK LIST completed',
        '* LIST (\Noselect) "." ""',
        'A0003 OK LIST completed',
        'A0004 OK LIST completed',
        'A0005 OK CREATE completed',
        'A0006 OK SUBSCRIBE completed',
        '+ Ready',
        'A0007 OK APPEND completed',
        'A0008 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(imapCommandsSent($server))
        ->toContain("A0002 LIST \"\" \"*\"\r\n")
        ->toContain("A0005 CREATE \"INBOX.Sent.SapinSolidaire\"\r\n")
        ->toMatch('/A0007 APPEND "INBOX\.Sent\.SapinSolidaire" /');

    expect(file_get_contents($logPath))
        ->toContain('Sent folder "Sent" not found, using "INBOX.Sent"');
});

test('missing sent folder without special-use flag is reported', function () {
    $logPath = enableImapCopy();

    $server = bindScriptedImapConnector([
        'A0001 OK LIST completed',
        '* LIST (\HasChildren) "." INBOX',
        'A0002 OK LIST completed',
        'A0003 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(imapCommandsSent($server))->not->toContain('APPEND');
    expect(file_get_contents($logPath))
        ->toContain('Sent folder \"Sent\" not found and the server flags no folder as \\\\Sent');
});

test('debug mode logs the imap conversation without the password', function () {
    enableImapCopy('', debug: true);

    [$clientEnd, $server] = scriptedImapSockets([
        'A0001 OK LOGIN completed',
        '+ Ready',
        'A0002 OK APPEND completed',
        'A0003 OK LOGOUT completed',
    ]);
    $lines = [];
    $client = new ImapClient($clientEnd, function (string $line) use (&$lines) {
        $lines[] = $line;
    });

    $client->login('mailbox@example.com', 'secret-password');
    $client->append('Sent', "Subject: Test\r\n\r\nBody");

    expect($lines)
        ->toContain('S: * OK IMAP ready')
        ->toContain('C: A0001 LOGIN "mailbox@example.com" "***"')
        ->toContain('S: A0002 OK APPEND completed')
        ->toContain('C: [21 bytes of message data]')
        ->and(implode("\n", $lines))->not->toContain('secret-password');
});

test('debug mode writes the trace to the imap log', function () {
    $logPath = enableImapCopy('', debug: true);

    $server = bindScriptedImapConnector([
        '* LIST (\HasNoChildren) "/" "Sent"',
        'A0001 OK LIST completed',
        '+ Ready',
        'A0002 OK APPEND completed',
        'A0003 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(file_get_contents($logPath))
        ->toContain('S: * OK IMAP ready')
        ->toContain('S: A0002 OK APPEND completed');
});

test('appended message length matches the literal size announced', function () {
    [$client, $server] = scriptedImapClient([
        '+ Ready',
        'A0001 OK APPEND completed',
    ]);

    $client->append('Sent', "Subject: Test\nTo: a@example.com\n\nBody");

    preg_match('/\{(\d+)\}\r\n(.*)\r\n$/s', imapCommandsSent($server), $matches);

    expect($matches[2])->toBe("Subject: Test\r\nTo: a@example.com\r\n\r\nBody")
        ->and((int) $matches[1])->toBe(strlen($matches[2]));
});

test('imap failure is logged as error and does not prevent the email from being sent', function () {
    $logPath = enableImapCopy();

    $server = bindScriptedImapConnector([
        '* LIST (\HasNoChildren) "/" "Sent"',
        'A0001 OK LIST completed',
        '* LIST (\Noselect) "/" ""',
        'A0002 OK LIST completed',
        'A0003 OK LIST completed',
        'A0004 NO [NOPERM] Permission denied',
        'A0005 OK LOGOUT completed',
    ]);

    sendTestMail();

    expect(file_get_contents($logPath))
        ->toContain('ERROR: Unable to store sent email copy on IMAP server')
        ->toContain('Permission denied');
});

test('failed imap commands throw an exception', function () {
    [$client, $server] = scriptedImapClient(['A0001 NO Invalid credentials']);

    $client->login('user', 'wrong');
})->throws(ImapException::class, 'Invalid credentials');

test('quoted credentials escape quotes and backslashes', function () {
    [$client, $server] = scriptedImapClient(['A0001 OK LOGIN completed']);

    $client->login('user@example.com', 'pa"ss\\word');

    expect(imapCommandsSent($server))->toBe("A0001 LOGIN \"user@example.com\" \"pa\\\"ss\\\\word\"\r\n");
});
