<x-filament-panels::page>
    <div class="flex h-full w-full flex-1 flex-col gap-8 rounded-2xl p-6 sm:p-10 max-w-7xl mx-auto
               bg-white dark:bg-gray-900/60
               shadow-sm dark:shadow-xl ring-1 ring-gray-200 dark:ring-white/5"
         x-data="{
            expandAll: false,
            expandedRows: [],
            summaryModal: { open: false, data: null, monthName: '' },
            matrixSummary: {{ json_encode($summary) }},
            openSummary(monthNum, monthName) {
                this.summaryModal.data = this.matrixSummary[monthNum];
                this.summaryModal.monthName = monthName;
                this.summaryModal.open = true;
            },
            modal: { open: false, loading: false, title: '', submissions: [], error: null },
            toggleRow(id) {
                if (this.expandedRows.includes(id)) {
                    this.expandedRows = this.expandedRows.filter(r => r !== id);
                } else {
                    this.expandedRows.push(id);
                }
            },
            async openDetail(formId, month, year, title) {
                this.modal.open = true;
                this.modal.loading = true;
                this.modal.title = title;
                this.modal.submissions = [];
                this.modal.error = null;
                try {
                    const url = '/matrix/submission-detail?form_id=' + formId + '&month=' + month + '&year=' + year;
                    const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const data = await resp.json();
                    this.modal.submissions = data.submissions || [];
                    this.modal.title = data.form_title || title;
                } catch(e) {
                    this.modal.submissions = [];
                    this.modal.error = 'Gagal memuat data: ' + e.message;
                } finally {
                    this.modal.loading = false;
                }
            }
         }">

        {{-- Header --}}
        <div class="flex flex-col gap-2 border-b border-gray-200 dark:border-gray-800 pb-8 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                        bg-primary-50 dark:bg-primary-500/10
                        text-primary-700 dark:text-primary-400
                        text-xs font-semibold tracking-widest uppercase w-fit
                        border border-primary-200 dark:border-primary-500/20">
                <x-heroicon-o-table-cells style="width: 14px; height: 14px;" />
                <span>Rekap Tahunan</span>
            </div>

            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                Matriks Checklist
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-500 to-primary-700 dark:from-primary-400 dark:to-primary-600">
                    Bulanan {{ $currentYear }}
                </span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                Rekap status pengisian seluruh form checklist sepanjang tahun {{ $currentYear }}.
                Klik nama form untuk melihat detail pengisian.
            </p>
        </div>

        {{-- Matrix Table --}}
        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-end p-4 border-b border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-800/50">
                <label class="flex items-center gap-3 cursor-pointer select-none group">
                    <div class="text-right">
                        <span class="block text-sm font-semibold transition-colors"
                              :class="expandAll ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300'"
                              x-text="expandAll ? 'Semua Detail Terbuka' : 'Tampilkan Semua Detail'"></span>
                        <span class="block text-xs transition-colors"
                              :class="expandAll ? 'text-primary-500/70 dark:text-primary-500/60' : 'text-gray-400 dark:text-gray-500'"
                              x-text="expandAll ? 'Klik untuk kembali ke ringkasan' : 'Klik untuk buka semua sub-form'"></span>
                    </div>
                    <button type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900" style="width: 2.75rem; height: 1.5rem;"
                        :class="expandAll ? 'bg-primary-500' : 'bg-gray-300 dark:bg-gray-600'"
                        @click="expandAll = !expandAll">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" style="width: 1.25rem; height: 1.25rem;"
                              :class="expandAll ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </label>
            </div>

            <table class="w-full text-left divide-y divide-gray-200 dark:divide-gray-700/60" style="table-layout: fixed; min-width: 700px;">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                        <th class="py-4 px-5 font-bold text-sm text-gray-800 dark:text-gray-200" style="width: 240px;">
                            Form Checklist
                        </th>
                        @foreach($months as $monthNum => $monthName)
                            <th class="py-4 px-2 font-semibold text-sm text-center text-gray-600 dark:text-gray-300">
                                <div class="flex flex-col items-center gap-1">
                                    <span>{{ substr($monthName, 0, 3) }}</span>
                                    <button type="button" @click="openSummary({{ $monthNum }}, '{{ $monthName }}')" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-200 transition-colors" title="Ringkasan Bulan Ini">
                                        <svg class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                    </button>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @foreach($matrix as $row)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
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
                                                class="flex items-center gap-2 text-left group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors min-w-0">
                                            <span class="flex-shrink-0 p-1 bg-transparent hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-gray-500 dark:text-gray-400 transition-colors">
                                                <svg x-show="expandAll || expandedRows.includes({{ $row['id'] }})" class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                                <svg x-show="!(expandAll || expandedRows.includes({{ $row['id'] }}))" class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </span>
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-primary-700 dark:group-hover:text-primary-400 truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</span>
                                        </button>
                                    @else
                                        <div class="flex-shrink-0 w-6"></div>
                                        {{-- Leaf form: clickable per-cell (handled in td), just show the title --}}
                                        <span class="{{ $row['level'] > 0 ? 'text-gray-700 dark:text-gray-300 text-xs' : 'font-semibold text-gray-800 dark:text-gray-200' }} truncate" title="{{ $row['title'] }}">
                                            {{ $row['title'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            @foreach($row['status'] as $monthNum => $statusVal)
                                <td class="py-3 px-2 text-center">
                                    @if(!$row['has_children'])
                                        {{-- Leaf form cell: clickable to open detail modal --}}
                                        <button @click="openDetail({{ $row['id'] }}, {{ $monthNum }}, {{ $currentYear }}, '{{ addslashes($row['title']) }}')"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500" style="width: 2.0rem; height: 2.0rem;"
                                                title="{{ $statusVal === 'full' ? 'Sudah diisi — klik untuk lihat detail' : 'Belum diisi' }}">
                                    @else
                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full" style="width: 2.0rem; height: 2.0rem;"
                                             title="{{ $statusVal === 'full' ? 'Semua sub-form sudah diisi' : ($statusVal === 'partial' ? 'Sebagian sudah diisi' : 'Belum ada pengisian') }}">
                                    @endif

                                    @if($statusVal === 'full')
                                        {{-- FULL: green check --}}
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-success-100 dark:bg-success-900/30 text-success-600 dark:text-success-400" style="width: 2.0rem; height: 2.0rem;">
                                            <svg class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                    @elseif($statusVal === 'partial')
                                        {{-- PARTIAL: amber minus/dash --}}
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-warning-100 dark:bg-warning-900/30 text-warning-600 dark:text-warning-400" style="width: 2.0rem; height: 2.0rem;">
                                            <svg class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                            </svg>
                                        </span>
                                    @else
                                        {{-- NONE: red cross --}}
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-danger-50 dark:bg-danger-900/20 text-danger-400 dark:text-danger-500" style="width: 2.0rem; height: 2.0rem;">
                                            <svg class="w-4 h-4" style="width: 1.0rem; height: 1.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif

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

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center text-success-600 dark:text-success-400" style="width: 1.25rem; height: 1.25rem;">
                    <svg class="w-3 h-3" style="width: 0.75rem; height: 0.75rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <span>Semua sub-form sudah diisi</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center text-warning-600 dark:text-warning-400" style="width: 1.25rem; height: 1.25rem;">
                    <svg class="w-3 h-3" style="width: 0.75rem; height: 0.75rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                </div>
                <span>Sebagian sub-form sudah diisi</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-danger-50 dark:bg-danger-900/20 flex items-center justify-center text-danger-400 dark:text-danger-500" style="width: 1.25rem; height: 1.25rem;">
                    <svg class="w-3 h-3" style="width: 0.75rem; height: 0.75rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
                <span>Belum ada pengisian</span>
            </div>
            <div class="flex items-center gap-2 ml-4 pl-4 border-l border-gray-200 dark:border-gray-700">
                <svg class="w-3.5 h-3.5 text-gray-400" style="width: 0.875rem; height: 0.875rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zm-7.518-.267A8.25 8.25 0 1120.25 10.5M8.288 14.212A5.25 5.25 0 1117.25 10.5" /></svg>
                <span class="text-xs">Klik ikon form untuk melihat detail checklist</span>
            </div>
        </div>

        {{-- Submission Detail Modal — teleported to <body> to escape sidebar stacking context --}}
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

                <div class="relative w-full max-w-2xl flex flex-col rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-200 dark:ring-white/10"
                     style="max-height: min(85vh, 700px);"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     @click.stop>

                    {{-- Modal Header — always visible --}}
                    <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="min-w-0 mr-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white truncate" x-text="modal.title"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Detail rekaman submission dari database</p>
                        </div>
                        <button @click="modal.open = false"
                                class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-5 h-5" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body — scrollable --}}
                    <div class="overflow-y-auto flex-1 p-6">
                        {{-- Loading state --}}
                        <div x-show="modal.loading" class="flex items-center justify-center py-12">
                            <svg class="animate-spin w-8 h-8 text-primary-500" style="width: 2.0rem; height: 2.0rem;" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        {{-- Error state --}}
                        <div x-show="!modal.loading && modal.error" class="text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-danger-50 dark:bg-danger-900/20 flex items-center justify-center" style="width: 4.0rem; height: 4.0rem;">
                                <svg class="w-8 h-8 text-danger-400" style="width: 2.0rem; height: 2.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <p class="text-danger-500 dark:text-danger-400 font-medium text-sm" x-text="modal.error"></p>
                        </div>

                        {{-- Empty state --}}
                        <div x-show="!modal.loading && !modal.error && modal.submissions.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center" style="width: 4.0rem; height: 4.0rem;">
                                <svg class="w-8 h-8 text-gray-400" style="width: 2.0rem; height: 2.0rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada submission untuk bulan ini.</p>
                        </div>

                        {{-- Submissions list --}}
                        <div x-show="!modal.loading && modal.submissions.length > 0" class="space-y-4">
                            <template x-for="(sub, idx) in modal.submissions" :key="sub.id">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

                                    {{-- Submission Header --}}
                                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="inline-flex flex-shrink-0 items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-400 text-xs font-bold" style="width: 1.75rem; height: 1.75rem;" x-text="idx + 1"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="sub.date"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Diisi oleh: <span class="font-medium" x-text="sub.user_name"></span></p>
                                            </div>
                                        </div>
                                        <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400">
                                            <svg class="w-3 h-3" style="width: 0.75rem; height: 0.75rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            Submitted
                                        </span>
                                    </div>

                                    {{-- Answers --}}
                                    <div class="px-4 py-3 space-y-3">
                                        <template x-if="sub.answers && Object.keys(sub.answers).length > 0">
                                            <div class="space-y-2">
                                                <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wide">Jawaban Checklist</p>
                                                <template x-for="(value, key) in sub.answers" :key="key">
                                                    <div class="flex items-start justify-between gap-4 text-sm py-1 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                        <span class="text-gray-600 dark:text-gray-400 flex-1 min-w-0 break-words" x-text="key"></span>
                                                        <span class="text-gray-900 dark:text-gray-200 font-semibold text-right shrink-0 max-w-[50%] break-words" x-text="value"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!sub.answers || Object.keys(sub.answers).length === 0">
                                            <p class="text-xs text-gray-400 italic">Tidak ada data isian tersimpan.</p>
                                        </template>

                                        {{-- Notes — always rendered, shows placeholder if empty --}}
                                        <div class="mt-1 pt-3 border-t border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <svg class="w-3.5 h-3.5 text-warning-500" style="width: 0.875rem; height: 0.875rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                                </svg>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wide">Catatan Responden</p>
                                            </div>
                                            <template x-if="sub.notes && sub.notes.trim() !== ''">
                                                <p class="text-sm text-gray-700 dark:text-gray-300 italic bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700/40 rounded-lg px-3 py-2" x-text="sub.notes"></p>
                                            </template>
                                            <template x-if="!sub.notes || sub.notes.trim() === ''">
                                                <p class="text-xs text-gray-400 dark:text-gray-600 italic">Tidak ada catatan.</p>
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

                <div class="relative w-full max-w-4xl flex flex-col rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-200 dark:ring-white/10"
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
                    <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="min-w-0 mr-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan Checklist: <span x-text="summaryModal.monthName"></span></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Total Form: <span class="font-bold text-gray-800 dark:text-gray-200" x-text="summaryModal.data?.total || 0"></span>
                            </p>
                        </div>
                        <button @click="summaryModal.open = false" class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-5 h-5" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Tab Bar — always visible --}}
                    <div class="flex-shrink-0 flex gap-1 px-6 pt-4 border-b border-gray-200 dark:border-gray-800 overflow-x-auto">
                        <button @click="tab = 'overview'"
                                class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                :class="tab === 'overview' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                            Overview
                        </button>
                        <button @click="tab = 'unfilled'"
                                class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                :class="tab === 'unfilled' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                            Belum Diceklis (<span x-text="summaryModal.data?.unfilled_count || 0"></span>)
                        </button>
                        <button @click="tab = 'not_good'"
                                class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                :class="tab === 'not_good' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                            Not Good (<span x-text="summaryModal.data?.not_good_count || 0"></span>)
                        </button>
                        <button @click="tab = 'good'"
                                class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                                :class="tab === 'good' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                            Good (<span x-text="summaryModal.data?.good_count || 0"></span>)
                        </button>
                    </div>

                    {{-- Modal Body — scrollable --}}
                    <div class="overflow-y-auto flex-1 p-6">

                        {{-- Tab: Overview --}}
                        <div x-show="tab === 'overview'" class="space-y-8 animate-fade-in-up">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-3">Progress Pengisian</h4>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                                        <div class="h-full bg-primary-500" :style="'width: ' + (summaryModal.data?.filled_percent || 0) + '%'"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300 w-12 text-right" x-text="(summaryModal.data?.filled_percent || 0) + '%'"></span>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span><span x-text="summaryModal.data?.filled_count || 0"></span> Sudah Diisi</span>
                                    <span><span x-text="summaryModal.data?.unfilled_count || 0"></span> Belum Diisi</span>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-3">Kondisi (Dari Form Terisi)</h4>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                                        <div class="h-full bg-success-500" :style="'width: ' + (summaryModal.data?.good_percent || 0) + '%'"></div>
                                        <div class="h-full bg-danger-500" :style="'width: ' + (summaryModal.data?.not_good_percent || 0) + '%'"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300 w-12 text-right" x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="text-success-600 dark:text-success-400 font-medium">Good: <span x-text="summaryModal.data?.good_count || 0"></span> (<span x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>)</span>
                                    <span class="text-danger-500 font-medium">Not Good: <span x-text="summaryModal.data?.not_good_count || 0"></span> (<span x-text="(summaryModal.data?.not_good_percent || 0) + '%'"></span>)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tab: Unfilled --}}
                        <div x-show="tab === 'unfilled'" class="space-y-2 animate-fade-in-up">
                            <template x-if="!summaryModal.data?.unfilled_forms || summaryModal.data.unfilled_forms.length === 0">
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Semua checklist sudah terisi.</p>
                            </template>
                            <template x-for="item in pagedUnfilled" :key="item.id">
                                <div class="flex items-start gap-3 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-800/30">
                                    <div class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 mt-1.5" style="width: 0.375rem; height: 0.375rem;"></div>
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="item.path"></p>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="item.title"></p>
                                    </div>
                                </div>
                            </template>

                            {{-- Pagination Unfilled --}}
                            <template x-if="summaryModal.data?.unfilled_forms && summaryModal.data.unfilled_forms.length > pageSize">
                                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                                    <button @click="if(pageUnfilled > 1) pageUnfilled--" :disabled="pageUnfilled === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`Hal ${pageUnfilled} dari ${Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)}`"></span>
                                    <button @click="if(pageUnfilled < Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)) pageUnfilled++" :disabled="pageUnfilled === Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                </div>
                            </template>
                        </div>

                        {{-- Tab: Not Good --}}
                        <div x-show="tab === 'not_good'" class="space-y-2 animate-fade-in-up">
                            <template x-if="!summaryModal.data?.not_good_forms || summaryModal.data.not_good_forms.length === 0">
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Tidak ada checklist dengan kondisi Not Good.</p>
                            </template>
                            <template x-for="item in pagedNotGood" :key="item.id">
                                <div class="px-3 py-2.5 rounded-lg border border-danger-200 dark:border-danger-900/30 bg-danger-50 dark:bg-danger-900/10">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="item.path"></p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate mb-1.5" x-text="item.title"></p>
                                    <div class="text-xs text-danger-600 dark:text-danger-400">
                                        <p class="font-semibold uppercase mb-1 text-danger-700 dark:text-danger-300">Bagian Tidak Standar (Failures):</p>
                                        <ul class="list-disc pl-4 space-y-0.5">
                                            <template x-for="(ns, i) in item.non_standard" :key="i">
                                                <li class="truncate" x-text="ns" :title="ns"></li>
                                            </template>
                                        </ul>
                                    </div>
                                    <template x-if="item.notes && item.notes.trim() !== ''">
                                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 italic p-2 bg-danger-100/50 dark:bg-danger-900/20 rounded">
                                            <strong>Catatan:</strong> <span x-text="item.notes"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Pagination Not Good --}}
                            <template x-if="summaryModal.data?.not_good_forms && summaryModal.data.not_good_forms.length > pageSize">
                                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                                    <button @click="if(pageNotGood > 1) pageNotGood--" :disabled="pageNotGood === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`Hal ${pageNotGood} dari ${Math.ceil(summaryModal.data.not_good_forms.length / pageSize)}`"></span>
                                    <button @click="if(pageNotGood < Math.ceil(summaryModal.data.not_good_forms.length / pageSize)) pageNotGood++" :disabled="pageNotGood === Math.ceil(summaryModal.data.not_good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                </div>
                            </template>
                        </div>

                        {{-- Tab: Good --}}
                        <div x-show="tab === 'good'" class="space-y-2 animate-fade-in-up">
                            <template x-if="!summaryModal.data?.good_forms || summaryModal.data.good_forms.length === 0">
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Tidak ada checklist dengan kondisi Good.</p>
                            </template>
                            <template x-for="item in pagedGood" :key="item.id">
                                <div class="px-3 py-2.5 rounded-lg border border-success-200 dark:border-success-900/30 bg-success-50 dark:bg-success-900/10">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="item.path"></p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate mb-1.5" x-text="item.title"></p>
                                    <div class="text-xs text-success-600 dark:text-success-400">
                                        <p class="font-semibold uppercase mb-1">Bagian Standar (Checked):</p>
                                        <div class="flex flex-wrap gap-1">
                                            <template x-for="(st, i) in item.standard" :key="i">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-success-100 dark:bg-success-800/40 text-success-700 dark:text-success-300 max-w-[200px] truncate" x-text="st" :title="st"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Pagination Good --}}
                            <template x-if="summaryModal.data?.good_forms && summaryModal.data.good_forms.length > pageSize">
                                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                                    <button @click="if(pageGood > 1) pageGood--" :disabled="pageGood === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Prev</button>
                                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`Hal ${pageGood} dari ${Math.ceil(summaryModal.data.good_forms.length / pageSize)}`"></span>
                                    <button @click="if(pageGood < Math.ceil(summaryModal.data.good_forms.length / pageSize)) pageGood++" :disabled="pageGood === Math.ceil(summaryModal.data.good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded disabled:opacity-50 transition-colors">Next</button>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </template>
    </div>

</x-filament-panels::page>
