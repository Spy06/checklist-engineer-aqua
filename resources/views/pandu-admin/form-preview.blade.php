<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Preview Form: {{ $slug }} | Pandu Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-zinc-950 text-gray-900 dark:text-gray-100 antialiased font-sans"
      x-data="{
          syncing: false,
          synced: {{ $existingForm ? 'true' : 'false' }},
          syncOutput: '',
          syncError: '',
          syncForm() {
              this.syncing = true;
              this.syncOutput = '';
              this.syncError = '';
              fetch('{{ route('pandu-admin.form.sync') }}', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                  },
                  body: JSON.stringify({
                      slug: '{{ $slug }}',
                      google_form_id: '{{ $formConfig['id'] }}'
                  })
              })
              .then(r => r.json())
              .then(data => {
                  this.syncing = false;
                  if (data.success) {
                      this.synced = true;
                      this.syncOutput = data.output || data.message;
                  } else {
                      this.syncError = data.message || 'Sinkronisasi gagal.';
                  }
              })
              .catch(err => {
                  this.syncing = false;
                  this.syncError = 'Terjadi kesalahan jaringan: ' + err.message;
              });
          }
      }">

    {{-- Navbar --}}
    <header class="sticky top-0 z-50 border-b border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
        <div class="flex h-16 items-center justify-between px-6 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('pandu-admin.dashboard') }}" class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                </a>
                <img src="{{ asset('GKL3_Aqua - Koleksilogo.com.png') }}" alt="Logo AQUA" class="h-8 w-auto object-contain" />
                <div>
                    <span class="block text-sm font-bold text-gray-900 dark:text-gray-100 tracking-tight">Pandu <span class="text-cyan-600 dark:text-cyan-500">Admin</span></span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">Preview Form: <code class="font-mono bg-gray-100 dark:bg-zinc-800 px-1 rounded text-cyan-600">{{ $slug }}</code></span>
                </div>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-gray-500 hover:text-cyan-600 dark:text-gray-400 dark:hover:text-cyan-400 transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                Lihat Matriks
            </a>
        </div>
    </header>

    <main class="p-6 max-w-7xl mx-auto w-full pb-32 space-y-8">

        {{-- Page Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight">Preview <span class="text-cyan-600 dark:text-cyan-500">Form Baru</span></h1>
                <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">Periksa struktur dan respons form sebelum menambahkannya ke Matriks.</p>
            </div>
            {{-- Step indicator --}}
            <div class="flex items-center gap-2 text-xs font-semibold">
                <div class="flex items-center gap-1.5 text-emerald-600">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">1</div>
                    <span>Konfigurasi</span>
                </div>
                <div class="w-8 h-px bg-gray-300 dark:bg-zinc-700"></div>
                <div class="flex items-center gap-1.5" :class="synced ? 'text-emerald-600' : 'text-cyan-600'">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="synced ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600'">2</div>
                    <span>Preview & Sinkronkan</span>
                </div>
                <div class="w-8 h-px bg-gray-300 dark:bg-zinc-700"></div>
                <div class="flex items-center gap-1.5" :class="synced ? 'text-cyan-600' : 'text-gray-400'">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="synced ? 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600' : 'bg-gray-100 dark:bg-zinc-800 text-gray-400'">3</div>
                    <span>Finalisasi</span>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- LEFT: Form Structure from Google Forms API --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-800/30 flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Struktur Google Form</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($structureTree) }} elemen ditemukan di Google Forms API</p>
                    </div>
                </div>
                <div class="p-4 space-y-1.5 max-h-[60vh] overflow-y-auto">
                    @forelse($structureTree as $item)
                        @if($item['type'] === 'section')
                            <div class="flex items-center gap-2 pt-3 pb-1">
                                <div class="h-px flex-1 bg-gray-200 dark:bg-zinc-700"></div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2">{{ $item['title'] }}</span>
                                <div class="h-px flex-1 bg-gray-200 dark:bg-zinc-700"></div>
                            </div>
                        @elseif($item['type'] === 'choice')
                            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30">
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 shrink-0 px-1.5 py-0.5 text-[9px] font-bold uppercase rounded bg-emerald-200 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-300">Pilihan</span>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $item['title'] }}</p>
                                        @if(!empty($item['opts']))
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                @foreach($item['opts'] as $opt)
                                                    <span class="px-2 py-0.5 rounded-full bg-white dark:bg-zinc-800 border border-emerald-200 dark:border-emerald-800 text-[10px] text-gray-600 dark:text-gray-400">{{ $opt }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($item['type'] === 'checklist')
                            <div class="p-3 rounded-xl bg-cyan-50 dark:bg-cyan-900/10 border border-cyan-100 dark:border-cyan-900/30">
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 shrink-0 px-1.5 py-0.5 text-[9px] font-bold uppercase rounded bg-cyan-200 dark:bg-cyan-800 text-cyan-700 dark:text-cyan-300">Checklist</span>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $item['title'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-zinc-800/30 border border-gray-200 dark:border-zinc-700/50">
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $item['title'] }}</p>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-gray-400 text-center py-8">Tidak ada elemen ditemukan.</p>
                    @endforelse
                </div>
            </div>

            {{-- RIGHT: DB Structure (after sync) + Responses --}}
            <div class="space-y-6">

                {{-- DB Structure after sync --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-800/30 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m0 5.625c0 2.278 3.694 4.125 8.25 4.125s8.25-1.847 8.25-4.125" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold">Struktur di Database</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Hasil setelah sinkronisasi berjalan</p>
                            </div>
                        </div>
                        {{-- Sync button --}}
                        <button @click="syncForm()"
                                :disabled="syncing"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all disabled:opacity-50"
                                :class="synced ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm'">
                            <svg :class="syncing ? 'animate-spin' : ''" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            <span x-text="syncing ? 'Menyinkronkan...' : (synced ? '✓ Tersinkronkan' : 'Sinkronkan Sekarang')"></span>
                        </button>
                    </div>
                    <div class="p-4 max-h-[30vh] overflow-y-auto">
                        {{-- Sync output --}}
                        <div x-show="syncOutput" class="mb-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-xs font-mono text-emerald-700 dark:text-emerald-300 whitespace-pre-wrap" x-text="syncOutput"></div>
                        <div x-show="syncError" class="mb-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300" x-text="syncError"></div>

                        @if($existingForm)
                            {{-- Show current DB tree --}}
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 p-2 rounded-lg bg-violet-50 dark:bg-violet-900/10">
                                    <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    <span class="text-xs font-bold text-violet-700 dark:text-violet-300">{{ $existingForm->title }}</span>
                                    <span class="text-[10px] text-violet-500 font-mono">/ {{ $existingForm->slug }}</span>
                                </div>
                                @foreach($existingForm->children as $child)
                                    <div class="ml-4 flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800/30">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 016 3H5.25m0 0A2.25 2.25 0 013 5.25v13.5A2.25 2.25 0 005.25 21H9m0-4.5v4.5m0 0h4.5M9 21h4.5m0 0V19.5m0 1.5v-1.5m0 1.5H18m0 0V5.25A2.25 2.25 0 0015.75 3H15m0 0H9" /></svg>
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $child->title }}</span>
                                        @if($child->is_category)
                                            <span class="text-[9px] bg-amber-100 dark:bg-amber-900/30 text-amber-600 px-1.5 rounded font-bold">Kategori</span>
                                        @else
                                            <span class="text-[9px] bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 px-1.5 rounded font-bold">Leaf</span>
                                        @endif
                                        @if($child->children->count() > 0)
                                            @foreach($child->children as $grandchild)
                                                <div class="ml-4 text-[10px] text-gray-500">↳ {{ $grandchild->title }}</div>
                                            @endforeach
                                        @endif
                                    </div>
                                    @foreach($child->children as $grandchild)
                                        <div class="ml-8 flex items-center gap-2 p-1.5 rounded-lg bg-gray-50 dark:bg-zinc-800/20">
                                            <span class="text-[10px] text-gray-500">↳ {{ $grandchild->title }}</span>
                                            <span class="text-[9px] {{ $grandchild->is_category ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600' }} px-1.5 rounded font-bold">{{ $grandchild->is_category ? 'Kategori' : 'Leaf' }}</span>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6 text-sm text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                Belum tersinkronkan. Klik "Sinkronkan Sekarang" untuk membuat struktur di database.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recent Responses --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-800/30 flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold">Respons Terbaru</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($previewResponses) }} respons ditampilkan (maks. 20)</p>
                        </div>
                    </div>
                    <div class="p-4 max-h-[30vh] overflow-y-auto space-y-3">
                        @forelse($previewResponses as $resp)
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-zinc-800/30 border border-gray-200 dark:border-zinc-700/50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-mono text-gray-400 truncate">ID: {{ Str::limit($resp['response_id'], 20) }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $resp['submitted_at'] ? \Carbon\Carbon::parse($resp['submitted_at'])->format('d/m/Y H:i') : '-' }}</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($resp['answers'] as $value)
                                        @if($value)
                                            <div class="flex items-start gap-1.5">
                                                <svg class="w-3 h-3 mt-0.5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                <span class="text-[10px] text-gray-600 dark:text-gray-400">{{ $value }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-sm text-gray-400">
                                Belum ada respons yang masuk ke Google Form ini.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </main>

    {{-- Sticky Finalize Bar --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-gray-200 dark:border-zinc-800 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] z-40 px-6 py-4">
        <form action="{{ route('pandu-admin.form.finalize') }}" method="POST"
              class="flex flex-col sm:flex-row items-center justify-between gap-4 max-w-7xl mx-auto">
            @csrf
            <input type="hidden" name="slug" value="{{ $slug }}">

            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Nama Tampilan di Matriks <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ $formConfig['name'] ?? '' }}" placeholder="Mis: Pompa Air Workshop"
                           class="w-64 text-sm rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Form Induk di Matriks</label>
                    <select name="parent_slug"
                            class="w-52 text-sm rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                        <option value="">— Tanpa induk (root) —</option>
                        @foreach($rootForms as $rf)
                            <option value="{{ $rf->slug }}" {{ ($formConfig['parent_slug'] ?? '') === $rf->slug ? 'selected' : '' }}>{{ $rf->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('pandu-admin.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    Kembali
                </a>
                <button type="submit"
                        :disabled="!synced"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white rounded-xl shadow-lg transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="synced ? 'bg-cyan-600 hover:bg-cyan-700 focus:ring-cyan-500' : 'bg-gray-400'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span x-text="synced ? 'Finalisasi & Tampilkan di Matriks' : 'Sinkronkan dulu sebelum finalisasi'"></span>
                </button>
            </div>
        </form>
    </div>

</body>
</html>
