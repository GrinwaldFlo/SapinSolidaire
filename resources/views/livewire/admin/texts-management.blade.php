<div class="space-y-6">
    <h1 class="section-title">Textes personnalisables</h1>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="notice-info">
        <p class="notice-title">Comment ça marche ?</p>
        <ul class="notice-list">
            <li>Laissez un champ vide pour utiliser le texte par défaut (affiché en gris dans le champ).</li>
            <li>Les éléments entre accolades, comme <span class="placeholder-tag">{site}</span>, sont remplacés automatiquement lors de l'affichage.</li>
            <li>Les retours à la ligne sont conservés.</li>
        </ul>
    </div>

    <form wire:submit="save" class="space-y-6">
        @foreach($groups as $group)
            <div class="card space-y-6">
                <h2 class="section-title">{{ $group['title'] }}</h2>

                @foreach($group['texts'] as $key => $definition)
                    <div wire:key="text-{{ $key }}">
                        <label for="text-{{ $key }}" class="field-label">{{ $definition['label'] }}</label>

                        @if($definition['multiline'])
                            <textarea id="text-{{ $key }}" wire:model.blur="texts.{{ $key }}" rows="4" placeholder="{{ $definition['default'] }}" class="field-input"></textarea>
                        @else
                            <input id="text-{{ $key }}" type="text" wire:model.blur="texts.{{ $key }}" placeholder="{{ $definition['default'] }}" class="field-input">
                        @endif

                        @if(!empty($definition['help']))
                            <p class="field-help">{{ $definition['help'] }}</p>
                        @endif

                        <p class="field-help">
                            Variables :
                            @foreach($placeholders[$key] as $placeholder => $description)
                                <span class="placeholder-tag">{{ $placeholder }}</span> {{ $description }}@if(!$loop->last) · @endif
                            @endforeach
                        </p>

                        @if($definition['default'] !== '')
                            <div class="button-row">
                                @if(($texts[$key] ?? '') === '')
                                    <button type="button" wire:click="useDefault('{{ $key }}')" class="btn-secondary btn-sm">Modifier à partir du texte par défaut</button>
                                @else
                                    <button type="button" wire:click="resetText('{{ $key }}')" class="btn-secondary btn-sm">Rétablir le texte par défaut</button>
                                @endif
                            </div>
                        @endif

                        @error("texts.{$key}") <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        @endforeach

        <div>
            <button type="submit" wire:loading.attr="disabled" class="btn-confirm">
                <span wire:loading.remove wire:target="save">Enregistrer les textes</span>
                <span wire:loading wire:target="save">Enregistrement...</span>
            </button>
        </div>
    </form>
</div>
