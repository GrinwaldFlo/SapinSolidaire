<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | This value controls the rate limit for sending emails, in seconds.
    | This is used to prevent abuse of the email sending functionality.
    |
    */

    'rate_limit_seconds' => env('MAIL_RATE_LIMIT_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | IMAP Copy Of Sent Emails
    |--------------------------------------------------------------------------
    |
    | When enabled, every email successfully sent by the application is also
    | appended to the IMAP mailbox, inside a subfolder of the Sent folder.
    | Leave the subfolder empty to store the copies directly in Sent.
    | Encryption: "ssl" (implicit TLS, port 993), "starttls" (port 143) or "none".
    |
    */

    'imap_copy' => [
        'enabled' => (bool) env('MAIL_IMAP_COPY_ENABLED', false),
        'host' => env('MAIL_IMAP_HOST', env('MAIL_HOST')),
        'port' => (int) env('MAIL_IMAP_PORT', 993),
        'encryption' => env('MAIL_IMAP_ENCRYPTION', 'ssl'),
        'username' => env('MAIL_IMAP_USERNAME', env('MAIL_USERNAME')),
        'password' => env('MAIL_IMAP_PASSWORD', env('MAIL_PASSWORD')),
        'sent_folder' => env('MAIL_IMAP_SENT_FOLDER', 'Sent'),
        'subfolder' => env('MAIL_IMAP_SUBFOLDER', 'SapinSolidaire'),
        'timeout' => (int) env('MAIL_IMAP_TIMEOUT', 15),
        // Log the full IMAP conversation (password masked) to storage/logs/imap.log
        'debug' => (bool) env('MAIL_IMAP_DEBUG', false),
    ],

];
