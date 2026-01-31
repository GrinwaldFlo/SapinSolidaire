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
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Votre compte est en attente d'attribution de rôle par un administrateur.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    Contactez un administrateur pour obtenir les permissions nécessaires.
                </p>
            @endcan
        </div>
    </div>
</x-layouts::app>
