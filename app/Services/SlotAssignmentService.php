<?php

namespace App\Services;

use App\Models\GiftRequest;
use App\Models\PickupSlot;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotAssignmentService
{
    /**
     * Assign pickup slots to families that don't have one yet.
     *
     * @return int Number of families assigned
     */
    public function assignUnassigned(Season $season): int
    {
        $subSlots = $this->buildSubSlots($season);

        if (empty($subSlots)) {
            return 0;
        }

        $unassigned = $this->getUnassignedRequests($season);
        $assigned = 0;
        $limit = $season->family_limit_per_slot ?? 0;

        foreach ($unassigned as $request) {
            $index = $this->findAvailableSubSlotIndex($subSlots, $limit);

            if ($index === null) {
                break;
            }

            $request->update([
                'pickup_slot_id' => $subSlots[$index]['pickup_slot_id'],
                'slot_start_datetime' => $subSlots[$index]['start'],
                'slot_end_datetime' => $subSlots[$index]['end'],
            ]);

            $subSlots[$index]['count']++;
            $assigned++;
        }

        return $assigned;
    }

    /**
     * Recalculate all slot assignments for a season.
     *
     * @return int Number of families assigned
     */
    public function recalculateAll(Season $season): int
    {
        // Clear all assignments
        GiftRequest::where('season_id', $season->id)
            ->where(function ($q) {
                $q->whereNotNull('pickup_slot_id')
                    ->orWhereNotNull('slot_start_datetime');
            })
            ->update([
                'pickup_slot_id' => null,
                'slot_start_datetime' => null,
                'slot_end_datetime' => null,
            ]);

        return $this->assignUnassigned($season);
    }

    /**
     * Get the total slot capacity for a season.
     */
    public function getTotalCapacity(Season $season): int
    {
        $limit = $season->family_limit_per_slot ?? 0;

        if ($limit === 0) {
            return 0;
        }

        $subSlots = $this->buildSubSlots($season);

        return count($subSlots) * $limit;
    }

    /**
     * Get the number of families that need a slot.
     */
    public function getFamiliesNeedingSlots(Season $season): int
    {
        return GiftRequest::where('season_id', $season->id)
            ->whereHas('children', function ($q) {
                $q->where('status', 'received');
            })
            ->count();
    }

    /**
     * Check if there are enough slots for all families.
     */
    public function hasEnoughSlots(Season $season): bool
    {
        $needed = $this->getFamiliesNeedingSlots($season);
        $capacity = $this->getTotalCapacity($season);

        return $capacity >= $needed;
    }

    /**
     * Build all sub-slots by subdividing each pickup window into
     * chunks of slot_duration_minutes, with current assignment counts.
     *
     * @return array<int, array{pickup_slot_id: int, start: Carbon, end: Carbon, count: int}>
     */
    protected function buildSubSlots(Season $season): array
    {
        $duration = $season->slot_duration_minutes;

        if (! $duration || $duration <= 0) {
            return [];
        }

        $windows = $season->pickupSlots()
            ->orderBy('start_datetime')
            ->get();

        if ($windows->isEmpty()) {
            return [];
        }

        // Get all assigned requests to count per sub-slot
        $assignedRequests = GiftRequest::where('season_id', $season->id)
            ->whereNotNull('slot_start_datetime')
            ->get(['slot_start_datetime', 'slot_end_datetime']);

        $subSlots = [];

        foreach ($windows as $window) {
            $cursor = $window->start_datetime->copy();
            $windowEnd = $window->end_datetime->copy();

            while ($cursor->copy()->addMinutes($duration)->lte($windowEnd)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($duration);

                // Count how many families are already assigned to this sub-slot
                $count = $assignedRequests->filter(function ($req) use ($slotStart, $slotEnd) {
                    return $req->slot_start_datetime->eq($slotStart)
                        && $req->slot_end_datetime->eq($slotEnd);
                })->count();

                $subSlots[] = [
                    'pickup_slot_id' => $window->id,
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'count' => $count,
                ];

                $cursor->addMinutes($duration);
            }
        }

        return $subSlots;
    }

    /**
     * Get unassigned gift requests with received children.
     */
    protected function getUnassignedRequests(Season $season): Collection
    {
        return GiftRequest::where('season_id', $season->id)
            ->whereNull('slot_start_datetime')
            ->whereHas('children', function ($q) {
                $q->where('status', 'received');
            })
            ->with('family')
            ->get();
    }

    /**
     * Find the first available sub-slot index that is not full.
     */
    protected function findAvailableSubSlotIndex(array $subSlots, int $limit): ?int
    {
        if ($limit <= 0) {
            return null;
        }

        foreach ($subSlots as $index => $slot) {
            if ($slot['count'] < $limit) {
                return $index;
            }
        }

        return null;
    }
}
