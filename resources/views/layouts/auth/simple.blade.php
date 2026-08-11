<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100">

        {{-- Background decorative blobs --}}
        <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute -top-40 -right-32 w-96 h-96 rounded-full
                        bg-cyan-600/10 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-32 w-96 h-96 rounded-full
                        bg-cyan-500/10 blur-3xl"></div>
        </div>

        <div class="relative bg-zinc-50 dark:bg-zinc-950 flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2 animate-fade-in-up">

                {{-- Logo & Brand --}}
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 mb-2" wire:navigate>
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl
                                 bg-cyan-600 shadow-lg shadow-cyan-900/20
                                 ring-1 ring-cyan-500/40
                                 transition-transform duration-200 hover:scale-105">
                        <x-app-logo-icon class="size-8 text-white" />
                    </span>
                    <div class="text-center">
                        <div class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">Sistem Checklist</div>
                        <div class="text-xs text-cyan-700 dark:text-cyan-400 font-semibold tracking-widest uppercase">Aqua</div>
                    </div>
                </a>

                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>

                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400 mt-4">
                    &copy; {{ date('Y') }} Sistem Checklist Aqua. All rights reserved.
                </p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

