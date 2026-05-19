<div class="space-y-6" x-data="{ showImageModal: false, imageUrl: '', imageAlt: '' }">
    <div class="flex items-center justify-between">
        <h1 class="section-title">Validation des familles</h1>
        <div class="text-sm text-muted">
            {{ $pendingFamiliesCount }} famille(s) en attente
        </div>
    </div>

    @if(!$activeSeason)
        <div class="notice-warning">
            Aucune saison n'est actuellement active.
        </div>
    @elseif(!$currentRequest)
        <div class="notice-success">
            🎉 Toutes les familles ont été traitées !
        </div>
    @else
        @if(!empty($potentialDuplicates))
            <div class="notice-warning">
                <strong>⚠ {{ __('Doublon potentiel détecté') }}</strong> —
                {{ __('Cette famille ressemble à') }} {{ count($potentialDuplicates) }} {{ __('famille(s) existante(s)') }} :
                <ul class="mt-1 list-disc list-inside">
                    @foreach($potentialDuplicates as $dup)
                        <li>{{ $dup['last_name'] }} {{ $dup['first_name'] }} — {{ __('score') }} : {{ $dup['score'] }}%</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="mb-6">
                <x-validation.family-info :request="$currentRequest">
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="validateFamily" class="btn-confirm text-sm">
                            ✓ Valider la famille
                        </button>
                        <button wire:click="openRejectionModal('{{ $currentRequest->id }}', false)" class="btn-warning text-sm">
                            Demander correction
                        </button>
                        <button wire:click="openRejectionModal('{{ $currentRequest->id }}', true)" class="btn-danger text-sm">
                            Refuser définitivement
                        </button>
                        <button wire:click="skip" class="btn-gray text-sm ml-auto">
                            Passer au suivant
                        </button>
                    </div>
                </x-validation.family-info>
            </div>
        </div>
    @endif

    <x-validation.image-preview-modal />
    <x-validation.rejection-modal :showRejectionModal="$showRejectionModal" :isFinalRejection="$isFinalRejection" />
</div>
