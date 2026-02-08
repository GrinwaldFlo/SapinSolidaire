<?php

namespace App\Services;

use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Facades\DB;

class SlotAssignmentService
{
    /**
     * Assign pickup slots to families that don't have one yet.
     * Iterates through sub-slots lazily using integer timestamps — no
     * Carbon objects or arrays are built for the full slot grid.
     *
     * @return int Number of families assigned
     */
    public function assignUnassigned(Season $season): int
    {
        $duration = $season->slot_duration_minutes ?? 0;
        $limit = $season->family_limit_per_slot ?? 0;

        if ($duration <= 0 || $limit <= 0) {
            return 0;
        }

        $windows = $season->pickupSlots()
            ->orderBy('start_datetime')
            ->get(['id', 'start_datetime', 'end_datetime']);

        if ($windows->isEmpty()) {
            return 0;
        }

        // Count map: how many families already occupy each sub-slot
        $countMap = $this->getCountMap($season);

        // Lazy cursor — one model in memory at a time
        $cursor = $this->getUnassignedRequests($season)->getIterator();

        if (! $cursor->valid()) {
            return 0;
        }

        $durationSec = $duration * 60;
        $assigned = 0;

        foreach ($windows as $window) {
            $start = $window->start_datetime->getTimestamp();
            $end = $window->end_datetime->getTimestamp();

            for ($t = $start; ($t + $durationSec) <= $end; $t += $durationSec) {
                $slotStart = date('Y-m-d H:i:s', $t);
                $slotEnd = date('Y-m-d H:i:s', $t + $durationSec);
                $key = $slotStart . '|' . $slotEnd;

                $current = $countMap[$key] ?? 0;

                // Fill this sub-slot up to the limit
                while ($current < $limit && $cursor->valid()) {
                    $request = $cursor->current();
                    $request->update([
                        'pickup_slot_id' => $window->id,
                        'slot_start_datetime' => $slotStart,
                        'slot_end_datetime' => $slotEnd,
                    ]);
                    $current++;
                    $assigned++;
                    $cursor->next();
                }

                if (! $cursor->valid()) {
                    return $assigned;
                }
            }
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
     * Get summary data for a season (capacity, needed, enough).
     *
     * @return array{total_capacity: int, families_needed: int, has_enough: bool}
     */
    public function getSummary(Season $season): array
    {
        $capacity = $this->getTotalCapacity($season);
        $needed = $this->getFamiliesNeedingSlots($season);

        return [
            'total_capacity' => $capacity,
            'families_needed' => $needed,
            'has_enough' => $capacity >= $needed,
        ];
    }

    /**
     * Get the total slot capacity for a season computed mathematically.
     */
    public function getTotalCapacity(Season $season): int
    {
        $limit = $season->family_limit_per_slot ?? 0;
        $duration = $season->slot_duration_minutes ?? 0;

        if ($limit === 0 || $duration === 0) {
            return 0;
        }

        $totalSubSlots = 0;

        $windows = $season->pickupSlots()
            ->orderBy('start_datetime')
            ->get(['id', 'start_datetime', 'end_datetime']);

        foreach ($windows as $window) {
            $windowSeconds = $window->end_datetime->getTimestamp() - $window->start_datetime->getTimestamp();
            $totalSubSlots += (int) floor($windowSeconds / ($duration * 60));
        }

        return $totalSubSlots * $limit;
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
     * Build a lookup map of existing slot assignments using a DB aggregate.
     * Returns ["Y-m-d H:i:s|Y-m-d H:i:s" => count].
     */
    protected function getCountMap(Season $season): array
    {
        return DB::table('gift_requests')
            ->where('season_id', $season->id)
            ->whereNotNull('slot_start_datetime')
            ->groupBy('slot_start_datetime', 'slot_end_datetime')
            ->selectRaw('CONCAT(slot_start_datetime, "|", slot_end_datetime) as slot_key, COUNT(*) as cnt')
            ->pluck('cnt', 'slot_key')
            ->toArray();
    }

    /**
     * Get unassigned gift requests with received children (lazy cursor).
     */
    protected function getUnassignedRequests(Season $season): \Illuminate\Support\LazyCollection
    {
        return GiftRequest::where('season_id', $season->id)
            ->whereNull('slot_start_datetime')
            ->whereHas('children', function ($q) {
                $q->where('status', 'received');
            })
            ->cursor();
    }
}
