@props(['showRejectionModal', 'isFinalRejection'])

@if($showRejectionModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 max-w-lg w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $isFinalRejection ? 'Refus définitif' : 'Demande de correction' }}
            </h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Commentaire / Motif *
                </label>
                <textarea wire:model="rejectionComment" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white" placeholder="Expliquez le motif du refus ou les corrections à apporter..."></textarea>
                @error('rejectionComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button wire:click="closeRejectionModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                    Annuler
                </button>
                <button wire:click="confirmRejection" class="{{ $isFinalRejection ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white px-4 py-2 rounded-lg">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
@endif
