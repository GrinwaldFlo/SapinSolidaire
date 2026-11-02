<?php

namespace App\Services;

use App\Models\EmailToken;
use App\Models\RegistrationFunnelCounter as Counter;
use App\Models\Season;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Anonymous statistics on the step where families stop registering.
 *
 * Only per season and per step totals are kept. The steps already counted for a registration
 * are remembered on its access token, which is deleted with the expired tokens, so that a
 * reload or a new link does not count the same family twice.
 */
class RegistrationFunnel
{
    /**
     * Steps in the order of the family flow: step => label.
     */
    public const STEPS = [
        Counter::STEP_LINK_REQUESTED => 'Lien demandé',
        Counter::STEP_LINK_OPENED => 'Lien ouvert',
        Counter::STEP_CONSECUTIVE_YEARS_ACCEPTED => 'Condition des années consécutives acceptée',
        Counter::STEP_PICKUP_ACCEPTED => 'Condition de retrait acceptée',
        Counter::STEP_CITY_ACCEPTED => 'Commune confirmée (accès au formulaire)',
        Counter::STEP_FAMILY_INFO_COMPLETED => 'Informations de la famille complétées',
        Counter::STEP_PROOF_COMPLETED => 'Justificatif de domicile fourni',
        Counter::STEP_ANONYMOUS_CHOICE_MADE => 'Choix de l\'anonymat effectué',
        Counter::STEP_CHILDREN_COMPLETED => 'Enfants saisis (résumé affiché)',
        Counter::STEP_SUBMIT_ATTEMPTED => 'Envoi du formulaire tenté',
        Counter::STEP_SUBMITTED => 'Demande enregistrée',
    ];

    /**
     * Steps that only exist in some form modes (multi-step form, proof of habitation):
     * hidden when never reached, otherwise they would look like everyone stopped there.
     */
    private const OPTIONAL_STEPS = [
        Counter::STEP_FAMILY_INFO_COMPLETED,
        Counter::STEP_PROOF_COMPLETED,
        Counter::STEP_ANONYMOUS_CHOICE_MADE,
        Counter::STEP_CHILDREN_COMPLETED,
    ];

    /**
     * Families stopped by a rule when opening their link: reason => label.
     */
    public const BLOCKED = [
        Counter::BLOCKED_REGISTRATION_CLOSED => 'Inscriptions closes',
        Counter::BLOCKED_CONSECUTIVE_YEARS => 'Limite d\'années consécutives atteinte',
        Counter::BLOCKED_EXCLUDED => 'Famille exclue',
    ];

    /**
     * Count a step once per registration. Never throws: statistics must not break the family flow.
     */
    public function record(Season $season, string $email, string $step): void
    {
        try {
            DB::transaction(function () use ($season, $email, $step): void {
                $emailToken = EmailToken::where('email', $email)->lockForUpdate()->first();
                $countedSteps = $emailToken?->funnel_steps ?? [];

                if (! $emailToken || in_array($step, $countedSteps, true)) {
                    return;
                }

                $emailToken->update(['funnel_steps' => [...$countedSteps, $step]]);
                $this->increment($season, $step);
            });
        } catch (\Throwable $e) {
            Log::warning('Registration funnel step not recorded: '.$e->getMessage(), ['step' => $step]);
        }
    }

    /**
     * @return array{
     *     steps: array<int, array{label: string, count: int, percent: int, stopped: int|null}>,
     *     blocked: array<int, array{label: string, count: int}>,
     *     started: int,
     *     submitted: int,
     *     completionPercent: int,
     *     biggestStop: array{label: string, count: int, percent: int, stopped: int|null}|null,
     * }
     */
    public function report(Season $season): array
    {
        $counts = Counter::where('season_id', $season->id)->pluck('count', 'step');

        $blocked = [];
        foreach (self::BLOCKED as $reason => $label) {
            $blocked[] = ['label' => $label, 'count' => (int) $counts->get($reason, 0)];
        }

        $reachedSteps = [];
        foreach (self::STEPS as $step => $label) {
            $count = (int) $counts->get($step, 0);

            if ($count > 0 || ! in_array($step, self::OPTIONAL_STEPS, true)) {
                $reachedSteps[$step] = ['label' => $label, 'count' => $count];
            }
        }

        // Tokens older than this feature can reach a later step without the earlier ones
        $started = max(array_column($reachedSteps, 'count'));
        $steps = $this->withStops($reachedSteps, array_sum(array_column($blocked, 'count')), $started);
        $submitted = (int) $counts->get(Counter::STEP_SUBMITTED, 0);

        return [
            'steps' => $steps,
            'blocked' => $blocked,
            'started' => $started,
            'submitted' => $submitted,
            'completionPercent' => $this->percent($submitted, $started),
            'biggestStop' => $this->biggestStop($steps),
        ];
    }

    /**
     * Add to each step the share of registrations that reached it and the number that stopped there.
     *
     * @param  array<string, array{label: string, count: int}>  $reachedSteps
     * @return array<int, array{label: string, count: int, percent: int, stopped: int|null}>
     */
    private function withStops(array $reachedSteps, int $blockedCount, int $started): array
    {
        $steps = [];
        $stepNames = array_keys($reachedSteps);

        foreach ($stepNames as $index => $step) {
            $count = $reachedSteps[$step]['count'];
            $nextStep = $stepNames[$index + 1] ?? null;
            $stopped = null;

            if ($nextStep !== null) {
                // Blocked families did not choose to stop: they are listed apart
                $alreadyExplained = $step === Counter::STEP_LINK_OPENED ? $blockedCount : 0;
                $stopped = max(0, $count - $reachedSteps[$nextStep]['count'] - $alreadyExplained);
            }

            $steps[] = [
                'label' => $reachedSteps[$step]['label'],
                'count' => $count,
                'percent' => $this->percent($count, $started),
                'stopped' => $stopped,
            ];
        }

        return $steps;
    }

    /**
     * @param  array<int, array{label: string, count: int, percent: int, stopped: int|null}>  $steps
     * @return array{label: string, count: int, percent: int, stopped: int|null}|null
     */
    private function biggestStop(array $steps): ?array
    {
        $biggest = collect($steps)->sortByDesc('stopped')->first();

        return ($biggest['stopped'] ?? 0) > 0 ? $biggest : null;
    }

    private function increment(Season $season, string $step): void
    {
        Counter::insertOrIgnore([
            'id' => (string) Str::uuid(),
            'season_id' => $season->id,
            'step' => $step,
            'count' => 0,
        ]);

        Counter::where('season_id', $season->id)->where('step', $step)->increment('count');
    }

    private function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round($count / $total * 100) : 0;
    }
}
