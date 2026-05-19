@if($showFamilyModal && $selectedFamily)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeFamilyModal()">
        <div class="fixed inset-0 bg-black/50" wire:click="closeFamilyModal"></div>
        <div class="modal-panel">
            <div class="modal-header">
                <h2 class="section-title">Famille {{ $selectedFamily['last_name'] }}</h2>
                <button wire:click="closeFamilyModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                {{-- Parent --}}
                <div>
                    <h3 class="sub-label">Parent</h3>
                    <p class="detail-value">{{ $selectedFamily['first_name'] }} {{ $selectedFamily['last_name'] }}</p>
                </div>

                {{-- Email --}}
                @if($selectedFamily['email'])
                    <div>
                        <h3 class="sub-label">Email</h3>
                        <a href="mailto:{{ $selectedFamily['email'] }}" class="link break-all">{{ $selectedFamily['email'] }}</a>
                    </div>
                @endif

                {{-- Phone --}}
                @if($selectedFamily['phone'])
                    <div>
                        <h3 class="sub-label">Téléphone</h3>
                        <a href="tel:{{ $selectedFamily['tel_phone'] }}" class="link">{{ $selectedFamily['formatted_phone'] }}</a>
                    </div>
                @endif

                {{-- Address --}}
                @if($selectedFamily['full_address'])
                    <div>
                        <h3 class="sub-label">Adresse</h3>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($selectedFamily['full_address']) }}" target="_blank" rel="noopener" class="link">{{ $selectedFamily['full_address'] }}</a>
                    </div>
                @endif

                {{-- Children --}}
                @if(count($selectedFamily['children']) > 0)
                    <div>
                        <h3 class="sub-label mb-2">Enfants</h3>
                        <ul class="space-y-2">
                            @foreach($selectedFamily['children'] as $familyChild)
                                <li class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-3">
                                    <p class="detail-value">{{ $familyChild['first_name'] }}</p>
                                    <p class="text-sm text-muted">
                                        {{ $familyChild['formatted_age'] }}
                                        @if($familyChild['gender_label']) — {{ $familyChild['gender_label'] }} @endif
                                        @if($familyChild['gift']) — {{ $familyChild['gift'] }} @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button wire:click="closeFamilyModal" class="w-full btn-secondary">
                    Fermer
                </button>
            </div>
        </div>
    </div>
@endif
