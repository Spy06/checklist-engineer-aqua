<x-layouts::app :title="$currentDef['title']">
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-xl p-4 sm:p-6 max-w-4xl mx-auto">
        @php
            $hex = $currentDef['hex_color'] ?? '#10b981';
        @endphp

        <!-- Back to Dashboard and Title -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-100 pb-6 dark:border-zinc-800">
            <div class="flex items-center gap-4">
                @php
                    $backRoute = str_contains($path, '/') ? route('forms.show', dirname($path)) : route('dashboard');
                @endphp
                <a href="{{ $backRoute }}"
                   class="flex items-center justify-center w-10 h-10 rounded-full border border-zinc-200 bg-white text-zinc-600 shadow-sm transition-all hover:bg-zinc-50 hover:text-zinc-900 active:scale-95 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                   title="Kembali"
                   wire:navigate>
                    <flux:icon name="arrow-left" class="w-5 h-5" />
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                        {{ $currentDef['title'] }}
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Pilih tipe spesifik di bawah ini.
                    </p>
                </div>
            </div>

            <!-- Accent Icon Indicator -->
            <div class="hidden sm:flex items-center justify-center w-14 h-14 rounded-2xl"
                 style="background-color: color-mix(in srgb, {{ $hex }} 10%, transparent); border: 1.5px solid color-mix(in srgb, {{ $hex }} 33%, transparent);">
                <flux:icon :name="$currentDef['icon'] ?? 'chevron-double-down'" class="w-7 h-7" style="color: {{ $hex }};" />
            </div>
        </div>

        <!-- Children Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($currentDef['items'] as $slug => $childDef)
                @php
                    $childHex = (is_array($childDef) ? ($childDef['hex_color'] ?? null) : null) ?? $hex;
                    $itemName = is_array($childDef) ? ($childDef['title'] ?? $slug) : $childDef;
                    $childIcon = is_array($childDef) ? ($childDef['icon'] ?? 'check') : 'check';
                @endphp
                <a href="{{ route('forms.show', $path . '/' . $slug) }}"
                   class="group flex flex-col justify-between h-full bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all hover:shadow-md active:scale-95"
                   style="hover:border-color: {{ $childHex }};"
                   wire:navigate>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-xl"
                                 style="background-color: color-mix(in srgb, {{ $childHex }} 10%, transparent);">
                                <flux:icon :name="$childIcon" class="w-5 h-5" style="color: {{ $childHex }};" />
                            </div>
                            <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-zinc-950 dark:group-hover:text-white transition-colors">
                                {{ $itemName }}
                            </h3>
                        </div>
                        <flux:icon name="arrow-right" class="w-5 h-5 text-zinc-400 group-hover:translate-x-1 transition-transform" style="color: {{ $childHex }};" />
                    </div>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                        Masuk ke form inspeksi
                    </p>
                </a>
            @endforeach
        </div>

    </div>
</x-layouts::app>
