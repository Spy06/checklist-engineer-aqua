<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-gray-950 antialiased transition-colors duration-200">
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-gray-200 dark:border-gray-800
                   bg-white dark:bg-gray-900
                   shadow-sm">

            {{-- Brand / Logo --}}
            <flux:sidebar.header class="border-b border-gray-200 dark:border-gray-800 pb-4">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="flex items-center gap-3 px-1 group">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg
                                 bg-gradient-to-br from-blue-500 to-blue-600
                                 shadow-sm group-hover:from-blue-400 group-hover:to-blue-500
                                 transition-all duration-200">
                        <x-app-logo-icon class="size-5 text-white" />
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 tracking-wide">Sistem Checklist</span>
                        <span class="text-xs text-blue-500 dark:text-blue-400 font-medium tracking-widest uppercase">Aqua</span>
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden ml-auto" />
            </flux:sidebar.header>

            {{-- Navigation --}}
            <flux:sidebar.nav class="mt-2">
                <flux:sidebar.group heading="Menu" class="grid">
                    {{-- Dashboard --}}
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                        class="rounded-md transition-all duration-200
                               text-gray-600 dark:text-gray-400
                               hover:text-gray-900 dark:hover:text-gray-100
                               hover:bg-gray-50 dark:hover:bg-gray-800
                               data-[current]:bg-blue-50 dark:data-[current]:bg-blue-900/30
                               data-[current]:text-blue-700 dark:data-[current]:text-blue-300
                               data-[current]:border-l-2 data-[current]:border-blue-500
                               data-[current]:shadow-sm">
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    {{-- Matriks Checklist --}}
                    <flux:sidebar.item
                        icon="table-cells"
                        :href="route('matrix')"
                        :current="request()->routeIs('matrix')"
                        wire:navigate
                        class="rounded-md transition-all duration-200
                               text-gray-600 dark:text-gray-400
                               hover:text-gray-900 dark:hover:text-gray-100
                               hover:bg-gray-50 dark:hover:bg-gray-800
                               data-[current]:bg-blue-50 dark:data-[current]:bg-blue-900/30
                               data-[current]:text-blue-700 dark:data-[current]:text-blue-300
                               data-[current]:border-l-2 data-[current]:border-blue-500
                               data-[current]:shadow-sm">
                        Matriks Checklist
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- User Profile & Logout at bottom --}}
            <div class="border-t border-gray-200 dark:border-gray-800 p-4 flex flex-col gap-4 mt-auto">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-sm font-bold text-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                    <div class="flex flex-col overflow-hidden">
                        <span class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ auth()->user()->name }}
                        </span>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 active:scale-95 transition-all duration-200 rounded-lg shadow-sm hover:shadow">
                        <flux:icon.arrow-right-start-on-rectangle class="w-4 h-4" />
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </flux:sidebar>

        {{-- Mobile Header --}}
        <flux:header class="lg:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
            <flux:sidebar.toggle class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" icon="bars-2" inset="left" />
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 mx-auto">Sistem Checklist Aqua</span>
            
            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                @csrf
                <button type="submit" class="flex items-center justify-center p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 active:scale-95 transition-all duration-200 rounded-lg">
                    <flux:icon.arrow-right-start-on-rectangle class="w-5 h-5" />
                </button>
            </form>
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
