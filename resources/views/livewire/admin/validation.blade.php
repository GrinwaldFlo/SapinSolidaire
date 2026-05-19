<div class="space-y-6" x-data="{ showImageModal: false, imageUrl: '', imageAlt: '' }">
    <x-admin.family-modal :showFamilyModal="$showFamilyModal" :selectedFamily="$selectedFamily" />
    <div class="flex items-center justify-between">
        <h1 class="section-title">Validation des demandes</h1>
        <div class="text-sm text-muted">
            {{ $pendingFamiliesCount }} famille(s) · {{ $pendingChildrenCount }} enfant(s) en attente
        </div>
    </div>

    @if(!$activeSeason)
        <div class="notice-warning">
            Aucune saison n'est actuellement active.
        </div>
    @elseif(!$currentRequest)
        <div class="notice-success">
            🎉 Toutes les demandes ont été traitées !
        </div>
    @else
        @if(!empty($potentialDuplicates))
            <div class="notice-warning">
                <strong>⚠ {{ __('Doublon potentiel détecté') }}</strong> —
                {{ __('Cette famille ressemble à') }} {{ count($potentialDuplicates) }} {{ __('famille(s) existante(s)') }} :
                <ul class="mt-1 list-disc list-inside">
                    @foreach($potentialDuplicates as $dup)
                        <li>
                            <button wire:click="showFamilyDetails('{{ $dup['id'] }}')" class="link">
                                {{ $dup['last_name'] }} {{ $dup['first_name'] }}
                            </button>
                            — {{ __('score') }} : {{ $dup['score'] }}%
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <x-validation.family-info :request="$currentRequest">
                    @if($currentRequest->status === 'pending')
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-lg">
                        <h3 class="section-title">Décision pour la famille :</h3>
                        <div class="flex flex-wrap gap-4 mb-3">
                            <label class="flex items-center space-x-2">
                                <input type="radio" wire:model.live="familyDecision" value="pending" class="form-radio text-blue-600">
                                <span>En attente</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" wire:model.live="familyDecision" value="validated" class="form-radio text-green-600">
                                <span>Valider</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" wire:model.live="familyDecision" value="correction" class="form-radio text-yellow-600">
                                <span>Demander correction</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" wire:model.live="familyDecision" value="rejected" class="form-radio text-red-600">
                                <span>Refuser définitivement</span>
                            </label>
                        </div>
                        @if(in_array($familyDecision, ['correction', 'rejected']))
                            <textarea wire:model="familyComment" class="field-input mt-2" rows="2" placeholder="Commentaire..."></textarea>
                            @error('familyComment') <p class="field-error mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    @endif
                </x-validation.family-info>
            </div>

            <h2 class="section-title">Enfants ({{ $currentRequest->children->count() }})</h2>

            <div class="space-y-4">
                @foreach($currentRequest->children as $child)
                    <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <span class="detail-label">Prénom :</span>
                                <span class="ml-2 detail-value">{{ $child->first_name }}</span>
                                @if($child->anonymous)
                                    <span class="ml-2 text-xs text-orange-600 dark:text-orange-400">(Anonyme)</span>
                                @endif
                            </div>
                            <div>
                                <span class="detail-label">Genre :</span>
                                <span class="ml-2 detail-value">{{ $child->gender_label }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Âge :</span>
                                <span class="ml-2 detail-value">{{ $child->formatted_age }} ({{ $child->birth_year }})</span>
                            </div>
                            <div>
                                <span class="detail-label">Taille :</span>
                                <span class="ml-2 detail-value">{{ $child->height ? $child->height . ' cm' : '-' }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Cadeau :</span>
                                <span class="ml-2 detail-value">{{ $child->gift }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Pointure :</span>
                                <span class="ml-2 detail-value">{{ $child->shoe_size ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Code :</span>
                                <span class="ml-2 detail-value font-mono font-bold">{{ $child->code ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="{{ $child->status === 'pending' ? 'badge--pending' : 'badge--validated' }}">
                                {{ $child->status_label }}
                            </span>

                            @if($child->status === 'pending')
                            <div class="w-full mt-4 p-3 bg-gray-50 dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-lg">
                                <div class="flex flex-wrap gap-4 mb-3">
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" wire:model.live="childDecisions.{{ $child->id }}" value="pending" class="form-radio text-blue-600">
                                        <span class="text-sm">En attente</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" wire:model.live="childDecisions.{{ $child->id }}" value="validated" class="form-radio text-green-600">
                                        <span class="text-sm">Valider</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" wire:model.live="childDecisions.{{ $child->id }}" value="correction" class="form-radio text-yellow-600">
                                        <span class="text-sm">Correction</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" wire:model.live="childDecisions.{{ $child->id }}" value="rejected" class="form-radio text-red-600">
                                        <span class="text-sm">Refuser</span>
                                    </label>
                                </div>
                                @if(in_array($childDecisions[$child->id] ?? '', ['correction', 'rejected']))
                                    <textarea wire:model="childComments.{{ $child->id }}" class="field-input mt-2 text-sm" rows="2" placeholder="Commentaire..."></textarea>
                                    @error('childComments.'.$child->id) <p class="field-error mt-1 mb-2">{{ $message }}</p> @enderror
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-zinc-700 flex justify-end gap-3">
                <button wire:click="skip" class="btn-secondary">
                    Passer au suivant
                </button>
                <button wire:click="submitValidation" 
                        class="btn-confirm {{ $this->has_pending_decisions ? 'bg-gray-400 opacity-50 cursor-not-allowed' : '' }}"
                        @if($this->has_pending_decisions) disabled @endif>
                    Soumettre la validation
                </button>
            </div>
        </div>
    @endif

    <x-validation.image-preview-modal />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('scroll-to-top', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</div>
