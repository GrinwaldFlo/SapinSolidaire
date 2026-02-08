<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-green-50 to-white dark:from-zinc-900 dark:to-zinc-800">
<div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-green-700 text-white py-4 px-6 shadow-lg">
            <div class="max-w-4xl mx-auto flex items-center justify-center">
                <a href="/" class="flex items-center gap-3">
                    <span class="text-3xl">🎄</span>
                    <h1 class="text-2xl font-bold">{{ \App\Models\Setting::getSiteName() }}</h1>
                </a>
            </div>
            <x-environment-banner />
        </header>

        <!-- Main Content -->
        <main class="flex-1 py-8 px-4">
            <div class="max-w-2xl mx-auto">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-green-800 text-white py-4 px-6 text-center text-sm">
            <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::getSiteName() }}</p>
        </footer>
    </div>

    @fluxScripts
</body>
</html>
