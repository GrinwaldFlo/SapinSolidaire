@props(['childId'])

<button
    type="button"
    wire:click="editGift('{{ $childId }}')"
    class="btn-icon"
    title="Modifier le cadeau"
    aria-label="Modifier le cadeau"
>
    <svg class="btn-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 8 18l1.464-4.536z"/>
    </svg>
</button>
