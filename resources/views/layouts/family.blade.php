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
        <header class="bg-header text-white py-4 px-6 shadow-lg">
            <div class="max-w-4xl mx-auto flex items-center justify-center">
                <a href="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="bg-white rounded-lg p-2">
                        <img src="{{ \App\Models\Setting::getLogoPath() }}" alt="Logo" class="h-12 w-auto">
                    </div>
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
        <footer class="bg-footer text-white py-4 px-6 text-center text-sm">
            <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::getSiteName() }} · En colaboration avec <a href="https://www.eerv.ch" target="_blank" class="underline hover:text-green-200">l'EERV</a> · Sources: <a href="https://github.com/GrinwaldFlo/SapinSolidaire" target="_blank" rel="noopener noreferrer" class="underline hover:text-green-200">GitHub</a></p>
        </footer>
    </div>

    @fluxScripts
</body>
</html>
