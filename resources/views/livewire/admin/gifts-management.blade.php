<div class="space-y-6">
    <h1 class="section-title">Gestion des cadeaux</h1>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="card">
        <form wire:submit="save" class="space-y-6">
            <div>
                <label class="field-label">Propositions de cadeaux</label>
                <textarea wire:model="giftSuggestions" rows="8" placeholder="Un cadeau par ligne" class="field-input"></textarea>
                <p class="mt-1 text-sm text-muted">Un cadeau par ligne. Ces suggestions apparaîtront dans l'autocomplétion du formulaire.</p>
            </div>

            <div>
                <label class="field-label">Restrictions de cadeaux (cadeaux interdits)</label>
                <textarea wire:model="giftRestrictions" rows="6" placeholder="Un mot-clé par ligne" class="field-input"></textarea>
                <p class="mt-1 text-sm text-muted">Un mot-clé par ligne. Si le cadeau demandé contient un de ces mots, il sera refusé.</p>
            </div>

            <div>
                <label class="field-label">Cadeaux avec pointure</label>
                <textarea wire:model="giftsWithShoeSize" rows="6" placeholder="Ex: chaussure, basket" class="field-input"></textarea>
                <p class="mt-1 text-sm text-muted">Un mot-clé par ligne. Si le cadeau contient un de ces mots, la pointure devient obligatoire.</p>
            </div>

            <div>
                <label class="field-label">Cadeaux avec taille</label>
                <textarea wire:model="giftsWithSize" rows="6" placeholder="Ex: veste, pantalon" class="field-input"></textarea>
                <p class="mt-1 text-sm text-muted">Un mot-clé par ligne. Si le cadeau contient un de ces mots, la taille (cm) devient obligatoire.</p>
            </div>

            <div class="pt-4">
                <button type="submit" wire:loading.attr="disabled" class="btn-confirm">
                    <span wire:loading.remove wire:target="save">Enregistrer les paramètres cadeaux</span>
                    <span wire:loading wire:target="save">Enregistrement...</span>
                </button>
            </div>
        </form>
    </div>
</div>
