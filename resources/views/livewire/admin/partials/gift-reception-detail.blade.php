<div class="p-6 space-y-4">
    <div class="text-center mb-6">
        <div class="label-value--success text-4xl font-mono">{{ $selectedChild->code }}</div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="detail-label">Prénom</span>
            <p class="detail-value">{{ $selectedChild->first_name }}</p>
        </div>
        <div>
            <span class="detail-label">Âge</span>
            <p class="detail-value">{{ $selectedChild->formatted_age }}</p>
        </div>
        <div>
            <span class="detail-label">Cadeau</span>
            <p class="detail-value">{{ $selectedChild->gift }}</p>
        </div>
        <div>
            <span class="detail-label">Taille</span>
            <p class="detail-value">{{ $selectedChild->height ? $selectedChild->height . ' cm' : '-' }}</p>
        </div>
        @if($selectedChild->shoe_size)
            <div>
                <span class="detail-label">Pointure</span>
                <p class="detail-value">{{ $selectedChild->shoe_size }}</p>
            </div>
        @endif
    </div>

    <div class="pt-6 border-t border-gray-200 dark:border-zinc-700">
        <button wire:click="markAsReceived('{{ $selectedChild->id }}')" class="w-full btn-primary">
            ✓ Marquer comme reçu
        </button>
    </div>
</div>
