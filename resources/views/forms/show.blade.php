<x-layouts::app :title="$form['title']">
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-2xl p-6 sm:p-10 max-w-5xl mx-auto
               bg-white dark:bg-zinc-900/60
               shadow-sm dark:shadow-xl ring-1 ring-zinc-200 dark:ring-white/5">
        @php
            $hex = $form['hex_color'] ?? '#10b981';
        @endphp
        <!-- Back to Dashboard and Title -->
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 dark:border-zinc-800 pb-8 animate-fade-in-up">
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
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 mb-2 rounded-full text-xs font-semibold tracking-widest uppercase w-fit border"
                         style="background-color: color-mix(in srgb, {{ $hex }} 10%, transparent); border-color: color-mix(in srgb, {{ $hex }} 30%, transparent); color: {{ $hex }};">
                        <flux:icon name="clipboard-document-check" class="w-3 h-3" />
                        <span>Checklist</span>
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                        {{ $form['title'] }}
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1.5 max-w-xl">
                        {{ $form['description'] }}
                    </p>
                </div>
            </div>

            <!-- Accent Icon Indicator -->
            <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-2xl shadow-md shrink-0"
                 style="background-color: color-mix(in srgb, {{ $hex }} 12%, transparent);
                        border: 1px solid color-mix(in srgb, {{ $hex }} 30%, transparent);
                        box-shadow: 0 0 20px color-mix(in srgb, {{ $hex }} 15%, transparent);">
                <flux:icon :name="$form['icon'] ?? 'shield-check'" class="w-8 h-8" style="color: {{ $hex }};" />
            </div>
        </div>

        <!-- Success Alert -->
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-900/20 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400">
                        <flux:icon name="check" class="h-5 w-5" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Main Checklist Form -->
        <form method="POST" action="{{ route('forms.store', $path) }}" class="flex flex-col gap-8">
            @csrf

            <!-- Date Picker (Must be placed at the very top) -->
            <div class="bg-zinc-50 dark:bg-zinc-800/40 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm flex flex-col gap-3 transition-colors hover:border-zinc-300 dark:hover:border-zinc-600">
                <label for="date" class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-200">
                    <flux:icon name="calendar-days" class="w-5 h-5 text-zinc-400" />
                    Tanggal Inspeksi <span class="text-red-500">*</span>
                </label>
                <div class="relative max-w-xs">
                    <input type="date" 
                           id="date" 
                           name="date" 
                           required 
                           value="{{ old('date', date('Y-m-d')) }}" 
                           class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 text-sm text-zinc-900 dark:text-white shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20" />
                </div>
                @error('date')
                    <span class="text-xs text-red-500 font-semibold">{{ $message }}</span>
                @enderror
            </div>

            <!-- Checklist Items Card -->
            <div class="bg-white dark:bg-zinc-800/40 rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm overflow-hidden">
                <div class="border-b border-zinc-100 dark:border-zinc-700/60 px-8 py-5 bg-zinc-50 dark:bg-zinc-900/40 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                        <flux:icon name="list-bullet" class="w-5 h-5" />
                        Daftar Pengecekan
                    </h2>
                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-800 px-3 py-1 rounded-full border border-zinc-200 dark:border-zinc-700">
                        {{ count($form['items']) }} Item
                    </span>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                    @foreach($form['items'] as $index => $item)
                        <label class="flex items-start gap-4 px-8 py-5 cursor-pointer group transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <!-- Hidden original checkbox, custom styled one -->
                            <div class="flex items-center h-6 pt-0.5">
                                <input type="checkbox" 
                                       name="answers[]" 
                                       value="{{ $item }}" 
                                       class="peer relative appearance-none w-5 h-5 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-900 checked:bg-cyan-600 checked:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all cursor-pointer" 
                                       {{ in_array($item, old('answers', [])) ? 'checked' : '' }} />
                                <!-- Custom Checkmark Icon for Tailwind checkbox -->
                                <svg class="absolute w-5 h-5 pointer-events-none hidden peer-checked:block text-white p-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <div class="flex-1 text-sm font-medium text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors leading-relaxed">
                                {{ $item }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Notes/Catatan -->
            <div class="bg-zinc-50 dark:bg-zinc-800/40 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm flex flex-col gap-3 transition-colors hover:border-zinc-300 dark:hover:border-zinc-600">
                <label for="notes" class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-200">
                    <flux:icon name="document-text" class="w-5 h-5 text-zinc-400" />
                    Catatan Tambahan (Opsional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="4" 
                          placeholder="Tuliskan temuan, anomali, atau catatan penting di sini..."
                          class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 text-sm text-zinc-900 dark:text-white shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 resize-y placeholder:text-zinc-400 dark:placeholder:text-zinc-600">{{ old('notes') }}</textarea>
                @error('notes')
                    <span class="text-xs text-red-500 font-semibold">{{ $message }}</span>
                @enderror
            </div>

            <!-- Action Button -->
            <div class="flex justify-end mt-4">
                <button type="submit" 
                        class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-cyan-600 px-8 py-4 text-base font-bold text-white shadow-md shadow-cyan-900/40 ring-1 ring-cyan-500/50 transition-all duration-200 hover:bg-cyan-500 hover:shadow-lg hover:shadow-cyan-800/40 hover:-translate-y-0.5 active:scale-95 w-full sm:w-auto">
                    <div class="absolute inset-0 flex h-full w-full justify-center [transform:skew(-12deg)_translateX(-100%)] group-hover:duration-700 group-hover:[transform:skew(-12deg)_translateX(100%)]">
                        <div class="relative h-full w-12 bg-white/10"></div>
                    </div>
                    <flux:icon name="paper-airplane" class="w-5 h-5 relative z-10" />
                    <span class="relative z-10">Simpan & Kirim Checklist</span>
                </button>
            </div>
        </form>

        <!-- History/Riwayat Submission -->
        <div class="mt-12 border-t border-zinc-200 pt-10 dark:border-zinc-800">
            <div class="flex items-center gap-3 mb-8">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                    <flux:icon name="clock" class="w-5 h-5" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    Riwayat Pengisian
                </h2>
            </div>

            @if($submissions->isEmpty())
                <div class="text-center p-12 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl bg-zinc-50/50 dark:bg-zinc-900/20">
                    <flux:icon name="document-magnifying-glass" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Belum ada data</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                        Belum ada riwayat pengisian untuk form ini. Mulai dengan mengisi checklist di atas.
                    </p>
                </div>
            @else
                <div class="flex flex-col gap-6">
                    @foreach($submissions as $sub)
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all hover:shadow-md">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-100 pb-4 mb-4 dark:border-zinc-800/80">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                        <flux:icon name="calendar" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div class="text-base font-bold text-zinc-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($sub->date)->translatedFormat('d F Y') }}
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $sub->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <div class="w-2 h-2 rounded-full bg-zinc-400"></div>
                                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                        Oleh: {{ $sub->user->name }}
                                    </span>
                                </div>
                            </div>

                            <!-- List Checklist Status -->
                            <div class="flex flex-col gap-3">
                                <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                                    <flux:icon name="check-badge" class="w-4 h-4" />
                                    Hasil Pengecekan
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3 mt-2">
                                    @foreach($form['items'] as $index => $item)
                                        @php
                                            $ans = is_array($sub->answers) ? $sub->answers : [];
                                            $isChecked = in_array($item, $ans) || (!empty($ans[$index]) && $ans[$index]) || (!empty($ans[$item]) && $ans[$item]);
                                        @endphp
                                        <div class="flex items-start gap-3 text-sm p-2 rounded-lg {{ $isChecked ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '' }}">
                                            @if($isChecked)
                                                <div class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 mt-0.5">
                                                    <flux:icon name="check" class="w-3 h-3" />
                                                </div>
                                                <span class="text-zinc-800 dark:text-zinc-200 font-medium leading-relaxed">{{ $item }}</span>
                                            @else
                                                <div class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800/80 dark:text-zinc-500 mt-0.5">
                                                    <flux:icon name="minus" class="w-3 h-3" />
                                                </div>
                                                <span class="text-zinc-400 dark:text-zinc-500 line-through leading-relaxed">{{ $item }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($sub->notes)
                                <div class="mt-6 p-4 rounded-xl bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30">
                                    <div class="flex items-center gap-2 text-xs font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wider mb-2">
                                        <flux:icon name="exclamation-circle" class="w-4 h-4" />
                                        Catatan Temuan
                                    </div>
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300 italic leading-relaxed">
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
