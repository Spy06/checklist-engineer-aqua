<x-layouts::app :title="'Matriks Checklist Bulanan'">
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-2xl p-6 sm:p-10 max-w-7xl mx-auto
               bg-white dark:bg-zinc-900/60
               shadow-sm dark:shadow-xl ring-1 ring-zinc-200 dark:ring-white/5">

        {{-- Header --}}
        <div class="flex flex-col gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-8 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                        bg-cyan-50 dark:bg-cyan-500/10
                        text-cyan-700 dark:text-cyan-400
                        text-xs font-semibold tracking-widest uppercase w-fit
                        border border-cyan-200 dark:border-cyan-500/20">
                <flux:icon name="table-cells" class="w-3.5 h-3.5" />
                <span>Rekap Tahunan</span>
            </div>

            <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                Matriks Checklist
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-cyan-700 dark:from-cyan-400 dark:to-cyan-600">
                    Bulanan {{ $currentYear }}
                </span>
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-xl">
                Rekap status pengisian seluruh form checklist sepanjang tahun {{ $currentYear }}.
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">✓ Sudah diisi</span> /
                <span class="text-red-500 dark:text-red-400 font-semibold">✗ Belum diisi</span>
            </p>
        </div>

        {{-- Matrix Table --}}
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm">
            <table class="w-full text-left divide-y divide-zinc-200 dark:divide-zinc-700/60" style="table-layout: fixed; min-width: 700px;">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="py-4 px-5 font-bold text-sm text-zinc-800 dark:text-zinc-200" style="width: 240px;">
                            Form Checklist
                        </th>
                        @foreach($months as $monthNum => $monthName)
                            <th class="py-4 px-2 font-semibold text-sm text-center text-zinc-600 dark:text-zinc-400">
                                {{ substr($monthName, 0, 3) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @foreach($matrix as $row)
                        <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-4 px-5 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                {{ $row['title'] }}
                            </td>
                            @foreach($row['status'] as $monthNum => $isCompleted)
                                <td class="py-4 px-2 text-center">
                                    @if($isCompleted)
                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                                    bg-emerald-100 dark:bg-emerald-900/30
                                                    text-emerald-600 dark:text-emerald-400"
                                             title="Sudah diisi bulan {{ $months[$monthNum] }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                                    bg-red-50 dark:bg-red-900/20
                                                    text-red-400 dark:text-red-500"
                                             title="Belum diisi bulan {{ $months[$monthNum] }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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

        {{-- Legend --}}
        <div class="flex items-center gap-6 text-sm text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <span>Sudah diisi (minimal 1 kali dalam bulan tersebut)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-400 dark:text-red-500">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
                <span>Belum ada pengisian</span>
            </div>
        </div>
    </div>
</x-layouts::app>
