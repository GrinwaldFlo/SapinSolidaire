<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Mailable;

class SentMailLog extends Model
{
    use HasFactory, HasUuids;

    public const PURPOSE_ACCESS_LINK = 'Lien d\'accès au formulaire';
    public const PURPOSE_CORRECTION_REQUEST = 'Demande de correction';
    public const PURPOSE_FINAL_REJECTION = 'Refus définitif';
    public const PURPOSE_GIFT_CONFIRMATION = 'Confirmation de réception des cadeaux';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'recipient_email',
        'purpose',
        'mailable_class',
        'sent_by_user_id',
        'sent_by_label',
    ];

    /**
     * Get the user who initiated the email sending.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public static function logQueuedMail(
        string $recipientEmail,
        string $purpose,
        Mailable $mailable,
        ?User $sender = null,
        ?string $senderLabel = null,
    ): void {
        self::create([
            'recipient_email' => $recipientEmail,
            'purpose' => $purpose,
            'mailable_class' => $mailable::class,
            'sent_by_user_id' => $sender?->id,
            'sent_by_label' => $senderLabel ?? $sender?->name,
        ]);
    }
}
