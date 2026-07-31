<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-zinc-950">
        {{-- Background decorative blobs --}}
        <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute -top-40 -right-32 w-96 h-96 rounded-full bg-cyan-600/10 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-32 w-96 h-96 rounded-full bg-cyan-500/8 blur-3xl"></div>
        </div>

        <div class="relative bg-background flex min-h-svh flex-col items-center p-6 md:p-10 pt-10">
            <div class="flex w-full max-w-7xl flex-col gap-6 animate-fade-in-up">
                
                <div class="flex items-center justify-between">
                    {{-- Logo & Brand --}}
                    <a href="{{ route('home') ?? url('/') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-600 shadow-lg shadow-cyan-900/40 ring-1 ring-cyan-500/40 transition-transform duration-200 hover:scale-105">
                            <x-app-logo-icon class="size-6 text-white" />
                        </span>
                        <div>
                            <div class="text-lg font-bold text-white tracking-tight">Sistem Checklist</div>
                            <div class="text-xs text-cyan-400 font-semibold tracking-widest uppercase">Aqua</div>
                        </div>
                    </a>
                    
                    <div>
                        <flux:button href="{{ route('login') }}" variant="primary" wire:navigate>
                            Login
                        </flux:button>
                    </div>
                </div>

                <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 shadow-xl">
                    <h2 class="text-xl font-bold text-white mb-6">Matriks Checklist Bulanan (Tahun {{ $currentYear }})</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left divide-y divide-white/5" style="table-layout: fixed; min-width: 800px;">
                            <thead>
                                <tr>
                                    <th class="py-3 font-semibold text-sm text-white bg-white/5" style="width: 250px; padding-left: 1rem; padding-right: 1rem;">Form Checklist</th>
                                    @foreach($months as $monthName)
                                        <th class="py-3 font-semibold text-sm text-center text-white bg-white/5" style="padding-left: 0.5rem; padding-right: 0.5rem; text-align: center;">
                                            {{ substr($monthName, 0, 3) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($matrix as $row)
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="py-3 text-sm text-gray-300 font-medium" style="padding-left: 1rem; padding-right: 1rem; white-space: normal;">
                                            {{ $row['title'] }}
                                        </td>
                                        @foreach($row['status'] as $isCompleted)
                                            <td class="py-3 text-center" style="padding-left: 0.5rem; padding-right: 0.5rem; text-align: center;">
                                                @if($isCompleted)
                                                    <div class="inline-flex items-center justify-center text-emerald-400 bg-emerald-400/10 rounded-full p-1" title="Sudah diisi">
                                                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div class="inline-flex items-center justify-center text-red-400 bg-red-400/10 rounded-full p-1" title="Belum diisi">
                                                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="text-center text-xs text-zinc-600 mt-4">
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
