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
        </div>

        <!-- Forms Grid (4 Columns, 3 Rows on Desktop) -->
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 md:grid-cols-4">
            @foreach($forms as $key => $form)
                <div class="flex flex-col items-center justify-center text-center group">
                    <!-- Clickable Icon Container with Hover & Press Animations -->
                    <a href="{{ route('forms.show', $key) }}" 
                       class="relative flex items-center justify-center w-24 h-24 rounded-3xl border {{ $form['border_color'] }} {{ $form['bg_color'] }} shadow-sm transition-all duration-300 ease-out hover:scale-110 hover:-translate-y-1 hover:shadow-lg active:scale-95 active:translate-y-0"
                       title="{{ $form['title'] }}"
                       wire:navigate>
                        
                        <!-- Glow effect behind icon on hover -->
                        <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 blur-md -z-10 {{ $form['color'] === 'blue' ? 'bg-blue-400/20' : '' }} {{ $form['color'] === 'emerald' ? 'bg-emerald-400/20' : '' }} {{ $form['color'] === 'amber' ? 'bg-amber-400/20' : '' }} {{ $form['color'] === 'red' ? 'bg-red-400/20' : '' }} {{ $form['color'] === 'cyan' ? 'bg-cyan-400/20' : '' }} {{ $form['color'] === 'orange' ? 'bg-orange-400/20' : '' }} {{ $form['color'] === 'indigo' ? 'bg-indigo-400/20' : '' }} {{ $form['color'] === 'yellow' ? 'bg-yellow-400/20' : '' }} {{ $form['color'] === 'violet' ? 'bg-violet-400/20' : '' }} {{ $form['color'] === 'pink' ? 'bg-pink-400/20' : '' }} {{ $form['color'] === 'lime' ? 'bg-lime-400/20' : '' }} {{ $form['color'] === 'fuchsia' ? 'bg-fuchsia-400/20' : '' }}"></div>
                        
                        <!-- Icon -->
                        <flux:icon :name="$form['icon']" class="w-10 h-10 {{ $form['text_color'] }}" />
                    </a>

                    <!-- Keterangan Tulisan Form -->
                    <div class="mt-4 flex flex-col gap-1 px-2">
                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100 group-hover:text-zinc-950 dark:group-hover:text-white transition-colors duration-200">
                            {{ $form['title'] }}
                        </span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-normal max-w-[200px]">
                            Form untuk keperluan {{ strtolower($form['title']) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts::app>
