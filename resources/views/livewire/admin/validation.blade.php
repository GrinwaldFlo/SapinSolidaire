<div class="space-y-6" x-data="{ showImageModal: false, imageUrl: '', imageAlt: '' }">
    <div class="flex items-center justify-between">
        <h1 class="section-title">Validation des demandes</h1>
        <div class="text-sm text-muted">
            {{ $queuedFamiliesCount }} famille(s) validée(s) · {{ $pendingChildrenCount }} enfant(s) en attente
        </div>
    </div>

    @if(session()->has('error'))
        <div class="notice-error">
            {{ session('error') }}
        </div>
    @endif

    @if(!$activeSeason)
        <div class="notice-warning">
            Aucune saison n'est actuellement active.
        </div>
    @elseif(!$currentRequest)
        <div class="notice-success">
            🎉 Tous les enfants des familles validées ont été traités !
        </div>
    @else
        <div class="card">
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <x-validation.family-info :request="$currentRequest" />
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
                            <span class="{{ $child->status_badge_class }}">
                                {{ $child->status_label }}
                            </span>

                            @if($child->status === \App\Models\Child::STATUS_PENDING)
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
                                    @if(!empty($predefinedRejectionMessages))
                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            <select wire:model="selectedChildMessageKeys.{{ $child->id }}" class="field-input">
                                                <option value="">Message prédéfini...</option>
                                                @foreach($predefinedRejectionMessages as $index => $message)
                                                    <option value="{{ $index }}">{{ \Illuminate\Support\Str::limit($message, 100) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" wire:click="applyChildMessage('{{ $child->id }}')" class="btn-secondary">Insérer</button>
                                        </div>
                                    @endif
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
                        class="btn-confirm"
                        @disabled($this->has_pending_decisions)>
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
