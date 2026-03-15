<div>
    @if(!$emailSent)
        {{-- Snowfall overlay --}}
        <div class="snowfall pointer-events-none fixed inset-0 z-50 overflow-hidden" aria-hidden="true">
            @for ($i = 0; $i < 40; $i++)
                <div class="snowflake"
                     style="
                        left: {{ rand(0, 100) }}%;
                        animation-delay: {{ rand(0, 80) / 10 }}s;
                        animation-duration: {{ rand(100, 240) / 10 }}s;
                        opacity: {{ rand(4, 10) / 10 }};
                        font-size: {{ rand(10, 24) }}px;
                     "
                >&#10052;</div>
            @endfor
        </div>

        <style>
            .snowflake {
                position: fixed;
                top: -30px;
                color: white;
                text-shadow: 0 0 3px rgba(255,255,255,0.7);
                animation-name: snowfall, snowsway;
                animation-timing-function: linear, ease-in-out;
                animation-iteration-count: infinite, infinite;
            }

            @keyframes snowfall {
                0%   { top: -5%; }
                100% { top: 105%; }
            }

            @keyframes snowsway {
                0%, 100% { transform: translateX(0); }
                25%      { transform: translateX(15px); }
                75%      { transform: translateX(-15px); }
            }
        </style>
    @endif

    @if($seasonStatus === 'active')
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8">
            @if(!$emailSent)
                <div class="text-center mb-8">
                    <span class="text-6xl mb-4 block">🎁</span>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Demande de cadeau</h2>
                    
                    @if($introductionText)
                        <div class="text-gray-600 dark:text-gray-300 mb-6 whitespace-pre-line">
                            {{ $introductionText }}
                        </div>
                    @endif
                </div>

                <form wire:submit="sendLink" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Votre adresse e-mail
                        </label>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white"
                            placeholder="exemple@email.com"
                            required
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                    >
                        <span wire:loading.remove>Recevoir le lien d'accès</span>
                        <span wire:loading>Envoi en cours...</span>
                    </button>
                </form>
            @else
                <div class="text-center">
                    <span class="text-6xl mb-4 block">✉️</span>
                    <h2 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-4">E-mail envoyé !</h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Un lien d'accès a été envoyé à <strong>{{ $email }}</strong>.
                    </p>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Vérifiez votre boîte de réception (et vos spams) et cliquez sur le lien pour continuer votre demande.
                    </p>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">
                        Le lien est valable pendant 48 heures.
                    </p>
                </div>
            @endif
        </div>
    @elseif($seasonStatus === 'future')
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8 text-center">
            <span class="text-6xl mb-4 block">📅</span>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Bientôt disponible</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ $statusMessage }}
            </p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8 text-center">
            <span class="text-6xl mb-4 block">🎄</span>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">À bientôt !</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ $statusMessage }}
            </p>
        </div>
    @endif
</div>
