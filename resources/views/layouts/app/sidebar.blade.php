<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-950 antialiased transition-colors duration-200">
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-zinc-200 dark:border-zinc-800/70
                   bg-white dark:bg-zinc-900
                   shadow-sm dark:shadow-xl dark:shadow-black/20">

            {{-- Brand / Logo --}}
            <flux:sidebar.header class="border-b border-zinc-200 dark:border-zinc-800/70 pb-4">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="flex items-center gap-3 px-1 group">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl
                                 bg-cyan-600 shadow-md shadow-cyan-900/30
                                 group-hover:bg-cyan-500 transition-colors duration-200">
                        <x-app-logo-icon class="size-5 text-white" />
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-white tracking-wide">Sistem Checklist</span>
                        <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-medium tracking-widest uppercase">Aqua</span>
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden ml-auto" />
            </flux:sidebar.header>

            {{-- Navigation --}}
            <flux:sidebar.nav class="mt-2">
                <flux:sidebar.group heading="Menu" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                        class="rounded-lg transition-all duration-150
                               text-zinc-500 dark:text-zinc-400
                               hover:text-zinc-900 dark:hover:text-white
                               hover:bg-zinc-100 dark:hover:bg-zinc-800
                               data-[current]:bg-cyan-50 dark:data-[current]:bg-cyan-600/20
                               data-[current]:text-cyan-700 dark:data-[current]:text-cyan-400
                               data-[current]:border data-[current]:border-cyan-200 dark:data-[current]:border-cyan-500/30">
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="table-cells"
                        :href="route('matrix')"
                        :current="request()->routeIs('matrix')"
                        wire:navigate
                        class="rounded-lg transition-all duration-150
                               text-zinc-500 dark:text-zinc-400
                               hover:text-zinc-900 dark:hover:text-white
                               hover:bg-zinc-100 dark:hover:bg-zinc-800
                               data-[current]:bg-cyan-50 dark:data-[current]:bg-cyan-600/20
                               data-[current]:text-cyan-700 dark:data-[current]:text-cyan-400
                               data-[current]:border data-[current]:border-cyan-200 dark:data-[current]:border-cyan-500/30">
                        Matriks Checklist
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- User menu at bottom --}}
            <div class="border-t border-zinc-200 dark:border-zinc-800/70 pt-3">
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            </div>
        </flux:sidebar>

        {{-- Mobile Header --}}
        <flux:header class="lg:hidden bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800/70">
            <flux:sidebar.toggle class="lg:hidden text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" icon="bars-2" inset="left" />
            <span class="text-sm font-semibold text-zinc-800 dark:text-white mx-auto">Sistem Checklist Aqua</span>

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
                                    <flux:text class="truncate text-zinc-500">{{ auth()->user()->username }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer text-red-500 hover:text-red-600"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
