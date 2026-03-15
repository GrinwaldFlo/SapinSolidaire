<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Réception des cadeaux</h1>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        {{-- Filter input: desktop uses native number field, mobile uses custom keypad --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Numéro de famille</label>

            {{-- Desktop: simple numeric input --}}
            <div class="hidden md:flex items-center gap-3">
                <input
                    type="number"
                    inputmode="numeric"
                    wire:model.live.debounce.300ms="familyNumber"
                    placeholder="XXXX"
                    min="0"
                    class="w-64 rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:ring-green-500 focus:border-green-500 text-lg font-mono [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                />
                @if($familyNumber !== '')
                    <button wire:click="clearFilter" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Effacer
                    </button>
                @endif
            </div>

            {{-- Mobile: display + numeric keypad --}}
            <div class="md:hidden">
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 rounded-lg border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 px-4 py-3 text-2xl font-mono text-gray-900 dark:text-white min-h-[3rem] flex items-center">
                        {{ $familyNumber ?: '' }}
                        <span class="animate-pulse text-green-500 ml-0.5">|</span>
                    </div>
                    @if($familyNumber !== '')
                        <button wire:click="clearFilter" class="px-3 py-3 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-300 dark:border-zinc-600 rounded-lg">
                            Effacer
                        </button>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(range(1, 9) as $digit)
                        <button wire:click="appendDigit('{{ $digit }}')" class="py-3 text-xl font-semibold rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-zinc-600 active:bg-gray-300 dark:active:bg-zinc-500 transition">
                            {{ $digit }}
                        </button>
                    @endforeach
                    <button wire:click="backspace" class="py-3 text-xl font-semibold rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 active:bg-red-300 transition">
                        ⌫
                    </button>
                    <button wire:click="appendDigit('0')" class="py-3 text-xl font-semibold rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-zinc-600 active:bg-gray-300 dark:active:bg-zinc-500 transition">
                        0
                    </button>
                </div>
            </div>
        </div>

        {{-- Results --}}
        @if($familyNumber !== '')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Children list --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Famille n°{{ $familyNumber }}
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">— {{ count($children) }} enfant(s)</span>
                        </h2>
                    </div>

                    @if(count($children) === 0)
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun cadeau imprimé pour cette famille.
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($children as $child)
                                <li wire:click="selectChild('{{ $child->id }}')" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer {{ $selectedChild?->id === $child->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $child->first_name }}</span>
                                            <span class="ml-2 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $child->code }}</span>
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $child->gift }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Desktop detail panel --}}
                <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Détails du cadeau</h2>
                    </div>

                    @if($selectedChild)
                        @include('livewire.admin.partials.gift-reception-detail')
                    @else
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            Sélectionnez un enfant dans la liste pour voir les détails.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mobile detail modal --}}
            @if($selectedChild && $showMobileDetail)
                <div class="lg:hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center" wire:click.self="closeMobileDetail">
                    <div class="fixed inset-0 bg-black/50" wire:click="closeMobileDetail"></div>
                    <div class="relative bg-white dark:bg-zinc-800 w-full sm:max-w-md sm:rounded-lg rounded-t-2xl shadow-xl max-h-[90vh] overflow-y-auto">
                        <div class="p-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                            <h2 class="font-semibold text-gray-900 dark:text-white">Détails du cadeau</h2>
                            <button wire:click="closeMobileDetail" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @include('livewire.admin.partials.gift-reception-detail')
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-8 text-center text-gray-500 dark:text-gray-400">
                Entrez un numéro de famille pour rechercher les cadeaux.
            </div>
        @endif
    @endif
</div>
