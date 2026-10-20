<?php

namespace App\Listeners;

use App\Services\SentMailArchiver;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

class StoreSentMailCopy
{
    public function __construct(private SentMailArchiver $archiver) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $recipients = implode(', ', array_map(fn (Address $address) => $address->getAddress(), $message->getTo()));

        $this->archiver->archive(
            $event->sent->toString(),
            "to {$recipients} \"{$message->getSubject()}\""
        );
    }
}
