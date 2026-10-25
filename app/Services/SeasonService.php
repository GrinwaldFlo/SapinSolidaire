<?php

namespace App\Services;

use App\Models\Season;
use App\Models\Setting;

class SeasonService
{
    /**
     * Get the current season status.
     *
     * @return array{status: string, season: ?Season, message: string}
     */
    public function getCurrentStatus(): array
    {
        $activeSeason = Season::getActive();

        if ($activeSeason) {
            return [
                'status' => 'active',
                'season' => $activeSeason,
                'message' => '',
            ];
        }

        $futureSeason = Season::getNextFuture();

        if ($futureSeason) {
            return [
                'status' => 'future',
                'season' => $futureSeason,
                'message' => CustomText::get(CustomText::SEASON_FUTURE, [
                    'date_ouverture' => $futureSeason->start_date->format('d/m/Y'),
                ]),
            ];
        }

        return [
            'status' => 'none',
            'season' => null,
            'message' => CustomText::get(CustomText::SEASON_NONE),
        ];
    }

    /**
     * Get the message explaining why registrations are closed for the season,
     * or null when families can still register and modify their request.
     */
    public function getRegistrationClosedMessage(Season $season): ?string
    {
        if (Setting::isRegistrationBlocked()) {
            return CustomText::get(CustomText::REGISTRATION_BLOCKED);
        }

        if (! $season->canModify()) {
            return CustomText::get(CustomText::REGISTRATION_DEADLINE_PASSED, [
                'date_limite' => $season->modification_deadline->format('d/m/Y'),
            ]);
        }

        return null;
    }

    /**
     * Check if season dates overlap with existing seasons.
     */
    public function hasOverlap(string $startDate, string $endDate, ?string $excludeId = null): bool
    {
        $query = Season::where(function ($q) use ($startDate, $endDate) {
            $q->where(function ($q2) use ($startDate, $endDate) {
                $q2->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
