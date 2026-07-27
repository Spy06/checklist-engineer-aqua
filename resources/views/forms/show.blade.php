<x-layouts::app :title="$form['title']">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-4 sm:p-6 max-w-4xl mx-auto">
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
                        {{ $form['title'] }}
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ $form['description'] }}
                    </p>
                </div>
            </div>

            <!-- Accent Icon Indicator -->
            <div class="hidden sm:flex items-center justify-center w-14 h-14 rounded-2xl border {{ $form['border_color'] }} {{ $form['bg_color'] }}">
                <flux:icon :name="$form['icon']" class="w-7 h-7 {{ $form['text_color'] }}" />
            </div>
        </div>

        <!-- Success Alert -->
        @if(session('success'))
            <flux:callout variant="success" icon="check" class="shadow-sm">
                {{ session('success') }}
            </flux:callout>
        @endif

        <!-- Main Checklist Form -->
        <form method="POST" action="{{ route('forms.store', $path) }}" class="flex flex-col gap-6">
            @csrf

            <!-- Date Picker (Must be placed at the very top) -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col gap-2">
                <label for="date" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200">
                    Tanggal Inspeksi <span class="text-red-500">*</span>
                </label>
                <div class="relative max-w-xs">
                    <input type="date" 
                           id="date" 
                           name="date" 
                           required 
                           value="{{ old('date', date('Y-m-d')) }}" 
                           class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:focus:border-zinc-700 dark:focus:ring-zinc-700" />
                </div>
                @error('date')
                    <span class="text-xs text-red-500 font-semibold">{{ $message }}</span>
                @enderror
            </div>

            <!-- Checklist Items Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="border-b border-zinc-100 dark:border-zinc-800 px-6 py-4 bg-zinc-50/50 dark:bg-zinc-950/20">
                    <h2 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider">
                        Daftar Checklist
                    </h2>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80 px-6 py-2">
                    @foreach($form['items'] as $index => $item)
                        <label class="flex items-start gap-4 py-4 cursor-pointer group transition-colors duration-150">
                            <!-- Hidden original checkbox, custom styled one -->
                            <div class="flex items-center h-5">
                                <input type="checkbox" 
                                       name="answers[{{ $index }}]" 
                                       value="1" 
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 h-4 w-4 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-600 dark:focus:ring-zinc-500" 
                                       {{ old("answers.{$index}") ? 'checked' : '' }} />
                            </div>
                            <div class="flex-1 text-sm font-medium text-zinc-700 group-hover:text-zinc-950 dark:text-zinc-300 dark:group-hover:text-white">
                                {{ $item }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Notes/Catatan -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col gap-2">
                <label for="notes" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200">
                    Catatan Tambahan (Opsional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="3" 
                          placeholder="Tuliskan temuan atau catatan penting di sini..."
                          class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:focus:border-zinc-700 dark:focus:ring-zinc-700">{{ old('notes') }}</textarea>
                @error('notes')
                    <span class="text-xs text-red-500 font-semibold">{{ $message }}</span>
                @enderror
            </div>

            <!-- Action Button -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="px-6 py-3 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-semibold text-sm shadow-sm transition-all hover:shadow hover:-translate-y-0.5 active:scale-95 active:translate-y-0 cursor-pointer dark:bg-white dark:hover:bg-zinc-100 dark:text-zinc-900">
                    Simpan & Kirim Checklist
                </button>
            </div>
        </form>

        <!-- History/Riwayat Submission -->
        <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-800">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">
                Riwayat Pengisian
            </h2>

            @if($submissions->isEmpty())
                <div class="text-center p-8 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
                    <flux:icon name="clock" class="w-8 h-8 text-zinc-400 mx-auto mb-3" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Belum ada riwayat pengisian untuk form ini.
                    </p>
                </div>
            @else
                <div class="flex flex-col gap-4">
                    @foreach($submissions as $sub)
                        <div class="bg-zinc-50 dark:bg-zinc-900/50 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-200/50 pb-3 mb-3 dark:border-zinc-800">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="calendar" class="w-4 h-4 text-zinc-400" />
                                    <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ \Carbon\Carbon::parse($sub->date)->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Dilaporkan oleh {{ $sub->user->name }} • {{ $sub->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <!-- List Checklist Status -->
                            <div class="flex flex-col gap-2">
                                <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                                    Hasil Pengecekan:
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                                    @foreach($form['items'] as $index => $item)
                                        @php
                                            $isChecked = (isset($sub->answers[$index]) && $sub->answers[$index]) || (isset($sub->answers[$item]) && $sub->answers[$item]);
                                        @endphp
                                        <div class="flex items-center gap-2 text-xs">
                                            @if($isChecked)
                                                <div class="flex items-center justify-center w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                                    <flux:icon name="check" class="w-3 h-3" />
                                                </div>
                                                <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $item }}</span>
                                            @else
                                                <div class="flex items-center justify-center w-4 h-4 rounded-full bg-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500">
                                                    <flux:icon name="minus" class="w-3 h-3" />
                                                </div>
                                                <span class="text-zinc-400 dark:text-zinc-600 line-through">{{ $item }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($sub->notes)
                                <div class="mt-4 pt-3 border-t border-dashed border-zinc-200/60 dark:border-zinc-800/80">
                                    <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                                        Catatan:
                                    </div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1 italic">
                                        "{{ $sub->notes }}"
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
