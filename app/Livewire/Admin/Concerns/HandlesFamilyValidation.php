<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\AdminActionLog;
use App\Models\GiftRequest;
use Illuminate\Support\Facades\DB;

trait HandlesFamilyValidation
{
    use SendsRejectionEmail;

    public bool $showRejectionModal = false;

    public bool $isFinalRejection = false;

    public string $rejectionComment = '';

    public function validateFamily(): void
    {
        if (! $this->currentRequest) {
            return;
        }

        $validatedRequest = null;

        DB::transaction(function () use (&$validatedRequest) {
            $request = GiftRequest::lockForUpdate()->find($this->currentRequest->id);

            if (! $request || $request->status !== GiftRequest::STATUS_PENDING) {
                return;
            }

            if ($request->family_number === null) {
                $request->family_number = $this->activeSeason->assignNextFamilyNumber();
                $request->save();
            }

            $request->setStatus(GiftRequest::STATUS_VALIDATED);
            $this->currentRequest = $request;
            $validatedRequest = $request->loadMissing('family');
        });

        if ($validatedRequest instanceof GiftRequest) {
            AdminActionLog::record(
                AdminActionLog::ACTION_FAMILY_VALIDATED,
                'Validation famille: '.$validatedRequest->family?->full_name,
                $validatedRequest->family_id,
                $validatedRequest->id,
            );
        }

        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function closeRejectionModal(): void
    {
        $this->showRejectionModal = false;
        $this->isFinalRejection = false;
        $this->rejectionComment = '';

        if (property_exists($this, 'selectedRejectionMessageKey')) {
            $this->selectedRejectionMessageKey = '';
        }
    }
}
