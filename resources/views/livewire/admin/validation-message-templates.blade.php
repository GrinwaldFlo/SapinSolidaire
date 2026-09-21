<div class="space-y-6">
    <h1 class="section-title">Messages prédéfinis de validation</h1>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="card">
        <p class="text-muted">
            Ces messages peuvent être insérés rapidement lors d'une demande de correction ou d'un refus dans la page de validation.
        </p>

        <form wire:submit="save" class="space-y-4 mt-4">
            @foreach($messages as $index => $message)
                <div class="card-sm">
                    <div class="flex items-center justify-between mb-2">
                        <label class="field-label">Message {{ $index + 1 }}</label>
                        <button type="button" wire:click="removeMessage({{ $index }})" class="btn-danger">Supprimer</button>
                    </div>

                    <textarea
                        wire:model="messages.{{ $index }}"
                        rows="4"
                        class="field-input"
                        placeholder="Ex: Merci de corriger la date de naissance de l'enfant et de vérifier l'adresse complète."
                    ></textarea>
                    @error('messages.'.$index) <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="button" wire:click="addMessage" class="btn-secondary">
                    Ajouter un message
                </button>

                <button type="submit" wire:loading.attr="disabled" class="btn-confirm">
                    <span wire:loading.remove wire:target="save">Enregistrer</span>
                    <span wire:loading wire:target="save">Enregistrement...</span>
                </button>
            </div>
        </form>
    </div>
</div>
