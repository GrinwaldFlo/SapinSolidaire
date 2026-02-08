<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-2" wire:navigate>
                    <span class="text-2xl">🎄</span>
                    <span class="font-semibold text-zinc-900 dark:text-white">Sapin Solidaire</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @can('access-admin')
                <flux:sidebar.group heading="Gestion" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        Tableau de bord
                    </flux:sidebar.item>

                    @can('validate')
                    <flux:sidebar.item icon="check-circle" :href="route('admin.validation')" :current="request()->routeIs('admin.validation')" wire:navigate>
                        Validation
                    </flux:sidebar.item>
                    @endcan

                    @can('organize')
                    <flux:sidebar.item icon="tag" :href="route('admin.labels')" :current="request()->routeIs('admin.labels')" wire:navigate>
                        Étiquettes
                    </flux:sidebar.item>
                    @endcan

                    @can('reception')
                    <flux:sidebar.item icon="inbox" :href="route('admin.reception')" :current="request()->routeIs('admin.reception')" wire:navigate>
                        Réception
                    </flux:sidebar.item>
                    @endcan

                    @can('organize')
                    <flux:sidebar.item icon="envelope" :href="route('admin.confirmations')" :current="request()->routeIs('admin.confirmations')" wire:navigate>
                        Confirmations
                    </flux:sidebar.item>
                    @endcan

                    @can('reception')
                    <flux:sidebar.item icon="gift" :href="route('admin.delivery')" :current="request()->routeIs('admin.delivery')" wire:navigate>
                        Remise
                    </flux:sidebar.item>
                    @endcan

                    @can('organize')
                    <flux:sidebar.item icon="users" :href="route('admin.monitoring')" :current="request()->routeIs('admin.monitoring')" wire:navigate>
                        Suivi enfants
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="home" :href="route('admin.families')" :current="request()->routeIs('admin.families')" wire:navigate>
                        Familles
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcan

                @can('admin')
                <flux:sidebar.group heading="Administration" class="grid">
                    <flux:sidebar.item icon="calendar" :href="route('admin.seasons')" :current="request()->routeIs('admin.seasons')" wire:navigate>
                        Saisons
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                        Utilisateurs
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="cog" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
                        Paramètres
                    </flux:sidebar.item>

                    @if(!app()->isProduction())
                    <flux:sidebar.item icon="wrench" :href="route('admin.dev-tools')" :current="request()->routeIs('admin.dev-tools')" wire:navigate>
                        Dev Tools
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Paramètres
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Déconnexion
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
