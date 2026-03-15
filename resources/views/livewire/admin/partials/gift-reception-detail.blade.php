<div class="p-6 space-y-4">
    <div class="text-center mb-6">
        <div class="text-4xl font-mono font-bold text-green-600">{{ $selectedChild->code }}</div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Prénom</span>
            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->first_name }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Âge</span>
            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->age }} ans</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Cadeau</span>
            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->gift }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Taille</span>
            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->height ? $selectedChild->height . ' cm' : '-' }}</p>
        </div>
        @if($selectedChild->shoe_size)
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Pointure</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->shoe_size }}</p>
            </div>
        @endif
    </div>

    <div class="pt-6 border-t border-gray-200 dark:border-zinc-700">
        <button wire:click="markAsReceived('{{ $selectedChild->id }}')" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold">
            ✓ Marquer comme reçu
        </button>
    </div>
</div>
