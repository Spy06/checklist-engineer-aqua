<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
 <head>
 @include('partials.head')
 </head>
 <body class="min-h-screen antialiased bg-gray-50 text-gray-900 "
 x-data="{
 isDark: document.documentElement.classList.contains('dark'),
 expandAll: false,
 expandedRows: [],
 summaryModal: { open: false, data: null, monthName: '' },
 modal: { open: false, loading: false, title: '', submissions: [], error: null },
 matrixSummary: {{ json_encode($summary) }},
 annualSummary: {{ json_encode($annualSummary) }},
 monthsList: {{ json_encode($monthsList) }},
 downloadModalOpen: false,
 navbarHeight: 64,

 liveMode: true,
 fromDate: '{{ date('Y-01-01') }}',
 toDate: '{{ date('Y-m-d') }}',
 filterLoading: false,

 // ── Chunked Polling State ─────────────────────────────────────
 isPolling: false,
 pollProgress: 0,
 pollText: '',

 // ── Form Filter State ─────────────────────────────────────────
 filterModalOpen: false,
 matrixRows: {{ json_encode($matrix) }},
 visibleIds: {{ json_encode(collect($matrix)->pluck('id')) }},

 toggleFilterNode(id, checked) {
 let row = this.matrixRows.find(r => r.id === id);
 if (checked) {
 if (!this.visibleIds.includes(id)) this.visibleIds.push(id);
 } else {
 this.visibleIds = this.visibleIds.filter(v => v !== id);
 }
 if (row && row.has_children) {
 let children = this.matrixRows.filter(r => r.parent_id === id);
 children.forEach(c => this.toggleFilterNode(c.id, checked));
 }
 },
 
 applyFilterChanges() {
 this.filterModalOpen = false;
 // If in live mode, we might want to refresh. But to be safe, just apply date filter (which sends hidden_ids)
 this.applyDateFilter();
 },

 // ── Donut diagram (computed, updated whenever annualSummary changes) ──
 donut: {
 belum: { len: 0, offset: 0, count: 0, pct: 0 },
 good: { len: 0, offset: 0, count: 0, pct: 0 },
 notGood: { len: 0, offset: 0, count: 0, pct: 0 },
 total: 0
 },
 donutHoverLabel: '',
 donutHoverValue: '',
 donutHoverColor: '',

 computeDonut() {
 const t = this.annualSummary;
 const total = t.total || 1; // avoid division by 0
 const belum = t.unfilled_count || 0;
 const good = t.good_count || 0;
 const notGood = t.not_good_count || 0;

 const C = 2 * Math.PI * 40; // 251.327

 const lenBelum = (belum / total) * C;
 const lenGood = (good / total) * C;
 const lenNotGood = (notGood / total) * C;

 const leafCount = this.matrixRows.filter(r => !r.is_category && this.visibleIds.includes(r.id)).length;

 this.donut = {
 belum: { len: lenBelum, offset: 0, count: belum, pct: t.unfilled_percent || 0 },
 good: { len: lenGood, offset: lenBelum, count: good, pct: Math.round((good/total)*100) || 0 },
 notGood: { len: lenNotGood, offset: lenBelum + lenGood, count: notGood, pct: Math.round((notGood/total)*100) || 0 },
 total: leafCount
 };
 },

 showDonutHover(label, value, colorClass) {
 this.donutHoverLabel = label;
 this.donutHoverValue = value;
 this.donutHoverColor = colorClass;
 },
 hideDonutHover() { 
 this.donutHoverLabel = '';
 this.donutHoverValue = '';
 this.donutHoverColor = '';
 },

 // ── Reactive status map ───────────────────────────────────────
 matrixStatusMap: {{ json_encode($matrixStatusMap) }},

 // ── Auto-refresh ──────────────────────────────────────────────
 autoRefresh: false,
 refreshInterval: 60,
 refreshing: false,
 refreshedAt: null,
 _refreshTimer: null,

 cellStatus(formId, monthKey) {
 if (!this.matrixStatusMap[formId]) return 'none';
 return this.matrixStatusMap[formId][monthKey] || 'none';
 },

 async pollLiveData() {
 if (this.refreshing || this.isPolling) return;
 this.isPolling = true;
 this.refreshing = true;
 this.pollProgress = 0;
 this.pollText = 'Menginisialisasi pengambilan data...';

 try {
 let initRes = await fetch('/guest/matrix/live/poll/init', {
 headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
 });
 let initData = await initRes.json();
 let forms = initData.forms || [];

 let slugsToFetch = forms.filter(gf => {
 let rootRow = this.matrixRows.find(r => r.slug === gf.slug);
 if (!rootRow) return true;
 let descendants = this.matrixRows.filter(r => r.ancestors && r.ancestors.includes(rootRow.id));
 return this.visibleIds.includes(rootRow.id) || descendants.some(d => this.visibleIds.includes(d.id));
 });

 let fetchedSlugs = [];
 for (let i = 0; i < slugsToFetch.length; i++) {
 let gf = slugsToFetch[i];
 this.pollText = `${i+1}/${slugsToFetch.length}: ${gf.slug}`;
 this.pollProgress = Math.round((i / slugsToFetch.length) * 100);
 
 try {
 let chunkRes = await fetch('/guest/matrix/live/poll/chunk', {
 method: 'POST',
 headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
 body: JSON.stringify({ slug: gf.slug, id: gf.id })
 });
 let chunkData = await chunkRes.json();
 if (chunkData.success) {
 this.pollText = `${i+1}/${slugsToFetch.length}: ${gf.slug} (${chunkData.count} data fetched)`;
 fetchedSlugs.push(gf.slug);
 }
 } catch (e) { console.error(e); }
 }

 this.pollProgress = 100;
 this.pollText = 'Menyimpan dan mengkalkulasi matriks...';

 let hiddenIds = this.matrixRows.map(r => r.id).filter(id => !this.visibleIds.includes(id));
 let finishRes = await fetch('/guest/matrix/live/poll/finish', {
 method: 'POST',
 headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
 body: JSON.stringify({ 
 fetched_slugs: fetchedSlugs, 
 hidden_ids: hiddenIds,
 from_date: this.liveMode ? null : this.fromDate,
 to_date: this.liveMode ? null : this.toDate
 })
 });
 
 let finishData = await finishRes.json();
 this.matrixStatusMap = finishData.matrixStatusMap;
 if (finishData.annualSummary) { this.annualSummary = finishData.annualSummary; this.computeDonut(); }
 if (finishData.summary) { this.matrixSummary = finishData.summary; }
 if (finishData.monthsList) { this.monthsList = finishData.monthsList; }
 this.refreshedAt = finishData.refreshedAt;
 } catch(e) {
 console.error('Auto-refresh error:', e);
 } finally {
 this.isPolling = false;
 this.refreshing = false;
 setTimeout(() => { if (!this.isPolling) this.pollText = ''; }, 3000);
 }
 },

 async applyDateFilter() {
 if (this.filterLoading) return;
 this.filterLoading = true;
 let hiddenIds = this.matrixRows.map(r => r.id).filter(id => !this.visibleIds.includes(id));
 try {
 const url = `/guest/matrix/view?from_date=${this.fromDate}&to_date=${this.toDate}&hidden_ids=${JSON.stringify(hiddenIds)}`;
 const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
 if (!res.ok) throw new Error('Filter failed');
 const data = await res.json();
 this.matrixStatusMap = data.matrixStatusMap;
 if (data.annualSummary) { this.annualSummary = data.annualSummary; this.computeDonut(); }
 if (data.summary) { this.matrixSummary = data.summary; }
 if (data.monthsList) { this.monthsList = data.monthsList; }
 } catch(e) {
 console.error('Filter error:', e);
 } finally {
 this.filterLoading = false;
 }
 },

 activateLiveMode() {
 this.liveMode = true;
 this.fromDate = '{{ date('Y-01-01') }}';
 this.toDate = '{{ date('Y-m-d') }}';
 this.applyDateFilter();
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
 if (this.autoRefresh) this.startAutoRefresh();
 else this.stopAutoRefresh();
 },

 openDetail(formId, monthKey, formTitle) {
 this.modal.title = formTitle;
 this.modal.loading = true;
 this.modal.error = null;
 this.modal.submissions = [];
 this.modal.open = true;
 fetch(`/matrix/submission-detail?form_id=${formId}&month_key=${monthKey}&live=1`, {
 headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
 })
 .then(async res => {
 if (!res.ok) {
 const errData = await res.json().catch(() => ({}));
 throw new Error(errData.error || 'Terjadi kesalahan saat memuat data.');
 }
 return res.json();
 })
 .then(data => { this.modal.submissions = data.submissions || []; this.modal.title = data.form_title || formTitle; this.modal.loading = false; })
 .catch(err => { console.error(err); this.modal.error = err.message; this.modal.loading = false; });
 },
 openSummary(monthKey, monthName) {
 this.summaryModal.data = this.matrixSummary[monthKey];
 this.summaryModal.monthName = monthName;
 this.summaryModal.open = true;
 },
 toggleRow(id) {
 if (this.expandedRows.includes(id)) this.expandedRows = this.expandedRows.filter(r => r !== id);
 else this.expandedRows.push(id);
 }
 }"
 x-init="computeDonut(); $watch('annualSummary', () => computeDonut()); pollLiveData(); navbarHeight = $refs.navbar.offsetHeight"
 @resize.window="navbarHeight = $refs.navbar.offsetHeight"
 @keydown.window.escape="modal.open = false; summaryModal.open = false"
 >

 <div class="flex min-h-screen w-full flex-col">
 
 {{-- Top Navbar --}}
 <header x-ref="navbar" class="sticky top-0 z-[100] border-b border-gray-200 bg-white shadow-sm" style="z-index: 9999;">
 <div class="flex items-center px-4 sm:px-10 py-3 sm:py-4 max-w-7xl mx-auto w-full gap-3 sm:gap-4">
 <img src="{{ asset('GKL3_Aqua - Koleksilogo.com.png') }}" alt="Logo AQUA" class="h-8 sm:h-10 w-auto object-contain shrink-0" />
 <div class="min-w-0 flex-1">
 <span class="block text-sm sm:text-base font-bold text-gray-900 tracking-tight">PANDU</span>
 <span class="block text-[10px] sm:text-sm text-gray-500 mt-0.5 truncate sm:overflow-visible sm:whitespace-normal">Platform Analitik dan Digitalisasi Checklist Plant Cianjur</span>
 </div>
 </div>
 </header>

 {{-- Main Content Area --}}
 <main class="flex-1 overflow-auto p-6 md:p-10 w-full max-w-full bg-gray-50 ">
 
 {{-- Alerts --}}
 <div class="max-w-7xl mx-auto">
 @if(session('success'))
 <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-sm">
 <flux:icon name="check-circle" class="w-5 h-5 shrink-0 mt-0.5" />
 <div>
 <h4 class="text-sm font-bold">Sinkronisasi Berhasil</h4>
 <p class="text-sm mt-1">{{ session('success') }}</p>
 </div>
 </div>
 @endif
 @if(session('error'))
 <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-start gap-3 shadow-sm">
 <flux:icon name="exclamation-circle" class="w-5 h-5 shrink-0 mt-0.5" />
 <div>
 <h4 class="text-sm font-bold">Sinkronisasi Gagal</h4>
 <p class="text-sm mt-1">{{ session('error') }}</p>
 </div>
 </div>
 @endif

 {{-- Info banner: data belum dimuat jika cache kosong (controlled reactively by Alpine) --}}
 <div x-show="!refreshing && !Object.values(matrixStatusMap).some(months => Object.values(months).some(s => s === 'full' || s === 'partial'))"
 x-cloak
 class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 flex items-start gap-3 shadow-sm">
 <flux:icon name="information-circle" class="w-5 h-5 shrink-0 mt-0.5" />
 <div>
 <h4 class="text-sm font-bold">Data Belum Dimuat</h4>
 <p class="text-sm mt-1">Klik tombol <strong>Ambil Data</strong> di bagian atas untuk menarik data langsung dari Google Forms. Proses ini mungkin memakan waktu 15–30 detik.</p>
 </div>
 </div>
 </div>

 {{-- Matrix Card --}}
 <div class="bg-white shadow-sm border border-gray-200 rounded-2xl w-full max-w-7xl mx-auto overflow-hidden">

 {{-- TOP CONTROLS BAR --}}
 <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50 ">
 <div class="flex items-center justify-start w-full md:w-auto gap-3 flex-wrap md:flex-nowrap">
 {{-- Ambil Data button --}}
 <button type="button" @click="pollLiveData()"
 :disabled="refreshing"
 class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white transition-colors disabled:opacity-50 shadow-sm">
 <svg :class="refreshing ? 'animate-spin' : ''" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
 <span x-text="refreshing ? 'Mengambil data...' : 'Ambil Data'"></span>
 </button>

 {{-- Loading Progress Bar --}}
 <div x-show="pollText !== ''" x-cloak class="flex flex-col justify-center min-w-[200px]" x-transition>
 <div class="text-[10px] font-bold text-cyan-700 mb-0.5 truncate max-w-[250px]" x-text="pollText"></div>
 <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
 <div class="h-full bg-cyan-500 transition-all duration-300 ease-out rounded-full" :style="`width: ${pollProgress}%`"></div>
 </div>
 </div>

 {{-- Auto-Refresh toggle --}}
 <label class="flex items-center gap-2 cursor-pointer select-none">
 <div class="text-left">
 <span class="block text-xs font-semibold transition-colors"
 :class="autoRefresh ? 'text-cyan-600 ' : 'text-gray-600 '"
 x-text="autoRefresh ? 'Auto-Refresh: ON' : 'Auto-Refresh'"></span>
 <span class="block text-[10px] text-gray-400 "
 x-text="refreshedAt ? 'Terakhir: ' + refreshedAt : 'Scrape ulang otomatis'"></span>
 </div>
 <button type="button"
 class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
 :class="autoRefresh ? 'bg-cyan-500' : 'bg-gray-300 '"
 @click="toggleAutoRefresh()">
 <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
 :class="autoRefresh ? 'translate-x-4' : 'translate-x-0'"></span>
 </button>
 </label>

 {{-- Interval input (only when auto-refresh is on) --}}
 <div x-show="autoRefresh" x-cloak
 x-transition:enter="transition ease-out duration-150"
 x-transition:enter-start="opacity-0 scale-95"
 x-transition:enter-end="opacity-100 scale-100"
 class="flex items-center gap-1.5">
 <label class="text-xs text-gray-500 ">Interval:</label>
 <input type="number" min="10" max="3600"
 x-model="refreshInterval"
 @change="autoRefresh && startAutoRefresh()"
 class="w-14 text-xs rounded-md border border-gray-300 bg-white text-gray-800 px-2 py-1 text-center focus:outline-none focus:ring-2 focus:ring-cyan-500"
 placeholder="60" />
 <span class="text-xs text-gray-400">detik</span>
 </div>
 </div>

 {{-- Expand All toggle --}}
 <label class="flex items-center justify-start md:justify-end w-full md:w-auto gap-2.5 cursor-pointer select-none group">
 <div class="text-left md:text-right">
 <span class="block text-xs font-semibold transition-colors"
 :class="expandAll ? 'text-cyan-600 ' : 'text-gray-600 '"
 x-text="expandAll ? 'Semua Detail Terbuka' : 'Tampilkan Semua Detail'"></span>
 <span class="block text-[10px] transition-colors"
 :class="expandAll ? 'text-cyan-500/70' : 'text-gray-400 '"
 x-text="expandAll ? 'Klik untuk kembali ke ringkasan' : 'Klik untuk buka semua sub-form'"></span>
 </div>
 <button type="button"
 class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
 :class="expandAll ? 'bg-cyan-500' : 'bg-gray-300 '"
 @click="expandAll = !expandAll">
 <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
 :class="expandAll ? 'translate-x-4' : 'translate-x-0'"></span>
 </button>
 </label>
 </div>

 <div class="p-6 sm:p-10">
 {{-- Matrix Header Inside Card --}}
 <div class="flex flex-col gap-2 border-b border-gray-100 pb-8">
 <div class="flex flex-wrap justify-between items-start gap-4">
 <div>
 <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
 bg-cyan-50 
 text-cyan-700 
 text-xs font-semibold tracking-widest uppercase w-fit
 border border-cyan-200 ">
 <flux:icon name="table-cells" class="w-3.5 h-3.5" />
 <span>Rekap Tahunan</span>
 </div>
 <h2 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl mt-2">
 Matriks Checklist <span class="text-cyan-600 ">Bulanan</span>
 </h2>
 <p class="text-sm text-gray-500 max-w-xl mt-1">
 Rekap status pengisian form checklist. Klik nama form untuk melihat detail.
 </p>
 </div>
 </div>

 {{-- Date range filter bar --}}
 <div class="mt-5 flex flex-col md:flex-row md:items-center gap-3 w-full">
 {{-- Live Mode toggle --}}
 <label class="flex items-center justify-between md:justify-start gap-2 cursor-pointer select-none group w-full md:w-auto">
 <span class="text-xs font-bold uppercase tracking-wide"
 :class="liveMode ? 'text-cyan-600 ' : 'text-gray-500 '"
 x-text="liveMode ? 'Live Mode: ON' : 'Live Mode: OFF'"></span>
 <button type="button"
 class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
 :class="liveMode ? 'bg-cyan-500' : 'bg-gray-300 '"
 @click="liveMode ? (liveMode=false) : activateLiveMode()">
 <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
 :class="liveMode ? 'translate-x-4' : 'translate-x-0'"></span>
 </button>
 </label>

 <div class="flex flex-col md:flex-row md:items-center gap-3 w-full md:w-auto">
 <div class="flex items-center gap-2 w-full md:w-auto">
 <span class="text-xs font-semibold text-gray-500 w-10">From</span>
 <input type="date" x-model="fromDate" @change="liveMode=false"
 class="flex-1 w-full md:w-auto text-xs rounded-lg border border-gray-300 bg-white text-gray-800 px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-colors" />
 </div>
 <div class="flex items-center gap-2 w-full md:w-auto">
 <span class="text-xs font-semibold text-gray-500 w-10">To</span>
 <input type="date" x-model="toDate" @change="liveMode=false"
 class="flex-1 w-full md:w-auto text-xs rounded-lg border border-gray-300 bg-white text-gray-800 px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-colors" />
 </div>
 </div>

 <button @click="applyDateFilter()" :disabled="filterLoading"
 class="inline-flex justify-center items-center gap-1.5 px-4 py-1.5 text-xs font-bold rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white w-full md:w-auto transition-all active:scale-95 disabled:opacity-60 shadow-sm">
 <svg :class="filterLoading ? 'animate-spin' : 'hidden'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
 <flux:icon name="magnifying-glass" class="w-3.5 h-3.5" />
 <span x-text="filterLoading ? 'Memuat...' : 'Terapkan Filter'"></span>
 </button>
 </div>

 {{-- ═══════════════════ SUMMARY / DONUT BOX ═══════════════════ --}}
 <div class="mt-6 p-4 bg-gray-50 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-center gap-6 max-w-5xl mx-auto">
 
 {{-- EXTREME LEFT: Filter Overlay Button --}}
 <div class="flex-shrink-0 flex flex-col justify-center border-b md:border-b-0 md:border-r border-gray-200 pb-4 md:pb-0 md:pr-6 w-full md:w-auto text-center">
 <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Filter Form</div>
 <button @click="filterModalOpen = true"
 class="inline-flex justify-center items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-lg bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white shadow-md transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 ">
 <flux:icon name="funnel" class="w-3.5 h-3.5" />
 Atur Tampilan Form
 </button>
 <div class="text-[9px] text-gray-400 mt-2">Pilih/Hide form spesifik</div>
 </div>

 {{-- LEFT: Donut Diagram --}}
 <div class="relative flex-shrink-0 mx-auto md:mx-0" style="width: 130px; height: 130px;">
 <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.05));">
 <!-- Base circle (empty) -->
 <circle cx="50" cy="50" r="40" fill="transparent" stroke="#e5e7eb" stroke-width="16" class="" />
 
 <!-- Belum Terisi -->
 <circle cx="50" cy="50" r="40" fill="transparent" stroke="#cbd5e1" stroke-width="16" 
 class="transition-all duration-300 ease-out cursor-pointer hover:stroke-slate-400 "
 :stroke-dasharray="`${donut.belum.len} 251.327`" 
 :stroke-dashoffset="`-${donut.belum.offset}`"
 @mouseenter="showDonutHover('Belum Terisi', donut.belum.count, 'text-slate-600 ')" 
 @mouseleave="hideDonutHover()" />
 
 <!-- Good -->
 <circle cx="50" cy="50" r="40" fill="transparent" stroke="#34d399" stroke-width="16" 
 class="transition-all duration-300 ease-out cursor-pointer hover:stroke-emerald-500 "
 :stroke-dasharray="`${donut.good.len} 251.327`" 
 :stroke-dashoffset="`-${donut.good.offset}`"
 @mouseenter="showDonutHover('Good', donut.good.count, 'text-emerald-600 ')" 
 @mouseleave="hideDonutHover()" />
 
 <!-- Not Good -->
 <circle cx="50" cy="50" r="40" fill="transparent" stroke="#f87171" stroke-width="16" 
 class="transition-all duration-300 ease-out cursor-pointer hover:stroke-red-500 "
 :stroke-dasharray="`${donut.notGood.len} 251.327`" 
 :stroke-dashoffset="`-${donut.notGood.offset}`"
 @mouseenter="showDonutHover('Not Good', donut.notGood.count, 'text-red-600 ')" 
 @mouseleave="hideDonutHover()" />
 </svg>
 <!-- Center Label -->
 <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
 <span class="font-bold uppercase tracking-widest transition-colors" 
 style="font-size: 10px;"
 :class="donutHoverColor ? donutHoverColor : 'text-gray-400 '" 
 x-text="donutHoverLabel || 'Total'"></span>
 <span class="text-lg font-black text-gray-800 transition-colors leading-none mt-1" 
 :class="donutHoverColor ? donutHoverColor : ''"
 x-text="donutHoverValue || donut.total"></span>
 </div>
 </div>
 
 {{-- CENTER: Stats Grid --}}
 <div class="flex flex-col sm:flex-row gap-6 flex-1 w-full justify-center px-4">
 <div class="flex flex-col gap-2 w-full sm:w-1/2">
 <!-- Checklist Terisi -->
 <div class="flex items-center justify-between px-3 py-2 bg-cyan-50 border border-cyan-100 rounded-lg shadow-sm cursor-default hover:bg-cyan-100 transition-colors"
 @mouseenter="showDonutHover('Terisi', donut.total - donut.belum.count, 'text-cyan-600 ')"
 @mouseleave="hideDonutHover()">
 <span class="text-xs font-semibold text-cyan-700 ">Checklist Terisi</span>
 <span class="text-xs font-bold bg-white text-cyan-600 px-2 py-0.5 rounded shadow-sm" x-text="annualSummary.filled_count || 0"></span>
 </div>
 <!-- Belum Terisi -->
 <div class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg shadow-sm cursor-default hover:bg-slate-100 transition-colors"
 @mouseenter="showDonutHover('Belum Terisi', donut.belum.count, 'text-slate-600 ')"
 @mouseleave="hideDonutHover()">
 <span class="text-xs font-semibold text-slate-600 ">Belum Terisi</span>
 <span class="text-xs font-bold bg-white text-slate-600 px-2 py-0.5 rounded shadow-sm" x-text="annualSummary.unfilled_count || 0"></span>
 </div>
 </div>
 <div class="flex flex-col gap-2 w-full sm:w-1/2">
 <!-- Good -->
 <div class="flex items-center justify-between px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm cursor-default hover:bg-emerald-100 transition-colors"
 @mouseenter="showDonutHover('Good', donut.good.count, 'text-emerald-600 ')"
 @mouseleave="hideDonutHover()">
 <span class="text-xs font-semibold text-emerald-700 ">Good</span>
 <span class="text-xs font-bold bg-white text-emerald-600 px-2 py-0.5 rounded shadow-sm" x-text="annualSummary.good_count || 0"></span>
 </div>
 <!-- Not Good -->
 <div class="flex items-center justify-between px-3 py-2 bg-red-50 border border-red-100 rounded-lg shadow-sm cursor-default hover:bg-red-100 transition-colors"
 @mouseenter="showDonutHover('Not Good', donut.notGood.count, 'text-red-600 ')"
 @mouseleave="hideDonutHover()">
 <span class="text-xs font-semibold text-red-700 ">Not Good</span>
 <span class="text-xs font-bold bg-white text-red-600 px-2 py-0.5 rounded shadow-sm" x-text="annualSummary.not_good_count || 0"></span>
 </div>
 </div>
 </div>
 
 {{-- RIGHT: Export button --}}
 <div class="flex-shrink-0 flex flex-col justify-center border-t md:border-t-0 md:border-l border-gray-200 pt-4 md:pt-0 md:pl-6 w-full md:w-auto text-center">
 <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Export Data</div>
