<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-10 rounded-2xl p-6 sm:p-10
                bg-white dark:bg-zinc-900/60
                shadow-sm dark:shadow-xl ring-1 ring-zinc-200 dark:ring-white/5">

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6
                    border-b border-zinc-200 dark:border-zinc-800 pb-8 animate-fade-in-up">
            <div class="flex flex-col gap-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                            bg-cyan-50 dark:bg-cyan-500/10
                            text-cyan-700 dark:text-cyan-400
                            text-xs font-semibold tracking-widest uppercase w-fit
                            border border-cyan-200 dark:border-cyan-500/20">
                    <flux:icon name="sparkles" class="w-3.5 h-3.5" />
                    <span>Workspace</span>
                </div>
                <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white sm:text-5xl">
                    Dashboard
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-cyan-700 dark:from-cyan-400 dark:to-cyan-600">
                        12 Basic Safety
                    </span>
                </h1>
                <p class="text-base text-zinc-500 dark:text-zinc-400 max-w-xl leading-relaxed">
                    Selamat datang kembali,
                    <span class="font-bold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>.
                    Pilih form di bawah ini untuk memulai pengisian checklist harian Anda.
                </p>
            </div>

            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
            <div class="shrink-0">
                <a href="{{ url('/admin') }}"
                   class="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl
                          bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white
                          shadow-md shadow-cyan-900/20 ring-1 ring-cyan-500/50
                          transition-all duration-200 hover:bg-cyan-500 hover:shadow-lg
                          active:scale-95">
                    <div class="absolute inset-0 flex h-full w-full justify-center
                                [transform:skew(-12deg)_translateX(-100%)]
                                group-hover:duration-700 group-hover:[transform:skew(-12deg)_translateX(100%)]">
                        <div class="relative h-full w-8 bg-white/10"></div>
                    </div>
                    <flux:icon name="command-line" class="w-4 h-4 relative z-10" />
                    <span class="relative z-10">Admin Panel</span>
                </a>
            </div>
            @endif
        </div>

        {{-- Forms Grid --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 stagger-children">
            @foreach($forms as $key => $form)
                @php
                    $hex = $form['hex_color'] ?? '#10b981';
                @endphp

                <a href="{{ route('forms.show', $key) }}"
                   wire:navigate
                   class="group relative flex flex-col gap-4 overflow-hidden rounded-2xl p-5
                          bg-zinc-50 dark:bg-zinc-800/60
                          border border-zinc-200 dark:border-zinc-700/60
                          transition-all duration-300
                          hover:-translate-y-1 hover:shadow-xl hover:shadow-black/10 dark:hover:shadow-black/40
                          hover:border-zinc-300 dark:hover:border-zinc-600/80"
                   style="border-bottom: 2px solid color-mix(in srgb, {{ $hex }} 60%, transparent);">

                    {{-- Hover glow --}}
                    <div class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100 rounded-2xl"
                         style="background: radial-gradient(circle at top right, color-mix(in srgb, {{ $hex }} 10%, transparent), transparent 65%);"></div>

                    {{-- Top row: icon + arrow --}}
                    <div class="relative flex items-start justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl shadow-sm
                                    transition-transform duration-300 group-hover:scale-110 group-hover:rotate-2"
                             style="background-color: color-mix(in srgb, {{ $hex }} 15%, transparent);
                                    border: 1px solid color-mix(in srgb, {{ $hex }} 35%, transparent);">
                            <flux:icon :name="$form['icon'] ?? 'shield-check'" class="w-6 h-6" style="color: {{ $hex }};" />
                        </div>
                        <div class="flex h-7 w-7 items-center justify-center rounded-full
                                    bg-white dark:bg-zinc-700 shadow-sm
                                    opacity-0 -translate-x-2
                                    transition-all duration-300
                                    group-hover:opacity-100 group-hover:translate-x-0">
                            <flux:icon name="arrow-up-right" class="w-3.5 h-3.5 text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-white transition-colors" />
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="relative flex flex-col gap-1.5">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white leading-snug
                                   group-hover:text-zinc-900 dark:group-hover:text-white transition-colors line-clamp-2">
                            {{ $form['title'] }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 line-clamp-2 leading-relaxed">
                            {{ $form['description'] ?? 'Form checklist standar untuk keperluan ' . strtolower($form['title']) }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
