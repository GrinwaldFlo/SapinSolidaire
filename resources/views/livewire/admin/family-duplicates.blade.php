<div class="space-y-6">
    <h1 class="section-title">Détection de doublons</h1>

    @if(session('success'))
        <div class="notice-success">{{ session('success') }}</div>
    @endif

    {{-- Controls --}}
    <div class="card-sm flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-48">
            <label class="field-label">Seuil de similarité minimum : {{ $threshold }}%</label>
            <input type="range" min="10" max="90" step="5" wire:model.live="threshold" class="w-full accent-green-600">
        </div>
        <button wire:click="scan" wire:loading.attr="disabled" class="btn-blue">
            <span wire:loading.remove wire:target="scan">Analyser les familles</span>
            <span wire:loading wire:target="scan">Analyse en cours…</span>
        </button>
    </div>

    {{-- Results --}}
    @if($pairs === null)
        <div class="notice-info">Cliquez sur « Analyser les familles » pour détecter les potentiels doublons.</div>
    @elseif($pairs->isEmpty())
        <div class="notice-success">Aucun doublon détecté avec un seuil de {{ $threshold }}%.</div>
    @else
        <p class="text-muted text-sm">{{ $pairs->count() }} paire(s) suspecte(s) trouvée(s), triée(s) par similarité décroissante.</p>

        <div class="space-y-4">
            @foreach($pairs as $pair)
                @php
                    $aId = $pair['familyA']['id'];
                    $bId = $pair['familyB']['id'];
                    $score = $pair['score'];
                    $details = $pair['details'];
                    $colorClass = $score >= 75 ? 'badge--rejected' : ($score >= 55 ? 'badge--warning' : 'badge--neutral');
                @endphp
                <div class="card space-y-4" wire:key="pair-{{ $aId }}-{{ $bId }}">
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="{{ $colorClass }} text-sm font-bold">{{ $score }}%</span>
                            <span class="text-muted text-sm">
                                Nom: {{ $details['last_name'] }}pts &bull;
                                Prénom: {{ $details['first_name'] }}pts &bull;
                                Adresse: {{ round($details['street_name'] + $details['postal_code'] + $details['city'], 1) }}pts &bull;
                                Tél: {{ $details['phone'] }}pts &bull;
                                Enfants: {{ $details['children'] }}pts
                            </span>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <button wire:click="openMerge('{{ $aId }}', '{{ $bId }}')" class="btn-confirm">
                                Fusionner
                            </button>
                            <button wire:click="dismissPair('{{ $aId }}', '{{ $bId }}')" class="btn-secondary">
                                Ignorer
                            </button>
                        </div>
                    </div>

                    {{-- Side-by-side comparison --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach(['A', 'B'] as $side)
                            @php $f = $side === 'A' ? $pair['familyA'] : $pair['familyB']; @endphp
                            <div class="card-sm space-y-2">
                                <p class="sub-label">Famille {{ $side }}</p>
                                <p class="detail-value font-semibold">{{ $f['first_name'] }} {{ $f['last_name'] }}</p>
                                <p class="text-muted text-sm break-all">{{ $f['email'] }}</p>
                                @if($f['phone'])
                                    <p class="text-muted text-sm">{{ $f['phone'] }}</p>
                                @endif
                                <p class="text-muted text-sm">
                                    {{ implode(', ', array_filter([$f['street_name'], $f['house_no'], $f['postal_code'], $f['city']])) }}
                                </p>
                                @if(!empty($f['seasons']))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($f['seasons'] as $season)
                                            <span class="badge--info text-xs">{{ $season }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Merge Modal --}}
    @if($showMergeModal && $modalPair)
        <div class="modal-backdrop" wire:click.self="closeMerge">
            <div class="modal-panel max-w-3xl">
                <div class="modal-header">
                    <h2 class="font-semibold text-lg">Fusionner deux familles</h2>
                    <button wire:click="closeMerge" class="text-muted hover:text-red-500 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Keep side selection --}}
                    <div>
                        <p class="field-label mb-2">Quelle famille souhaitez-vous conserver ?</p>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach(['A', 'B'] as $side)
                                @php $f = $side === 'A' ? $modalPair['familyA'] : $modalPair['familyB']; @endphp
                                <button
                                    wire:click="$set('keepSide', '{{ $side }}')"
                                    class="{{ $keepSide === $side ? 'ring-2 ring-green-500' : '' }} card-sm text-left space-y-1 w-full transition"
                                >
                                    <p class="sub-label">Famille {{ $side }}</p>
                                    <p class="detail-value font-semibold">{{ $f['first_name'] }} {{ $f['last_name'] }}</p>
                                    <p class="text-muted text-sm break-all">{{ $f['email'] }}</p>
                                    @if($f['phone'])<p class="text-muted text-sm">{{ $f['phone'] }}</p>@endif
                                    @if(!empty($f['seasons']))
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($f['seasons'] as $season)
                                                <span class="badge--info text-xs">{{ $season }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Field-level overrides --}}
                    @php
                        $familyFields = [
                            'email'       => 'Email',
                            'first_name'  => 'Prénom',
                            'last_name'   => 'Nom',
                            'phone'       => 'Téléphone',
                            'street_name' => 'Rue',
                            'house_no'    => 'N° de rue',
                            'postal_code' => 'Code postal',
                            'city'        => 'Ville',
                        ];
                        $keptFamily    = $keepSide === 'A' ? $modalPair['familyA'] : $modalPair['familyB'];
                        $removedFamily = $keepSide === 'A' ? $modalPair['familyB'] : $modalPair['familyA'];
                    @endphp

                    <div>
                        <p class="field-label mb-2">Champs à conserver (cochez pour utiliser la valeur de l'autre famille)</p>
                        <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($familyFields as $field => $label)
                                @php
                                    $valA = $keptFamily[$field] ?? '';
                                    $valB = $removedFamily[$field] ?? '';
                                    $differ = $valA !== $valB;
                                @endphp
                                @if($differ)
                                    <div class="py-2 flex items-center gap-3 flex-wrap">
                                        <span class="w-28 shrink-0 detail-label text-xs">{{ $label }}</span>
                                        <div class="flex-1 grid grid-cols-2 gap-2 text-sm">
                                            <div class="{{ !in_array($field, $overrideFields) ? 'font-semibold detail-value' : 'text-muted line-through' }} break-all">
                                                {{ $valA ?: '—' }}
                                                @if(!in_array($field, $overrideFields))
                                                    <span class="badge--validated ml-1 text-xs">Conservé</span>
                                                @endif
                                            </div>
                                            <div class="{{ in_array($field, $overrideFields) ? 'font-semibold detail-value' : 'text-muted line-through' }} break-all">
                                                {{ $valB ?: '—' }}
                                                @if(in_array($field, $overrideFields))
                                                    <span class="badge--validated ml-1 text-xs">Conservé</span>
                                                @endif
                                            </div>
                                        </div>
                                        <label class="flex items-center gap-1 text-sm cursor-pointer shrink-0">
                                            <input type="checkbox"
                                                wire:model.live="overrideFields"
                                                value="{{ $field }}"
                                                class="accent-green-600">
                                            Utiliser l'autre
                                        </label>
                                    </div>
                                @else
                                    <div class="py-2 flex items-center gap-3 text-sm text-muted">
                                        <span class="w-28 shrink-0 detail-label text-xs">{{ $label }}</span>
                                        <span class="break-all">{{ $valA ?: '—' }}</span>
                                        <span class="badge--neutral text-xs ml-auto">Identique</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Children preview --}}
                    @php
                        $allRequests = array_merge(
                            array_map(fn($r) => array_merge($r, ['side' => 'A']), $keptFamily['requests']),
                            array_map(fn($r) => array_merge($r, ['side' => 'B']), $removedFamily['requests']),
                        );
                        $genderLabels = ['boy' => 'Garçon', 'girl' => 'Fille', 'unspecified' => 'Non précisé'];
                    @endphp
                    @if(!empty($allRequests))
                        <div>
                            <p class="field-label mb-2">Aperçu des enfants après fusion</p>
                            <div class="space-y-2">
                                @foreach($allRequests as $req)
                                    <div class="card-sm text-sm space-y-1">
                                        <p class="detail-label">{{ $req['season'] }}
                                            <span class="badge--info ml-1 text-xs">Famille {{ $req['side'] }}</span>
                                        </p>
                                        @if(!empty($req['children']))
                                            <ul class="list-disc list-inside text-muted">
                                                @foreach($req['children'] as $child)
                                                    <li>{{ $child['first_name'] }} ({{ $child['birth_year'] ?? '?' }}) — {{ $genderLabels[$child['gender']] ?? 'Non précisé' }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted">Aucun enfant</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="notice-warning text-sm">
                        ⚠️ Cette action est irréversible. La famille supprimée et toutes ses données seront définitivement effacées. Les demandes et enfants seront transférés vers la famille conservée.
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="closeMerge" class="btn-secondary">Annuler</button>
                    <button wire:click="confirmMerge" wire:loading.attr="disabled" wire:target="confirmMerge" class="btn-danger">
                        <span wire:loading.remove wire:target="confirmMerge">Fusionner définitivement</span>
                        <span wire:loading wire:target="confirmMerge">Fusion en cours…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
