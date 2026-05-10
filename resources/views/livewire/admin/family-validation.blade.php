<div class="space-y-6" x-data="{ showImageModal: false, imageUrl: '', imageAlt: '' }">
    <div class="flex items-center justify-between">
        <h1 class="section-title text-2xl !border-0 !mb-0">Validation des familles</h1>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ $pendingFamiliesCount }} famille(s) en attente
        </div>
    </div>

    @if(!$activeSeason)
        <div class="notice-info">
            Aucune saison n'est actuellement active.
        </div>
    @elseif(!$currentRequest)
        <div class="badge-success">
            🎉 Toutes les familles ont été traitées !
        </div>
    @else
        <div class="card !p-6">
            <div class="mb-6">
                <x-validation.family-info :request="$currentRequest">
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="validateFamily" class="btn-confirm text-sm">
                            ✓ Valider la famille
                        </button>
                        <button wire:click="openRejectionModal('{{ $currentRequest->id }}', false)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-200">
                            Demander correction
                        </button>
                        <button wire:click="openRejectionModal('{{ $currentRequest->id }}', true)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-200">
                            Refuser définitivement
                        </button>
                        <button wire:click="skip" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-200 ml-auto">
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
