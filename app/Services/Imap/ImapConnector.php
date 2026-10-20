<?php

namespace App\Services\Imap;

use Closure;

/**
 * Opens authenticated IMAP connections from the mail.imap_copy configuration.
 */
class ImapConnector
{
    /**
     * @param  (Closure(string): void)|null  $tracer  Receives every line exchanged with the server.
     */
    public function connect(?Closure $tracer = null): ImapClient
    {
        $config = config('mail.imap_copy');

        $client = ImapClient::connect(
            (string) $config['host'],
            (int) $config['port'],
            (string) $config['encryption'],
            (int) $config['timeout'],
            $tracer,
        );

        $client->login((string) $config['username'], (string) $config['password']);

        return $client;
    }
}
