<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Bienvenue sur Sapin Solidaire</h1>
            
            @can('access-admin')
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Accédez à l'interface d'administration pour gérer les demandes de cadeaux.
                </p>
                <a href="{{ route('admin.dashboard') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Accéder à l'administration
                </a>
            @else
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 max-w-lg mx-auto">
                    <div class="flex justify-center mb-4">
                        <svg class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">
                        Votre compte a bien été créé !
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Veuillez contacter un administrateur pour qu'il vous attribue les droits d'accès nécessaires.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500">
                        Une fois vos droits attribués, vous pourrez accéder aux fonctionnalités de l'application.
                    </p>
                </div>
            @endcan
        </div>
    </div>
</x-layouts::app>
