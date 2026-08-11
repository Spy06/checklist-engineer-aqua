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
                
                {{-- Logo & Brand Header --}}
                <div class="flex items-center justify-between">
                    <a href="/" class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-600 shadow-lg shadow-cyan-900/40 ring-1 ring-cyan-500/40 transition-transform duration-200 hover:scale-105">
                            <x-app-logo-icon class="size-6 text-white" />
                        </span>
                        <div>
                            <div class="text-lg font-bold text-white tracking-tight">Sistem Checklist</div>
                            <div class="text-xs text-cyan-400 font-semibold tracking-widest uppercase">Aqua</div>
                        </div>
                    </a>
                    {{-- Live badge always shown --}}
                    <span class="px-3 py-1.5 bg-amber-500/15 text-amber-400 text-xs font-bold rounded-full uppercase tracking-widest border border-amber-500/30 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Live
                    </span>
                </div>

                 <div class="bg-white dark:bg-zinc-900/60 shadow-sm dark:shadow-xl ring-1 ring-zinc-200 dark:ring-white/5 rounded-2xl p-6 sm:p-10 w-full"
                      x-data="{
                         expandAll: false,
                         expandedRows: [],
                         summaryModal: { open: false, data: null, monthName: '' },
                         modal: {
                             open: false,
                             loading: false,
                             title: '',
                             submissions: [],
                             error: null
                         },
                         matrixSummary: {{ json_encode($summary) }},

                         {{-- Reactive status map: [formId][month] = 'full'|'partial'|'none' --}}
                         matrixStatusMap: {{ json_encode($matrixStatusMap) }},

                         {{-- Auto-refresh state (only meaningful in Live Mode) --}}
                         autoRefresh: false,
                         refreshInterval: 60,
                         refreshing: false,
                         refreshedAt: null,
                         _refreshTimer: null,

                         cellStatus(formId, monthNum) {
                             if (!this.matrixStatusMap[formId]) return 'none';
                             return this.matrixStatusMap[formId][monthNum] || 'none';
                         },

                         async pollLiveData() {
                             if (this.refreshing) return;
                             this.refreshing = true;
                             try {
                                 const res = await fetch('/guest/matrix/live/poll', {
                                     headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                 });
                                 if (!res.ok) throw new Error('Poll failed');
                                 const data = await res.json();
                                 this.matrixStatusMap = data.matrixStatusMap;
                                 this.refreshedAt     = data.refreshedAt;
                             } catch(e) {
                                 console.error('Auto-refresh error:', e);
                             } finally {
                                 this.refreshing = false;
                             }
                         },
                         startAutoRefresh() {
                             this.stopAutoRefresh();
                             const ms = Math.max(10, parseInt(this.refreshInterval) || 60) * 1000;
                             this._refreshTimer = setInterval(() => this.pollLiveData(), ms);
                         },
                         stopAutoRefresh() {
                             if (this._refreshTimer) { clearInterval(this._refreshTimer); this._refreshTimer = null; }
                         },
                         toggleAutoRefresh() {
                             this.autoRefresh = !this.autoRefresh;
                             if (this.autoRefresh) { this.startAutoRefresh(); }
                             else { this.stopAutoRefresh(); }
                         },

                         openDetail(formId, monthNum, year, formTitle) {
                             this.modal.title = formTitle;
                             this.modal.loading = true;
                             this.modal.error = null;
                             this.modal.submissions = [];
                             this.modal.open = true;

                             fetch(`/matrix/submission-detail?form_id=${formId}&month=${monthNum}&year=${year}&live=1`, {
                                 headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                             })
                                 .then(res => {
                                     if (!res.ok) throw new Error('Gagal mengambil data detail');
                                     return res.json();
                                 })
                                 .then(data => {
                                     this.modal.submissions = data.submissions || [];
                                     this.modal.title = data.form_title || formTitle;
                                     this.modal.loading = false;
                                 })
                                 .catch(err => {
                                     console.error(err);
                                     this.modal.error = 'Terjadi kesalahan saat memuat data.';
                                     this.modal.loading = false;
                                 });
                         },
                         openSummary(monthNum, monthName) {
                             this.summaryModal.data = this.matrixSummary[monthNum];
                             this.summaryModal.monthName = monthName;
                             this.summaryModal.open = true;
                         },
                         toggleRow(id) {
                             if (this.expandedRows.includes(id)) {
                                 this.expandedRows = this.expandedRows.filter(r => r !== id);
                             } else {
                                 this.expandedRows.push(id);
                             }
                         }
                      }"
                      @keydown.window.escape="modal.open = false; summaryModal.open = false"
                 >
                     
                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-start gap-3">
                            <flux:icon name="check-circle" class="w-5 h-5 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="text-sm font-bold">Sinkronisasi Berhasil</h4>
                                <p class="text-sm mt-1">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 flex items-start gap-3">
                            <flux:icon name="exclamation-circle" class="w-5 h-5 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="text-sm font-bold">Sinkronisasi Gagal</h4>
                                <p class="text-sm mt-1">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

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

                        <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white sm:text-4xl flex items-center gap-3">
                            <span>Matriks Checklist
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-cyan-700 dark:from-cyan-400 dark:to-cyan-600">
                                Bulanan {{ $currentYear }}
                            </span></span>
                                <span class="px-3 py-1 bg-amber-500 text-white text-sm font-bold rounded-full uppercase tracking-widest shadow-lg shadow-amber-500/30 flex items-center gap-1.5">
                                    <flux:icon name="bolt" class="w-4 h-4" /> Live
                                </span>
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-xl">
                            Rekap status pengisian seluruh form checklist sepanjang tahun {{ $currentYear }}.
                            Klik nama form untuk melihat detail pengisian.
                        </p>
                    </div>

                    {{-- Matrix Table --}}
                    <div class="mt-8">
                        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm">
                            <div class="flex items-center justify-between gap-4 p-4 border-b border-zinc-200 dark:border-zinc-700/60 bg-zinc-50 dark:bg-zinc-800/50">

                                {{-- Auto-refresh toggle --}}
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-3 cursor-pointer select-none group">
                                        <div class="text-left">
                                            <span class="block text-sm font-semibold transition-colors"
                                                  :class="autoRefresh ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-700 dark:text-zinc-300'"
                                                  x-text="autoRefresh ? 'Auto-Refresh: ON' : 'Auto-Refresh'"></span>
                                            <span class="block text-xs transition-colors text-zinc-400 dark:text-zinc-500"
                                                  x-text="refreshedAt ? 'Terakhir: ' + refreshedAt : 'Scrape ulang otomatis'"></span>
                                        </div>
                                        <button type="button"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="autoRefresh ? 'bg-amber-500' : 'bg-zinc-300 dark:bg-zinc-600'"
                                            @click="toggleAutoRefresh()">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                  :class="autoRefresh ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                    </label>

                                    {{-- Interval input (only visible when toggle is on) --}}
                                    <div x-show="autoRefresh" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         class="flex items-center gap-2">
                                        <label class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">Interval:</label>
                                        <input type="number" min="10" max="3600"
                                               x-model="refreshInterval"
                                               @change="autoRefresh && startAutoRefresh()"
                                               class="w-20 text-xs rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white px-2 py-1 text-center focus:outline-none focus:ring-2 focus:ring-amber-500"
                                               placeholder="60" />
                                        <span class="text-xs text-zinc-400">detik</span>
                                        <button type="button" @click="pollLiveData()"
                                                :disabled="refreshing"
                                                class="flex items-center gap-1 px-2 py-1 text-xs rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-800/40 transition-colors disabled:opacity-50">
                                            <svg :class="refreshing ? 'animate-spin' : ''" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            <span x-text="refreshing ? 'Scraping...' : 'Refresh Sekarang'"></span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Expand all toggle --}}
                                <label class="flex items-center gap-3 cursor-pointer select-none group">
                                    <div class="text-right">
                                        <span class="block text-sm font-semibold transition-colors"
                                              :class="expandAll ? 'text-cyan-600 dark:text-cyan-400' : 'text-zinc-700 dark:text-zinc-300'"
                                              x-text="expandAll ? 'Semua Detail Terbuka' : 'Tampilkan Semua Detail'"></span>
                                        <span class="block text-xs transition-colors"
                                              :class="expandAll ? 'text-cyan-500/70 dark:text-cyan-500/60' : 'text-zinc-400 dark:text-zinc-500'"
                                              x-text="expandAll ? 'Klik untuk kembali ke ringkasan' : 'Klik untuk buka semua sub-form'"></span>
                                    </div>
                                    <button type="button"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-zinc-900"
                                        :class="expandAll ? 'bg-cyan-500' : 'bg-zinc-300 dark:bg-zinc-600'"
                                        @click="expandAll = !expandAll">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="expandAll ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </label>
                            </div>

                            <table class="w-full text-left divide-y divide-zinc-200 dark:divide-zinc-700/60" style="table-layout: fixed; min-width: 700px;">
                                <thead>
                                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                        <th class="py-4 px-5 font-bold text-sm text-zinc-800 dark:text-zinc-200" style="width: 240px;">
                                            Form Checklist
                                        </th>
                                        @foreach($months as $monthNum => $monthName)
                                            <th class="py-4 px-2 font-semibold text-sm text-center text-zinc-600 dark:text-zinc-300">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span>{{ substr($monthName, 0, 3) }}</span>
                                                    <button type="button" @click="openSummary({{ $monthNum }}, '{{ $monthName }}')" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-200 transition-colors" title="Ringkasan Bulan Ini">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                                    </button>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                    @foreach($matrix as $row)
                                        <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors"
                                            @if($row['parent_id'])
                                                x-show="expandAll || {{ json_encode($row['ancestors']) }}.every(id => expandedRows.includes(id))"
                                                x-cloak
                                            @endif
                                        >
                                            <td class="py-3 px-5 text-sm"
                                                style="padding-left: {{ 1.25 + ($row['level'] * 1.5) }}rem;">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    @if($row['has_children'])
                                                        <button @click="toggleRow({{ $row['id'] }})"
                                                                class="flex items-center gap-2 text-left group-hover:text-cyan-700 dark:group-hover:text-cyan-400 transition-colors min-w-0">
                                                            <span class="flex-shrink-0 p-1 bg-transparent hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded text-zinc-500 dark:text-zinc-400 transition-colors">
                                                                <svg x-show="expandAll || expandedRows.includes({{ $row['id'] }})" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                                </svg>
                                                                <svg x-show="!(expandAll || expandedRows.includes({{ $row['id'] }}))" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                                </svg>
                                                            </span>
                                                            <span class="font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-cyan-700 dark:group-hover:text-cyan-400 truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</span>
                                                        </button>
                                                    @else
                                                        <div class="flex-shrink-0 w-6"></div>
                                                        <span class="{{ $row['level'] > 0 ? 'text-zinc-700 dark:text-zinc-300 text-xs' : 'font-semibold text-zinc-800 dark:text-zinc-200' }} truncate" title="{{ $row['title'] }}">
                                                            {{ $row['title'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            @foreach($row['status'] as $monthNum => $initialStatus)
                                                <td class="py-3 px-2 text-center">
                                                    @if(!$row['has_children'])
                                                        <button @click="openDetail({{ $row['id'] }}, {{ $monthNum }}, {{ $currentYear }}, '{{ addslashes($row['title']) }}')"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-cyan-500"
                                                                :title="cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'full' ? 'Sudah diisi — klik untuk lihat detail' : 'Belum diisi'">
                                                    @else
                                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full"
                                                             :title="cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'full' ? 'Semua sub-form sudah diisi' : (cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'partial' ? 'Sebagian sudah diisi' : 'Belum ada pengisian')">
                                                    @endif

                                                        {{-- Full (check) --}}
                                                        <span x-show="cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'full'"
                                                              class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                            </svg>
                                                        </span>
                                                        {{-- Partial (dash) --}}
                                                        <span x-show="cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'partial'"
                                                              class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                                            </svg>
                                                        </span>
                                                        {{-- None (X) --}}
                                                        <span x-show="cellStatus({{ $row['id'] }}, {{ $monthNum }}) === 'none'"
                                                              class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/20 text-red-400 dark:text-red-500">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </span>

                                                    @if(!$row['has_children'])
                                                        </button>
                                                    @else
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

                    {{-- Legend --}}
                    <div class="mt-6">
                        <div class="flex flex-wrap items-center gap-6 text-sm text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <span>Semua sub-form sudah diisi</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                </div>
                                <span>Sebagian sub-form sudah diisi</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-400 dark:text-red-500">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </div>
                                <span>Belum ada pengisian</span>
                            </div>
                        </div>
                    </div>

                    {{-- Submission Detail Modal --}}
                    <template x-teleport="body">
                        <div x-show="modal.open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 flex items-center justify-center p-4"
                             style="z-index: 9999; background: rgba(0,0,0,0.65);"
                             @keydown.escape.window="modal.open = false"
                             @click.self="modal.open = false">

                            <div class="relative w-full max-w-2xl flex flex-col rounded-2xl bg-white dark:bg-zinc-900 shadow-2xl ring-1 ring-zinc-200 dark:ring-white/10"
                                 style="max-height: min(85vh, 700px);"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 @click.stop>

                                {{-- Modal Header --}}
                                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                                    <div class="min-w-0 mr-4">
                                        <h3 class="text-base font-bold text-zinc-900 dark:text-white truncate" x-text="modal.title"></h3>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Detail rekaman submission dari database</p>
                                    </div>
                                    <button @click="modal.open = false"
                                            class="flex-shrink-0 p-2 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Modal Body --}}
                                <div class="overflow-y-auto flex-1 p-6">
                                    {{-- Loading state --}}
                                    <div x-show="modal.loading" class="flex items-center justify-center py-12">
                                        <svg class="animate-spin w-8 h-8 text-cyan-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>

                                    {{-- Error state --}}
                                    <div x-show="!modal.loading && modal.error" class="text-center py-12">
                                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                            </svg>
                                        </div>
                                        <p class="text-red-500 dark:text-red-400 font-medium text-sm" x-text="modal.error"></p>
                                    </div>

                                    {{-- Empty state --}}
                                    <div x-show="!modal.loading && !modal.error && modal.submissions.length === 0" class="text-center py-12">
                                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                        <p class="text-zinc-500 dark:text-zinc-400 font-medium">Belum ada submission untuk bulan ini.</p>
                                    </div>

                                    {{-- Submissions list --}}
                                    <div x-show="!modal.loading && modal.submissions.length > 0" class="space-y-4">
                                        <template x-for="(sub, idx) in modal.submissions" :key="sub.id">
                                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">

                                                {{-- Submission Header --}}
                                                <div class="flex items-center justify-between px-4 py-3 bg-zinc-50 dark:bg-zinc-800">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <span class="inline-flex flex-shrink-0 items-center justify-center w-7 h-7 rounded-full bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400 text-xs font-bold" x-text="idx + 1"></span>
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200" x-text="sub.date"></p>
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">Diisi oleh: <span class="font-medium" x-text="sub.user_name"></span></p>
                                                        </div>
                                                    </div>
                                                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                        Submitted
                                                    </span>
                                                </div>

                                                {{-- Answers --}}
                                                <div class="px-4 py-3 space-y-3">
                                                    <template x-if="sub.answers && Object.keys(sub.answers).length > 0">
                                                        <div class="space-y-2">
                                                            <p class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wide">Jawaban Checklist</p>
                                                            <template x-for="(value, key) in sub.answers" :key="key">
                                                                <div class="flex items-start justify-between gap-4 text-sm py-1 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                                                    <span class="text-zinc-600 dark:text-zinc-400 flex-1 min-w-0 break-words" x-text="key"></span>
                                                                    <span class="text-zinc-900 dark:text-zinc-200 font-semibold text-right shrink-0 max-w-[50%] break-words" x-text="value"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!sub.answers || Object.keys(sub.answers).length === 0">
                                                        <p class="text-xs text-zinc-400 italic">Tidak ada data isian tersimpan.</p>
                                                    </template>

                                                    {{-- Failures --}}
                                                    <template x-if="sub.failures && sub.failures.length > 0">
                                                        <div class="mt-2 pt-3 border-t border-red-100 dark:border-red-900/30">
                                                            <div class="flex items-center gap-2 mb-1.5">
                                                                <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                                <p class="text-xs text-red-600 dark:text-red-400 font-semibold uppercase tracking-wide">Bagian Tidak Standar (Failures)</p>
                                                            </div>
                                                            <ul class="list-disc pl-5 space-y-1">
                                                                <template x-for="(failure, i) in sub.failures" :key="i">
                                                                    <li class="text-sm text-red-700 dark:text-red-300 font-medium" x-text="failure"></li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </template>

                                                    {{-- Notes --}}
                                                    <div class="mt-1 pt-3 border-t border-zinc-100 dark:border-zinc-700">
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                                            </svg>
                                                            <p class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wide">Catatan Responden</p>
                                                        </div>
                                                        <template x-if="sub.notes && sub.notes.trim() !== ''">
                                                            <p class="text-sm text-zinc-700 dark:text-zinc-300 italic bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-lg px-3 py-2" x-text="sub.notes"></p>
                                                        </template>
                                                        <template x-if="!sub.notes || sub.notes.trim() === ''">
                                                            <p class="text-xs text-zinc-400 dark:text-zinc-600 italic">Tidak ada catatan.</p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Summary Modal --}}
                    <template x-teleport="body">
                        <div x-show="summaryModal.open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 flex items-center justify-center p-4"
                             style="z-index: 9999; background: rgba(0,0,0,0.65);"
                             @keydown.escape.window="summaryModal.open = false"
                             @click.self="summaryModal.open = false">

                            <div class="relative w-full max-w-4xl flex flex-col rounded-2xl bg-white dark:bg-zinc-900 shadow-2xl ring-1 ring-zinc-200 dark:ring-white/10"
                                 style="max-height: min(90vh, 750px);"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 @click.stop
                                 x-data="{
                                     tab: 'overview',
                                     pageSize: 10,
                                     pageUnfilled: 1,
                                     pageNotGood: 1,
                                     pageGood: 1,
                                     get pagedUnfilled() {
                                         let items = this.summaryModal.data?.unfilled_forms || [];
                                         let start = (this.pageUnfilled - 1) * this.pageSize;
                                         return items.slice(start, start + this.pageSize);
                                     },
                                     get pagedNotGood() {
                                         let items = this.summaryModal.data?.not_good_forms || [];
                                         let start = (this.pageNotGood - 1) * this.pageSize;
                                         return items.slice(start, start + this.pageSize);
                                     },
                                     get pagedGood() {
                                         let items = this.summaryModal.data?.good_forms || [];
                                         let start = (this.pageGood - 1) * this.pageSize;
                                         return items.slice(start, start + this.pageSize);
                                     },
                                     resetPages() {
                                         this.tab = 'overview';
                                         this.pageUnfilled = 1;
                                         this.pageNotGood = 1;
                                         this.pageGood = 1;
                                     }
                                 }"
                                 x-init="$watch('summaryModal.open', val => { if(val) resetPages(); })">

                                {{-- Modal Header — always visible --}}
                                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                                    <div class="min-w-0 mr-4">
                                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Ringkasan Checklist: <span x-text="summaryModal.monthName"></span></h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                                            Total Form: <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="summaryModal.data?.total || 0"></span>
                                        </p>
                                    </div>
                                    <button @click="summaryModal.open = false" class="flex-shrink-0 p-2 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                {{-- Tab Bar — always visible --}}
                                <div class="flex-shrink-0 flex gap-1 px-6 pt-4 border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto">
                                    <button @click="tab = 'overview'"
                                            class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                            :class="tab === 'overview' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                                        Overview
                                    </button>
                                    <button @click="tab = 'unfilled'"
                                            class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                            :class="tab === 'unfilled' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                                        Belum Diceklis (<span x-text="summaryModal.data?.unfilled_count || 0"></span>)
                                    </button>
                                    <button @click="tab = 'not_good'"
                                            class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                            :class="tab === 'not_good' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                                        Not Good (<span x-text="summaryModal.data?.not_good_count || 0"></span>)
                                    </button>
                                    <button @click="tab = 'good'"
                                            class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                            :class="tab === 'good' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                                        Good (<span x-text="summaryModal.data?.good_count || 0"></span>)
                                    </button>
                                </div>

                                {{-- Modal Body — scrollable --}}
                                <div class="overflow-y-auto flex-1 p-6">

                                    {{-- Tab: Overview --}}
                                    <div x-show="tab === 'overview'" class="space-y-8 animate-fade-in-up">
                                        <div>
                                            <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-3">Progress Pengisian</h4>
                                            <div class="flex items-center gap-4">
                                                <div class="flex-1 h-4 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden flex">
                                                    <div class="h-full bg-cyan-500" :style="'width: ' + (summaryModal.data?.filled_percent || 0) + '%'"></div>
                                                </div>
                                                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300 w-12 text-right" x-text="(summaryModal.data?.filled_percent || 0) + '%'"></span>
                                            </div>
                                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                <span><span x-text="summaryModal.data?.filled_count || 0"></span> Sudah Diisi</span>
                                                <span><span x-text="summaryModal.data?.unfilled_count || 0"></span> Belum Diisi</span>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-3">Kondisi (Dari Form Terisi)</h4>
                                            <div class="flex items-center gap-4">
                                                <div class="flex-1 h-4 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden flex">
                                                    <div class="h-full bg-emerald-500" :style="'width: ' + (summaryModal.data?.good_percent || 0) + '%'"></div>
                                                    <div class="h-full bg-red-500" :style="'width: ' + (summaryModal.data?.not_good_percent || 0) + '%'"></div>
                                                </div>
                                                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300 w-12 text-right" x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>
                                            </div>
                                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Good: <span x-text="summaryModal.data?.good_count || 0"></span> (<span x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>)</span>
                                                <span class="text-red-500 font-medium">Not Good: <span x-text="summaryModal.data?.not_good_count || 0"></span> (<span x-text="(summaryModal.data?.not_good_percent || 0) + '%'"></span>)</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tab: Unfilled --}}
                                    <div x-show="tab === 'unfilled'" class="space-y-2 animate-fade-in-up">
                                        <template x-if="!summaryModal.data?.unfilled_forms || summaryModal.data.unfilled_forms.length === 0">
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">Semua checklist sudah terisi.</p>
                                        </template>
                                        <template x-for="item in pagedUnfilled" :key="item.id">
                                            <div class="flex items-start gap-3 px-3 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700/60 bg-zinc-50 dark:bg-zinc-800/30">
                                                <div class="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500 mt-1.5"></div>
                                                <div class="min-w-0">
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate" x-text="item.path"></p>
                                                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate" x-text="item.title"></p>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Pagination Unfilled --}}
                                        <template x-if="summaryModal.data?.unfilled_forms && summaryModal.data.unfilled_forms.length > pageSize">
                                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700/60">
                                                <button @click="if(pageUnfilled > 1) pageUnfilled--" :disabled="pageUnfilled === 1" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                                <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="`Hal ${pageUnfilled} dari ${Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)}`"></span>
                                                <button @click="if(pageUnfilled < Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)) pageUnfilled++" :disabled="pageUnfilled === Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Tab: Not Good --}}
                                    <div x-show="tab === 'not_good'" class="space-y-2 animate-fade-in-up">
                                        <template x-if="!summaryModal.data?.not_good_forms || summaryModal.data.not_good_forms.length === 0">
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">Tidak ada checklist dengan kondisi Not Good.</p>
                                        </template>
                                        <template x-for="item in pagedNotGood" :key="item.id">
                                            <div class="px-3 py-2.5 rounded-lg border border-red-200 dark:border-red-900/30 bg-red-50 dark:bg-red-900/10">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate" x-text="item.path"></p>
                                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate mb-1.5" x-text="item.title"></p>
                                                <div class="text-xs text-red-600 dark:text-red-400">
                                                    <p class="font-semibold uppercase mb-1 text-red-700 dark:text-red-300">Bagian Tidak Standar (Failures):</p>
                                                    <ul class="list-disc pl-4 space-y-0.5">
                                                        <template x-for="(ns, i) in item.non_standard" :key="i">
                                                            <li class="truncate" x-text="ns" :title="ns"></li>
                                                        </template>
                                                    </ul>
                                                </div>
                                                <template x-if="item.notes && item.notes.trim() !== ''">
                                                    <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 italic p-2 bg-red-100/50 dark:bg-red-900/20 rounded">
                                                        <strong>Catatan:</strong> <span x-text="item.notes"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Pagination Not Good --}}
                                        <template x-if="summaryModal.data?.not_good_forms && summaryModal.data.not_good_forms.length > pageSize">
                                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700/60">
                                                <button @click="if(pageNotGood > 1) pageNotGood--" :disabled="pageNotGood === 1" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                                <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="`Hal ${pageNotGood} dari ${Math.ceil(summaryModal.data.not_good_forms.length / pageSize)}`"></span>
                                                <button @click="if(pageNotGood < Math.ceil(summaryModal.data.not_good_forms.length / pageSize)) pageNotGood++" :disabled="pageNotGood === Math.ceil(summaryModal.data.not_good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Tab: Good --}}
                                    <div x-show="tab === 'good'" class="space-y-2 animate-fade-in-up">
                                        <template x-if="!summaryModal.data?.good_forms || summaryModal.data.good_forms.length === 0">
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">Tidak ada checklist dengan kondisi Good.</p>
                                        </template>
                                        <template x-for="item in pagedGood" :key="item.id">
                                            <div class="px-3 py-2.5 rounded-lg border border-emerald-200 dark:border-emerald-900/30 bg-emerald-50 dark:bg-emerald-900/10">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate" x-text="item.path"></p>
                                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate mb-1.5" x-text="item.title"></p>
                                                <div class="text-xs text-emerald-600 dark:text-emerald-400">
                                                    <p class="font-semibold uppercase mb-1">Bagian Standar (Checked):</p>
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="(st, i) in item.standard" :key="i">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-800/40 text-emerald-700 dark:text-emerald-300 max-w-[200px] truncate" x-text="st" :title="st"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Pagination Good --}}
                                        <template x-if="summaryModal.data?.good_forms && summaryModal.data.good_forms.length > pageSize">
                                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700/60">
                                                <button @click="if(pageGood > 1) pageGood--" :disabled="pageGood === 1" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                                <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="`Hal ${pageGood} dari ${Math.ceil(summaryModal.data.good_forms.length / pageSize)}`"></span>
                                                <button @click="if(pageGood < Math.ceil(summaryModal.data.good_forms.length / pageSize)) pageGood++" :disabled="pageGood === Math.ceil(summaryModal.data.good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                            </div>
                                        </template>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </template>

                </div>

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
