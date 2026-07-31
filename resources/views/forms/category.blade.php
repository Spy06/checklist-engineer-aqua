<x-layouts::app :title="$currentDef['title']">
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-2xl p-6 sm:p-10 max-w-5xl mx-auto
                bg-white dark:bg-zinc-900/60
                shadow-sm dark:shadow-xl ring-1 ring-zinc-200 dark:ring-white/5">
        @php
            $hex = $currentDef['hex_color'] ?? '#10b981';
        @endphp

        {{-- Back to Dashboard and Title --}}
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between
                    border-b border-zinc-200 dark:border-zinc-800 pb-8 animate-fade-in-up">
            <div class="flex items-center gap-5">
                @php
                    $backRoute = str_contains($path, '/') ? route('forms.show', dirname($path)) : route('dashboard');
                @endphp
                <a href="{{ $backRoute }}"
                   class="group flex items-center justify-center w-10 h-10 rounded-xl
                          border border-zinc-200 dark:border-zinc-700
                          bg-white dark:bg-zinc-800
                          text-zinc-500 dark:text-zinc-400
                          shadow-sm transition-all duration-200
                          hover:bg-zinc-50 dark:hover:bg-zinc-700
                          hover:text-zinc-900 dark:hover:text-white
                          hover:border-zinc-300 dark:hover:border-zinc-600
                          hover:shadow-md active:scale-95"
                   title="Kembali"
                   wire:navigate>
                    <flux:icon name="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" />
                </a>
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 mb-2 rounded-full
                                text-xs font-semibold tracking-widest uppercase w-fit
                                border"
                         style="background-color: color-mix(in srgb, {{ $hex }} 10%, transparent);
                                border-color: color-mix(in srgb, {{ $hex }} 30%, transparent);
                                color: {{ $hex }};">
                        <flux:icon name="folder-open" class="w-3 h-3" />
                        <span>Kategori</span>
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                        {{ $currentDef['title'] }}
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1.5 max-w-xl">
                        Pilih tipe spesifik di bawah ini untuk mulai mengisi checklist.
                    </p>
                </div>
            </div>

            {{-- Accent Icon --}}
            <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-2xl shadow-md shrink-0"
                 style="background-color: color-mix(in srgb, {{ $hex }} 12%, transparent);
                        border: 1px solid color-mix(in srgb, {{ $hex }} 30%, transparent);">
                <flux:icon :name="$currentDef['icon'] ?? 'chevron-double-down'" class="w-8 h-8" style="color: {{ $hex }};" />
            </div>
        </div>

        {{-- Children Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger-children">
            @foreach($currentDef['items'] as $slug => $childDef)
                @php
                    $childHex = (is_array($childDef) ? ($childDef['hex_color'] ?? null) : null) ?? $hex;
                    $itemName = is_array($childDef) ? ($childDef['title'] ?? $slug) : $childDef;
                    $childIcon = is_array($childDef) ? ($childDef['icon'] ?? 'check') : 'check';
                @endphp
                <a href="{{ route('forms.show', $path . '/' . $slug) }}"
                   class="group relative flex flex-col justify-between h-full
                          bg-zinc-50 dark:bg-zinc-800/60
                          border border-zinc-200 dark:border-zinc-700/60
                          rounded-2xl p-5
                          transition-all duration-300
                          hover:-translate-y-1 hover:shadow-xl hover:shadow-black/10 dark:hover:shadow-black/40
                          hover:border-zinc-300 dark:hover:border-zinc-600/80 overflow-hidden"
                   style="border-bottom: 2px solid {{ $childHex }};"
                   wire:navigate>

                    {{-- Hover glow --}}
                    <div class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100 rounded-2xl"
                         style="background: radial-gradient(circle at top right, color-mix(in srgb, {{ $childHex }} 10%, transparent), transparent 70%);"></div>

                    <div class="relative flex items-start justify-between mb-6">
                        <div class="flex items-center justify-center w-11 h-11 rounded-xl shadow-sm
                                    transition-transform duration-300 group-hover:scale-110 group-hover:rotate-2"
                             style="background-color: color-mix(in srgb, {{ $childHex }} 15%, transparent);
                                    border: 1px solid color-mix(in srgb, {{ $childHex }} 40%, transparent);">
                            <flux:icon :name="$childIcon" class="w-5 h-5" style="color: {{ $childHex }};" />
                        </div>
                        <div class="flex h-7 w-7 items-center justify-center rounded-full
                                    bg-white dark:bg-zinc-700 shadow-sm
                                    opacity-0 -translate-x-2
                                    transition-all duration-300
                                    group-hover:opacity-100 group-hover:translate-x-0">
                            <flux:icon name="arrow-right" class="w-3.5 h-3.5 text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-white transition-colors" />
                        </div>
                    </div>

                    <div class="relative flex flex-col gap-1.5">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white leading-snug
                                   group-hover:text-zinc-900 dark:group-hover:text-white transition-colors line-clamp-2">
                            {{ $itemName }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 leading-relaxed">
                            Masuk ke form inspeksi
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

    </div>
</x-layouts::app>
