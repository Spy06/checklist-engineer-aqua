<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Pandu Admin</title>
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
<body class="min-h-screen bg-gray-50 dark:bg-zinc-950 text-gray-900 dark:text-gray-100 antialiased font-sans">
    
    {{-- Navbar --}}
    <header class="sticky top-0 z-50 border-b border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
        <div class="flex h-16 items-center justify-between px-6 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-3">
                <img src="{{ asset('GKL3_Aqua - Koleksilogo.com.png') }}" alt="Logo AQUA" class="h-8 w-auto object-contain" />
                <span class="block text-sm font-bold text-gray-900 dark:text-gray-100 tracking-tight">Pandu <span class="text-cyan-600 dark:text-cyan-500">Admin</span></span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-gray-500 hover:text-cyan-600 dark:text-gray-400 dark:hover:text-cyan-400 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    Lihat Matriks
                </a>
                <form action="{{ route('pandu-admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="p-6 max-w-5xl mx-auto w-full pb-20">
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight">Konfigurasi <span class="text-cyan-600 dark:text-cyan-500">Sistem</span></h1>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">Kelola kredensial Google API dan daftar scraping Google Forms di sini.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <h4 class="text-sm font-bold">Berhasil</h4>
                    <p class="text-sm mt-1">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <div>
                    <h4 class="text-sm font-bold">Gagal</h4>
                    <p class="text-sm mt-1">{{ session('error') }}</p>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 shadow-sm">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pandu-admin.config.update') }}" method="POST"
              x-data="{
                  forms: {{ json_encode($config['forms'] ?? []) }}
              }">
            @csrf

            {{-- SECTION: API CREDENTIALS --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 mb-8 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-800/30 flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Google API Credentials</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">JSON service account yang di-encode Base64</p>
                    </div>
                </div>
                <div class="p-6">
                    <label for="google_api_base64" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Base64 Encoded JSON</label>
                    <textarea id="google_api_base64" name="google_api_base64" rows="5"
                              class="w-full font-mono text-xs rounded-xl border border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-950 px-4 py-3 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors shadow-inner"
                              placeholder="eyJ0eXBlIjoic2VydmljZV9hY2NvdW50Ii..."></textarea>
                    
                    <div class="mt-3 text-xs text-gray-500 flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>Hanya diisi jika Anda ingin mengganti kredensial API. Biarkan kosong untuk tetap menggunakan kredensial yang lama (atau dari konfigurasi bawaan).</span>
                    </div>
                </div>
            </div>

            {{-- SECTION: GOOGLE FORMS LIST --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 mb-8 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-800/30 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold">Daftar Link Google Forms</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pemetaan antara ID Google Form dan Slug Checklist di Database</p>
                        </div>
                    </div>
                    <button type="button" @click="forms.unshift({id: '', slug: ''})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-400 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Tambah Form
                    </button>
                </div>
                
                <div class="p-6">
                    <template x-if="forms.length === 0">
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                            Belum ada form yang ditambahkan. Klik "Tambah Form".
                        </div>
                    </template>
                    
                    <div class="space-y-3">
                        <template x-for="(form, index) in forms" :key="index">
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center p-4 bg-gray-50 dark:bg-zinc-800/20 border border-gray-200 dark:border-zinc-800 rounded-xl group transition-colors hover:bg-gray-100 dark:hover:bg-zinc-800/40">
                                
                                <div class="w-full sm:w-1/2">
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 sm:hidden">Google Form ID</label>
                                    <input type="text" x-model="form.id" :name="`forms[${index}][id]`" required placeholder="Contoh: 1TsEx5jisS8tlPV..."
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors" />
                                </div>
                                
                                <div class="w-full sm:w-5/12">
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 sm:hidden">Slug Database</label>
                                    <input type="text" x-model="form.slug" :name="`forms[${index}][slug]`" required placeholder="Contoh: mobile-ladder"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors font-mono" />
                                </div>
                                
                                <div class="w-full sm:w-1/12 flex justify-end">
                                    <button type="button" @click="forms.splice(index, 1)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Hapus Form">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Floating Save Button --}}
            <div class="fixed bottom-0 left-0 right-0 p-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border-t border-gray-200 dark:border-zinc-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-40 flex justify-center">
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-8 py-3 text-sm font-bold text-white bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 rounded-xl shadow-lg transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" /></svg>
                    Simpan Perubahan
                </button>
            </div>
            
        </form>
    </main>
    
</body>
</html>
