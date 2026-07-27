<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-xl p-4 sm:p-6">
        <!-- Header Section -->
        <div class="flex flex-col gap-2 border-b border-zinc-100 pb-6 dark:border-zinc-800">
            <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                Dashboard 12 Basic Safety dan LOTO
            </h1>
            <p class="text-zinc-500 dark:text-zinc-400">
                Selamat bekerja, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name }}</span>. Silakan pilih salah satu form checklist di bawah ini untuk memulai pencatatan.
            </p>
            @if(auth()->user()->is_admin)
            <div class="mt-4">
                <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 transition">
                    <flux:icon name="cog-8-tooth" class="w-4 h-4" />
                    Masuk ke Admin Panel
                </a>
            </div>
            @endif
        </div>

        <!-- Forms Grid (4 Columns, 3 Rows on Desktop) -->
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 md:grid-cols-4">
            @foreach($forms as $key => $form)
                @php
                    $hex = $form['hex_color'] ?? '#10b981';
                @endphp
                <div class="flex flex-col items-center justify-center text-center group">
                    <!-- Clickable Icon Container with Hover & Press Animations -->
                    <a href="{{ route('forms.show', $key) }}"
                       class="relative flex items-center justify-center w-24 h-24 rounded-3xl shadow-sm transition-all duration-300 ease-out hover:scale-110 hover:-translate-y-1 hover:shadow-lg active:scale-95 active:translate-y-0 active:hue-rotate-180 active:brightness-150"
                       style="background-color: color-mix(in srgb, {{ $hex }} 10%, transparent); border: 1.5px solid color-mix(in srgb, {{ $hex }} 33%, transparent);"
                       title="{{ $form['title'] }}"
                       wire:navigate>

                        <!-- Glow effect behind icon on hover -->
                        <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 group-active:opacity-100 transition-opacity duration-300 blur-md -z-10"
                             style="background-color: color-mix(in srgb, {{ $hex }} 27%, transparent);"></div>

                        <!-- Icon -->
                        <flux:icon :name="$form['icon'] ?? 'shield-check'" class="w-10 h-10" style="color: {{ $hex }};" />
                    </a>

                    <!-- Keterangan Tulisan Form -->
                    <div class="mt-4 flex flex-col gap-1 px-2">
                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100 group-hover:text-zinc-950 dark:group-hover:text-white transition-colors duration-200">
                            {{ $form['title'] }}
                        </span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-normal max-w-[200px]">
                            {{ $form['description'] ?? 'Form untuk keperluan ' . strtolower($form['title']) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts::app>