<button @click="downloadModalOpen = true"
 class="inline-flex justify-center items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-lg bg-green-600 hover:bg-green-700 active:bg-green-800 text-white shadow-md transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 ">
 <flux:icon name="arrow-down-tray" class="w-3.5 h-3.5" />
 Export Excel
 </button>
 <div class="text-[9px] text-gray-400 mt-2">Pilih rentang tanggal</div>
 </div>
 </div>
 </div>

 {{-- Table Wrapper --}}
 <div class="mt-6 px-6 sm:px-10 pb-6 sm:pb-10">
 <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm bg-white ">
 <div class="flex flex-wrap items-center justify-end gap-4 p-4 border-b border-gray-200 bg-gray-50 ">
 </div>

 <table class="w-full text-left divide-y divide-gray-200 " style="table-layout: fixed; min-width: 800px;">

 <thead>
 <tr class="bg-gray-50 ">
 <th class="py-4 px-5 font-bold text-sm text-gray-800 " style="width: 240px;">
 Form Checklist
 </th>
 <template x-for="m in monthsList" :key="m.key">
 <th class="py-4 px-2 font-semibold text-sm text-center text-gray-600 ">
 <div class="flex flex-col items-center gap-1">
 <span x-text="(() => {
     let parts = m.name.split(' ');
     let shortMonth = parts[0].substring(0, 3);
     let shortYear = parts[1] ? parts[1].substring(2) : '';
     return shortMonth + (shortYear ? ' \'' + shortYear : '');
 })()"></span>
 <button type="button" @click="openSummary(m.key, m.name)" class="text-cyan-600 hover:text-cyan-800 transition-colors" title="Ringkasan Bulan Ini">
 <flux:icon name="information-circle" class="w-4 h-4" />
 </button>
 </div>
 </th>
 </template>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 <template x-for="row in matrixRows" :key="row.id">
 <tr class="group hover:bg-gray-50 transition-colors"
 x-show="visibleIds.includes(row.id) && (!row.parent_id || (expandAll || row.ancestors.every(id => expandedRows.includes(id))))"
 x-cloak
 >
 <td class="py-3 px-5 text-sm" :style="`padding-left: ${1.25 + (row.level * 1.5)}rem;`">
 <div class="flex items-center gap-2 min-w-0">
 <template x-if="row.has_children">
 <button @click="toggleRow(row.id)"
 class="flex items-center gap-2 text-left group-hover:text-cyan-700 transition-colors min-w-0">
 <span class="flex-shrink-0 p-1 bg-transparent hover:bg-gray-200 rounded text-gray-500 transition-colors">
 <flux:icon name="chevron-down" class="w-4 h-4" x-show="expandAll || expandedRows.includes(row.id)" />
 <flux:icon name="chevron-right" class="w-4 h-4" x-show="!(expandAll || expandedRows.includes(row.id))" />
 </span>
 <span class="font-semibold text-gray-800 group-hover:text-cyan-700 truncate" :title="row.title" x-text="row.title"></span>
 </button>
 </template>
 <template x-if="!row.has_children">
 <div class="flex items-center gap-2 min-w-0 w-full">
 <div class="flex-shrink-0 w-6"></div>
 <span :class="row.level > 0 ? 'text-gray-700 text-xs' : 'font-semibold text-gray-800 '" class="truncate" :title="row.title" x-text="row.title"></span>
 </div>
 </template>
 </div>
 </td>

 <template x-for="m in monthsList" :key="m.key">
 <td class="py-3 px-2 text-center">
 <template x-if="!row.has_children">
 <button @click="openDetail(row.id, m.key, row.title)"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-cyan-500 "
 :title="cellStatus(row.id, m.key) === 'full' ? 'Sudah diisi — klik untuk lihat detail' : 'Belum diisi'">
 
 <span x-show="cellStatus(row.id, m.key) === 'full'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 ">
 <flux:icon name="check" class="w-4 h-4 stroke-[3]" />
 </span>
 <span x-show="cellStatus(row.id, m.key) === 'partial'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 ">
 <flux:icon name="minus" class="w-4 h-4 stroke-[3]" />
 </span>
 <span x-show="cellStatus(row.id, m.key) === 'none'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-400 ">
 <flux:icon name="x-mark" class="w-4 h-4 stroke-[3]" />
 </span>
 
 </button>
 </template>
 
 <template x-if="row.has_children">
 <div class="inline-flex items-center justify-center w-8 h-8 rounded-full"
 :title="cellStatus(row.id, m.key) === 'full' ? 'Semua sub-form sudah diisi' : (cellStatus(row.id, m.key) === 'partial' ? 'Sebagian sudah diisi' : 'Belum ada pengisian')">
 
 <span x-show="cellStatus(row.id, m.key) === 'full'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 ">
 <flux:icon name="check" class="w-4 h-4 stroke-[3]" />
 </span>
 <span x-show="cellStatus(row.id, m.key) === 'partial'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 ">
 <flux:icon name="minus" class="w-4 h-4 stroke-[3]" />
 </span>
 <span x-show="cellStatus(row.id, m.key) === 'none'"
 class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-400 ">
 <flux:icon name="x-mark" class="w-4 h-4 stroke-[3]" />
 </span>
 
 </div>
 </template>
 </td>
 </template>
 </tr>
 </template>
 </tbody>
 </table>
 </div>
 </div>

 {{-- Legend --}}
 <div class="mt-6">
 <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500 ">
 <div class="flex items-center gap-2">
 <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 ">
 <flux:icon name="check" class="w-3 h-3 stroke-[3]" />
 </div>
 <span>Semua sub-form sudah diisi</span>
 </div>
 <div class="flex items-center gap-2">
 <div class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 ">
 <flux:icon name="minus" class="w-3 h-3 stroke-[3]" />
 </div>
 <span>Sebagian sub-form sudah diisi</span>
 </div>
 <div class="flex items-center gap-2">
 <div class="w-5 h-5 rounded-full bg-red-50 flex items-center justify-center text-red-400 ">
 <flux:icon name="x-mark" class="w-3 h-3 stroke-[3]" />
 </div>
 <span>Belum ada pengisian</span>
 </div>
 </div>
 </div>
 </div>{{-- end legend --}}
 </div>{{-- end p-6 sm:p-10 wrapper --}}
 </div>{{-- end matrix card --}}

 </main>
 
 {{-- Footer --}}
 <footer class="border-t border-gray-200 bg-white py-4 px-6 text-center text-xs text-gray-500 w-full mt-auto">
 &copy; 2026 PT Tirta Investama - Plant Cianjur. Built by <a href="https://github.com/Spy06" target="_blank" rel="noopener noreferrer" class="font-medium text-cyan-600 hover:text-cyan-700 hover:underline">Farhan Hakim</a>. All Rights Reserved
 </footer>

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
 class="fixed left-0 right-0 bottom-0 flex items-center justify-center p-4 pb-8 z-[90]"
 :style="{ top: navbarHeight + 'px', background: 'rgba(0,0,0,0.65)' }"
 @keydown.escape.window="modal.open = false"
 @click.self="modal.open = false">

 <div class="relative w-full max-w-xl flex flex-col rounded-2xl bg-white shadow-2xl border border-gray-200 "
 style="max-height: 100%;"
 x-transition:enter="transition ease-out duration-200"
 x-transition:enter-start="opacity-0 scale-95"
 x-transition:enter-end="opacity-100 scale-100"
 @click.stop>

 {{-- Modal Header --}}
 <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 ">
 <div class="min-w-0 mr-4">
 <h3 class="text-base font-bold text-gray-900 truncate" x-text="modal.title"></h3>
 <p class="text-xs text-gray-500 mt-0.5">Detail rekaman submission (Live Form)</p>
 </div>
 <button @click="modal.open = false" class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
 <flux:icon name="x-mark" class="w-5 h-5" />
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
 <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
 <flux:icon name="exclamation-triangle" class="w-8 h-8 text-red-400" />
 </div>
 <p class="text-red-500 font-medium text-sm" x-text="modal.error"></p>
 </div>

 {{-- Empty state --}}
 <div x-show="!modal.loading && !modal.error && modal.submissions.length === 0" class="text-center py-12">
 <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
 <flux:icon name="document-magnifying-glass" class="w-8 h-8 text-gray-400" />
 </div>
 <p class="text-gray-500 font-medium">Belum ada submission untuk bulan ini.</p>
 </div>

 {{-- Submissions list --}}
 <div x-show="!modal.loading && modal.submissions.length > 0" class="space-y-4">
 <template x-for="(sub, idx) in modal.submissions" :key="sub.id">
 <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
 {{-- Submission Header --}}
 <div class="flex items-center justify-between px-4 py-3 bg-gray-50 ">
 <div class="flex items-center gap-3 min-w-0">
 <span class="inline-flex flex-shrink-0 items-center justify-center w-7 h-7 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold" x-text="idx + 1"></span>
 <div class="min-w-0">
 <p class="text-sm font-semibold text-gray-800 " x-text="sub.date"></p>
 <p class="text-xs text-gray-500 truncate">Diisi oleh: <span class="font-medium" x-text="sub.user_name"></span></p>
 </div>
 </div>
 <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 border border-emerald-200 ">
 <flux:icon name="check" class="w-3 h-3 stroke-[3]" />
 Submitted
 </span>
 </div>

 {{-- Answers --}}
 <div class="px-4 py-3 space-y-3">
 <template x-if="sub.answers && Object.keys(sub.answers).length > 0">
 <div class="space-y-2">
 <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Jawaban Checklist</p>
 <template x-for="(value, key) in sub.answers" :key="key">
 <div class="flex flex-col text-sm py-2 border-b border-gray-100 last:border-0">
 <span class="text-gray-500 text-xs font-medium mb-0.5 break-words" x-text="key"></span>
 <span class="text-gray-900 font-semibold break-words" x-text="value"></span>
 </div>
 </template>
 </div>
 </template>
 <template x-if="!sub.answers || Object.keys(sub.answers).length === 0">
 <p class="text-xs text-gray-400 italic">Tidak ada data isian tersimpan.</p>
 </template>

 {{-- Failures --}}
 <template x-if="sub.failures && sub.failures.length > 0">
 <div class="mt-2 pt-3 border-t border-red-100 ">
 <div class="flex items-center gap-2 mb-1.5">
 <flux:icon name="exclamation-triangle" class="w-3.5 h-3.5 text-red-500" />
 <p class="text-xs text-red-600 font-semibold uppercase tracking-wide">Bagian Tidak Standar (Failures)</p>
 </div>
 <ul class="list-disc pl-5 space-y-1">
 <template x-for="(failure, i) in sub.failures" :key="i">
 <li class="text-sm text-red-700 font-medium" x-text="failure"></li>
 </template>
 </ul>
 </div>
 </template>

 {{-- Notes --}}
 <div class="mt-1 pt-3 border-t border-gray-100 ">
 <div class="flex items-center gap-2 mb-1.5">
 <flux:icon name="chat-bubble-left-ellipsis" class="w-3.5 h-3.5 text-amber-500" />
 <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Catatan Responden</p>
 </div>
 <template x-if="sub.notes && sub.notes.trim() !== ''">
 <p class="text-sm text-gray-700 italic bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 whitespace-pre-wrap" x-text="sub.notes"></p>
 </template>
 <template x-if="!sub.notes || sub.notes.trim() === ''">
 <p class="text-xs text-gray-400 italic">Tidak ada catatan.</p>
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
 class="fixed left-0 right-0 bottom-0 flex items-center justify-center p-4 pb-8 z-[90]"
 :style="{ top: navbarHeight + 'px', background: 'rgba(0,0,0,0.65)' }"
 @keydown.escape.window="summaryModal.open = false"
 @click.self="summaryModal.open = false">

 <div class="relative w-full max-w-4xl flex flex-col rounded-2xl bg-white shadow-2xl border border-gray-200 "
 style="max-height: 100%;"
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

 {{-- Modal Header --}}
 <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 ">
 <div class="min-w-0 mr-4">
 <h3 class="text-lg font-bold text-gray-900 ">Ringkasan Checklist: <span x-text="summaryModal.monthName"></span></h3>
 <p class="text-sm text-gray-500 mt-0.5">
 Total Form: <span class="font-bold text-gray-800 " x-text="summaryModal.data?.total || 0"></span>
 </p>
 </div>
 <button @click="summaryModal.open = false" class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
 <flux:icon name="x-mark" class="w-5 h-5" />
 </button>
 </div>

 {{-- Tab Bar --}}
 <div class="flex-shrink-0 flex gap-1 px-6 pt-4 border-b border-gray-200 overflow-x-auto">
 <button @click="tab = 'overview'"
 class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
 :class="tab === 'overview' ? 'border-cyan-500 text-cyan-600 ' : 'border-transparent text-gray-500 hover:text-gray-700 '">
 Overview
 </button>
 <button @click="tab = 'unfilled'"
 class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
 :class="tab === 'unfilled' ? 'border-cyan-500 text-cyan-600 ' : 'border-transparent text-gray-500 hover:text-gray-700 '">
 Belum Diceklis (<span x-text="summaryModal.data?.unfilled_count || 0"></span>)
 </button>
 <button @click="tab = 'not_good'"
 class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
 :class="tab === 'not_good' ? 'border-cyan-500 text-cyan-600 ' : 'border-transparent text-gray-500 hover:text-gray-700 '">
 Not Good (<span x-text="summaryModal.data?.not_good_count || 0"></span>)
 </button>
 <button @click="tab = 'good'"
 class="flex-shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
 :class="tab === 'good' ? 'border-cyan-500 text-cyan-600 ' : 'border-transparent text-gray-500 hover:text-gray-700 '">
 Good (<span x-text="summaryModal.data?.good_count || 0"></span>)
 </button>
 </div>

 {{-- Modal Body --}}
 <div class="overflow-y-auto flex-1 p-6">
 {{-- Tab: Overview --}}
 <div x-show="tab === 'overview'" class="space-y-8">
 <div>
 <h4 class="text-sm font-bold text-gray-800 mb-3">Progress Pengisian</h4>
 <div class="flex items-center gap-4">
 <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden flex">
 <div class="h-full bg-cyan-500" :style="'width: ' + (summaryModal.data?.filled_percent || 0) + '%'"></div>
 </div>
 <span class="text-sm font-bold text-gray-700 w-12 text-right" x-text="(summaryModal.data?.filled_percent || 0) + '%'"></span>
 </div>
 <div class="flex justify-between mt-2 text-xs text-gray-500 ">
 <span><span x-text="summaryModal.data?.filled_count || 0"></span> Sudah Diisi</span>
 <span><span x-text="summaryModal.data?.unfilled_count || 0"></span> Belum Diisi</span>
 </div>
 </div>

 <div>
 <h4 class="text-sm font-bold text-gray-800 mb-3">Kondisi (Dari Form Terisi)</h4>
 <div class="flex items-center gap-4">
 <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden flex">
 <div class="h-full bg-emerald-500" :style="'width: ' + (summaryModal.data?.good_percent || 0) + '%'"></div>
 <div class="h-full bg-red-500" :style="'width: ' + (summaryModal.data?.not_good_percent || 0) + '%'"></div>
 </div>
 <span class="text-sm font-bold text-gray-700 w-12 text-right" x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>
 </div>
 <div class="flex justify-between mt-2 text-xs text-gray-500 ">
 <span class="text-emerald-600 font-medium">Good: <span x-text="summaryModal.data?.good_count || 0"></span> (<span x-text="(summaryModal.data?.good_percent || 0) + '%'"></span>)</span>
 <span class="text-red-500 font-medium">Not Good: <span x-text="summaryModal.data?.not_good_count || 0"></span> (<span x-text="(summaryModal.data?.not_good_percent || 0) + '%'"></span>)</span>
 </div>
 </div>
 </div>

 {{-- Tab: Unfilled --}}
 <div x-show="tab === 'unfilled'" class="space-y-2">
 <template x-if="!summaryModal.data?.unfilled_forms || summaryModal.data.unfilled_forms.length === 0">
 <p class="text-sm text-gray-500 italic">Semua checklist sudah terisi.</p>
 </template>
 <template x-for="item in pagedUnfilled" :key="item.id">
 <div class="flex items-start gap-3 px-3 py-2.5 rounded-lg border border-gray-200 bg-gray-50 ">
 <div class="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-gray-400 mt-1.5"></div>
 <div class="min-w-0">
 <p class="text-xs text-gray-500 truncate" x-text="item.path"></p>
 <p class="text-sm font-semibold text-gray-800 truncate" x-text="item.title"></p>
 </div>
 </div>
 </template>

 {{-- Pagination Unfilled --}}
 <template x-if="summaryModal.data?.unfilled_forms && summaryModal.data.unfilled_forms.length > pageSize">
 <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 ">
 <button @click="if(pageUnfilled > 1) pageUnfilled--" :disabled="pageUnfilled === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Prev</button>
 <span class="text-sm text-gray-500 " x-text="`Hal ${pageUnfilled} dari ${Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)}`"></span>
 <button @click="if(pageUnfilled < Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)) pageUnfilled++" :disabled="pageUnfilled === Math.ceil(summaryModal.data.unfilled_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Next</button>
 </div>
 </template>
 </div>

 {{-- Tab: Not Good --}}
 <div x-show="tab === 'not_good'" class="space-y-2">
 <template x-if="!summaryModal.data?.not_good_forms || summaryModal.data.not_good_forms.length === 0">
 <p class="text-sm text-gray-500 italic">Tidak ada checklist dengan kondisi Not Good.</p>
 </template>
 <template x-for="item in pagedNotGood" :key="item.id">
 <div class="px-3 py-2.5 rounded-lg border border-red-200 bg-red-50 ">
 <p class="text-xs text-gray-500 truncate" x-text="item.path"></p>
 <p class="text-sm font-semibold text-gray-800 truncate mb-1.5" x-text="item.title"></p>
 <div class="text-xs text-red-600 ">
 <p class="font-semibold uppercase mb-1 text-red-700 ">Bagian Tidak Standar (Failures):</p>
 <ul class="list-disc pl-4 space-y-0.5">
 <template x-for="(ns, i) in item.non_standard" :key="i">
 <li class="truncate" x-text="ns" :title="ns"></li>
 </template>
 </ul>
 </div>
 <template x-if="item.notes && item.notes.trim() !== ''">
 <div class="mt-2 text-xs text-gray-600 italic p-2 bg-red-100/50 rounded">
 <strong>Catatan:</strong> <span x-text="item.notes"></span>
 </div>
 </template>
 </div>
 </template>

 {{-- Pagination Not Good --}}
 <template x-if="summaryModal.data?.not_good_forms && summaryModal.data.not_good_forms.length > pageSize">
 <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 ">
 <button @click="if(pageNotGood > 1) pageNotGood--" :disabled="pageNotGood === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Prev</button>
 <span class="text-sm text-gray-500 " x-text="`Hal ${pageNotGood} dari ${Math.ceil(summaryModal.data.not_good_forms.length / pageSize)}`"></span>
 <button @click="if(pageNotGood < Math.ceil(summaryModal.data.not_good_forms.length / pageSize)) pageNotGood++" :disabled="pageNotGood === Math.ceil(summaryModal.data.not_good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Next</button>
 </div>
 </template>
 </div>

 {{-- Tab: Good --}}
 <div x-show="tab === 'good'" class="space-y-2">
 <template x-if="!summaryModal.data?.good_forms || summaryModal.data.good_forms.length === 0">
 <p class="text-sm text-gray-500 italic">Tidak ada checklist dengan kondisi Good.</p>
 </template>
 <template x-for="item in pagedGood" :key="item.id">
 <div class="px-3 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 ">
 <p class="text-xs text-gray-500 truncate" x-text="item.path"></p>
 <p class="text-sm font-semibold text-gray-800 truncate mb-1.5" x-text="item.title"></p>
 <div class="text-xs text-emerald-600 ">
 <p class="font-semibold uppercase mb-1">Bagian Standar (Checked):</p>
 <div class="flex flex-wrap gap-1">
 <template x-for="(st, i) in item.standard" :key="i">
 <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 max-w-[200px] truncate" x-text="st" :title="st"></span>
 </template>
 </div>
 </div>
 </div>
 </template>

 {{-- Pagination Good --}}
 <template x-if="summaryModal.data?.good_forms && summaryModal.data.good_forms.length > pageSize">
 <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 ">
 <button @click="if(pageGood > 1) pageGood--" :disabled="pageGood === 1" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Prev</button>
 <span class="text-sm text-gray-500 " x-text="`Hal ${pageGood} dari ${Math.ceil(summaryModal.data.good_forms.length / pageSize)}`"></span>
 <button @click="if(pageGood < Math.ceil(summaryModal.data.good_forms.length / pageSize)) pageGood++" :disabled="pageGood === Math.ceil(summaryModal.data.good_forms.length / pageSize)" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 transition-colors">Next</button>
 </div>
 </template>
 </div>

 </div>
 </div>
 </div>
 </template>

 {{-- Download Excel Modal --}}
 <template x-teleport="body">
 <div x-show="downloadModalOpen"
 x-cloak
 x-transition:enter="transition ease-out duration-200"
 x-transition:enter-start="opacity-0"
 x-transition:enter-end="opacity-100"
 x-transition:leave="transition ease-in duration-150"
 x-transition:leave-start="opacity-100"
 x-transition:leave-end="opacity-0"
 class="fixed inset-0 flex items-center justify-center p-4 z-50"
 style="background: rgba(0,0,0,0.65);"
 @keydown.escape.window="downloadModalOpen = false"
 @click.self="downloadModalOpen = false">

 <div class="relative w-full max-w-md flex flex-col rounded-2xl bg-white shadow-2xl border border-gray-200 "
 x-transition:enter="transition ease-out duration-200"
 x-transition:enter-start="opacity-0 scale-95"
 x-transition:enter-end="opacity-100 scale-100"
 @click.stop>
 
 <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 ">
 <div class="min-w-0 mr-4">
 <h3 class="text-lg font-bold text-gray-900 ">Download Data Excel</h3>
 <p class="text-sm text-gray-500 mt-0.5">Pilih rentang tanggal untuk diekspor.</p>
 </div>
 <button @click="downloadModalOpen = false" class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
 <flux:icon name="x-mark" class="w-5 h-5" />
 </button>
 </div>

 <form action="{{ route('guest.matrix.export') }}" method="GET" class="p-6">
 <div class="space-y-4">
 <div>
 <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Awal</label>
 <input type="date" name="start_date" required value="{{ date('Y-m-01') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5 " />
 </div>
 <div>
 <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Akhir</label>
 <input type="date" name="end_date" required value="{{ date('Y-m-d') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5 " />
 </div>
 </div>
 <div class="mt-6 flex justify-end gap-3">
 <button type="button" @click="downloadModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 ">
 Batal
 </button>
 <button type="submit" @click="setTimeout(() => downloadModalOpen = false, 500)" class="px-4 py-2 text-sm font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-md shadow-emerald-500/20 active:scale-95 transition-all ">
 Download Excel
 </button>
 </div>
 </form>
 </div>
 </div>
 </template>

 @persist('toast')
 <flux:toast.group>
 <flux:toast />
 </flux:toast.group>
 @endpersist

 {{-- ═══════════════════ FILTER MODAL (TELEPORTED) ═══════════════════ --}}
 <template x-teleport="body">
 <div x-show="filterModalOpen" class="fixed inset-0 flex items-center justify-center p-6 sm:p-10" style="z-index: 10001;" x-cloak>
 <div x-show="filterModalOpen" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
 x-transition:enter="transition-opacity ease-linear duration-300"
 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
 x-transition:leave="transition-opacity ease-linear duration-300"
 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
 @click="applyFilterChanges()"></div>

 <div x-show="filterModalOpen"
 class="relative w-full max-w-sm flex flex-col rounded-2xl bg-white shadow-2xl border border-gray-200"
 style="max-height: calc(100vh - 8rem);"
 x-transition:enter="transition ease-out duration-200"
 x-transition:enter-start="opacity-0 scale-95"
 x-transition:enter-end="opacity-100 scale-100"
 x-transition:leave="transition ease-in duration-150"
 x-transition:leave-start="opacity-100 scale-100"
 x-transition:leave-end="opacity-0 scale-95"
 @click.stop>
 
 <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 ">
 <h3 class="text-lg font-bold text-gray-800 ">Pilih Form Ditampilkan</h3>
 <button type="button" @click="applyFilterChanges()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
 <span class="sr-only">Close</span>
 <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
 </button>
 </div>

 <div class="px-5 py-4 overflow-y-auto flex-1 text-sm text-gray-700 ">
 <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded-lg text-xs">
 <strong>Catatan:</strong> Form yang di-hide tidak akan ditarik datanya saat menekan tombol "Ambil Data", dan barisnya disembunyikan.
 </div>

 <div class="space-y-1">
 @foreach($matrix as $row)
 <div class="flex items-center py-1.5 hover:bg-gray-50 rounded-md transition-colors"
 style="padding-left: {{ $row['level'] * 1.5 }}rem;">
 <label class="flex items-center gap-2.5 cursor-pointer flex-1">
 <input type="checkbox"
 class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 "
 :checked="visibleIds.includes({{ $row['id'] }})"
 @change="toggleFilterNode({{ $row['id'] }}, $event.target.checked)" />
 <span class="text-sm {{ $row['is_category'] ? 'font-semibold text-gray-900 ' : 'text-gray-700 ' }}">
 @if($row['is_category']) 📁 @else 📄 @endif
 {{ $row['title'] }}
 </span>
 </label>
 </div>
 @endforeach
 </div>
 </div>

 <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end">
 <button type="button" @click="applyFilterChanges()"
 class="px-5 py-2 text-sm font-bold rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white shadow-md active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500">
 Terapkan & Muat Ulang
 </button>
 </div>
 </div>
 </div>
 </template>

 @fluxScripts
 </body>
</html>
