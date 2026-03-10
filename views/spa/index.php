<!DOCTYPE html>
<html lang="id" x-data="{ isDark: $persist(false) }" :class="isDark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= token() ?>">
    <title>AgriSmart - AI Inventory</title>
    <!-- <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> -->

    <script src="<?= assets('js/htmx.min.js') ?>"></script>    
    <script defer src="<?= assets('/js/persist@3.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script defer src="<?= assets('/js/alpinejs3.min.js') ?>"></script>
    <!-- <script src="<?= assets('/js/cdn-tailwindcss.js') ?>"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', 
        }
    </script> -->

    <link rel="stylesheet" href="<?= assets('/assets/css/app.css') ?>">

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
    <link rel="stylesheet" href="<?= assets('/assets/fontawesome-web/css/all.min.css') ?>">
    
    <style>
        /* Letakkan di dalam <style> di index.html */
        .htmx-indicator {
            display: none;
        }
        .htmx-request .htmx-indicator {
            display: flex;
        }
        .htmx-request#tab-content {
            filter: blur(1px);
            opacity: 0.5;
        }
    </style>
</head>

<body class="bg-white dark:bg-black text-slate-900 dark:text-white transition-colors duration-500 font-sans antialiased">

    <nav class="fixed w-full z-50 bg-white/80 dark:bg-black/80 backdrop-blur-md border-b border-slate-100 dark:border-slate-800 transition-colors duration-500">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            
            <div class="flex items-center gap-2">
                <div class="bg-indigo-600 p-2 rounded-lg text-white shadow-lg shadow-indigo-200 dark:shadow-none">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-800 dark:text-white transition-colors">
                    AgriSmart
                </span>
            </div>

            <div class="hidden md:flex gap-8 text-sm font-semibold text-slate-600 dark:text-slate-400">
                <a href="#hero" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Beranda
                </a>
                <a href="#fitur" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Fitur AI
                </a>
                <a href="#demo" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Demo Sistem
                </a>
                <a href="#subscribe" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Daftar Gratis
                </a>
                <a href="<?= url('/notification') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    FCM Demo
                </a>
                <a href="<?= url('/dashboard') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Dashboard HTML
                </a>
                <a href="<?= url('/extra') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    Framework Features
                </a>
            </div>

            <div class="flex items-center gap-4">
                <a href="<?= url('/login') ?>" class="inline-block">
                    <button class="bg-slate-900 dark:bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-800 dark:hover:bg-indigo-500 transition-all active:scale-95 shadow-lg shadow-slate-200 dark:shadow-none">
                        Mulai Gratis
                    </button>
                </a>
                
                <button @click="isDark = !isDark" 
                        class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white transition-colors">
                    <i class="fas" :class="isDark ? 'fa-sun' : 'fa-moon'"></i>
                </button>
            </div>
        </div>
    </nav>

    <section id="hero" 
         class="pt-32 pb-20 px-6 bg-white dark:bg-black transition-colors duration-500">
        <div class="max-w-7xl mx-auto text-center">
            <span class="bg-indigo-50 text-indigo-600 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest">
                Teknologi Pertanian 4.0
            </span>

            <h1 class="mt-8 text-5xl md:text-7xl font-bold text-slate-900 dark:text-white tracking-tight leading-tight">
                Kelola Stok Tani <br> <span class="text-indigo-600 underline decoration-indigo-200">Lebih Cerdas.</span>
            </h1>

            <p class="mt-6 text-slate-500 dark:text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed">
                Platform manajemen inventaris pertama yang menggunakan AI untuk memprediksi kapan pupuk dan benih Anda akan habis.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <a href="<?= url('/login') ?>" class="inline-block">
                    <button class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold shadow-xl shadow-indigo-200 dark:shadow-indigo-900/20 hover:scale-105 transition-all active:scale-95">
                        Coba Dashboard AI
                    </button>
                </a>
                
                <!-- <button class="bg-white dark:bg-black border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-8 py-4 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all active:scale-95">
                    Lihat Video Demo
                </button> -->
                <div x-data="{ openVideo: false }">
    
                    <button @click="openVideo = true" 
                            class="bg-white dark:bg-black border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-8 py-4 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all active:scale-95 flex items-center gap-2 group">
                        <i class="fas fa-play-circle text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform"></i>
                        <span>Lihat Video Demo</span>
                    </button>

                    <template x-teleport="body">
                        <div x-show="openVideo" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md">
                            
                            <div @click.away="openVideo = false" 
                                 x-show="openVideo"
                                 x-transition:enter="transition ease-out duration-300 transform"
                                 x-transition:enter-start="opacity-0 scale-90"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="relative w-full max-w-5xl aspect-video bg-black rounded-[2rem] overflow-hidden shadow-2xl border border-white/10">
                                
                                <button @click="openVideo = false" 
                                        class="absolute top-4 right-4 z-10 w-10 h-10 bg-black/50 text-white rounded-full flex items-center justify-center hover:bg-rose-500 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>

                                <template x-if="openVideo">
                                    <iframe class="w-full h-full" 
                                            src="https://www.youtube.com/embed/wgn32fooZEs?autoplay=0" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                    </iframe>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-24 bg-white dark:bg-black transition-colors duration-500">
        <div class="max-w-7xl mx-auto px-6">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-3">Keunggulan Sistem</h2>
                <h3 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-6 tracking-tight">
                    Teknologi AI yang Memahami <span class="text-indigo-600 dark:text-indigo-400">Lahan Anda.</span>
                </h3>
                <p class="text-slate-500 dark:text-slate-400 text-lg leading-relaxed">
                    Kami menggabungkan data satelit dan algoritma machine learning untuk memberikan wawasan yang belum pernah ada sebelumnya.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/10 hover:border-indigo-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Prediksi Stok Otomatis</h4>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        AI kami menganalisis pola pemakaian tahunan untuk memberikan peringatan dini sebelum stok pupuk Anda habis.
                    </p>
                </div>

                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/10 hover:border-emerald-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-cloud-sun text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Sinkronisasi Cuaca</h4>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        Menyesuaikan jadwal pemupukan berdasarkan ramalan cuaca presisi tinggi untuk mencegah pemborosan akibat hujan.
                    </p>
                </div>

                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/10 hover:border-amber-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Optimasi Biaya</h4>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        Rekomendasi pembelian dari supplier dengan harga terendah saat tren pasar sedang turun.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section id="demo" class="py-20 bg-white dark:bg-slate-950 border-y border-slate-100 dark:border-slate-900 transition-colors">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Eksplorasi Dashboard</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Pilih modul untuk melihat cara kerja HTMX kami.</p>
            </div>

            <div class="flex p-1.5 bg-slate-100 dark:bg-slate-900 rounded-2xl gap-2 mb-8 transition-colors">
                <button hx-get="/demo/inventory" hx-target="#tab-content" hx-indicator="#loading"
                        class="flex-1 py-3 text-sm font-bold rounded-xl transition-all"
                        :class="isDark ? 'text-slate-400 hover:text-white' : 'bg-white shadow-sm text-indigo-600'">
                    <i class="fas fa-boxes mr-2"></i> Inventaris
                </button>
                <button hx-get="/demo/prediction" hx-target="#tab-content" hx-indicator="#loading"
                        class="flex-1 py-3 text-sm font-bold rounded-xl transition-all"
                        :class="isDark ? 'text-slate-400 hover:text-white' : 'bg-white shadow-sm text-indigo-600'">
                    <i class="fas fa-chart-line mr-2"></i> Prediksi AI
                </button>
                <button hx-get="/demo/suppliers" hx-target="#tab-content" hx-indicator="#loading"
                        class="flex-1 py-3 text-sm font-bold rounded-xl transition-all"
                        :class="isDark ? 'text-slate-400 hover:text-white' : 'bg-white shadow-sm text-indigo-600'">
                    <i class="fas fa-truck-moving mr-2"></i> Supplier
                </button>
            </div>

            <div class="relative min-h-[400px] bg-slate-50 dark:bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800 p-8 transition-all">
                <div id="loading" class="htmx-indicator absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-[2px] z-10 flex items-center justify-center rounded-3xl">
                    <i class="fas fa-circle-notch animate-spin text-3xl text-indigo-600"></i>
                </div>

                <div id="tab-content" class="dark:text-white">
                    <div class="text-center py-20 text-slate-300 dark:text-slate-700">
                        <i class="fas fa-hand-pointer text-4xl mb-4"></i>
                        <p class="font-medium">Klik tab di atas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="subscribe" class="py-20 px-6 max-w-3xl mx-auto">
        <div class="bg-slate-900 rounded-[2.5rem] p-10 md:p-16 text-center text-white relative overflow-hidden">
            
            <div id="subscribe-content" class="animate-in fade-in duration-700">
                <h2 class="text-3xl font-bold mb-4">Konsultasi Gratis</h2>
                <p class="text-slate-400 mb-8">Tinggalkan email untuk mendapatkan analisa lahan pertanian Anda.</p>
                
                <form hx-post="/subscribe" 
                      hx-target="#subscribe-content" 
                      hx-swap="outerHTML" 
                      class="flex flex-col sm:flex-row gap-3">
                    <?= csrfField() ?>
                    <input type="email" name="email" required placeholder="Alamat email Anda..." 
                        autocomplete="off" 
                        class="flex-1 px-6 py-4 rounded-2xl bg-white/10 dark:bg-black/10 border border-white/10 outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <button type="submit" class="bg-indigo-600 px-8 py-4 rounded-2xl font-bold hover:bg-indigo-500 transition flex items-center justify-center gap-2">
                        <span>Hubungi Saya</span>
                        <i class="fas fa-circle-notch animate-spin htmx-indicator"></i>
                    </button>
                </form>
            </div>
            
        </div>
    </section>

    <footer class="bg-white dark:bg-black pt-20 pb-10 border-t border-slate-100 dark:border-white/5 transition-colors duration-500">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="bg-indigo-600 p-2 rounded-lg text-white">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-slate-800 dark:text-white">AgriSmart</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed mb-6">
                        Solusi manajemen inventaris pertanian berbasis AI pertama di Indonesia. Membantu petani mengoptimalkan stok dan biaya secara presisi.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 transition-all">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 transition-all">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-6">Produk</h4>
                    <ul class="space-y-4 text-sm">
                        <li><a href="#fitur" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Fitur Utama</a></li>
                        <li><a href="#demo" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Demo Dashboard</a></li>
                        <li><a href="#" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Update Harga</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-6">Dukungan</h4>
                    <ul class="space-y-4 text-sm">
                        <li><a href="#" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Pusat Bantuan</a></li>
                        <li><a href="#" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Kebijakan Privasi</a></li>
                        <li><a href="#" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Syarat & Ketentuan</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-6">Status Sistem</h4>
                    <div class="bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/10 p-4 rounded-2xl">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Semua Sistem Normal</span>
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-500 leading-tight">
                            AI Model v4.2 aktif. Sinkronisasi cuaca diperbarui 5 menit yang lalu.
                        </p>
                    </div>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-100 dark:border-white/5 flex flex-col md:row justify-between items-center gap-4">
                <p class="text-xs text-slate-400 dark:text-slate-600 font-medium">
                    &copy; 2026 AgriSmart AI Indonesia. Seluruh hak cipta dilindungi.
                </p>
                <div class="flex items-center gap-2 text-[10px] text-slate-400 dark:text-slate-600 uppercase font-black tracking-widest">
                    <span>Dibuat dengan</span>
                    <i class="fas fa-heart text-rose-500"></i>
                    <span>Untuk Pertanian Indonesia</span>
                </div>
            </div>
        </div>
    </footer>

    <div x-data="{ showTop: false }" 
         @scroll.window="showTop = (window.pageYOffset > 500) ? true : false"
         class="fixed bottom-24 right-6 z-50">
        
        <button @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                x-show="showTop"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-10"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-10"
                class="group relative flex items-center justify-center w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 shadow-xl hover:bg-indigo-600 dark:hover:bg-indigo-600 transition-all duration-300">
            
            <i class="fas fa-arrow-up text-slate-600 dark:text-slate-400 group-hover:text-white transition-colors"></i>
            
            <span class="absolute right-full mr-4 px-2 py-1 rounded bg-slate-800 text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                Kembali ke Atas
            </span>
        </button>
    </div>


    <template id="skeleton-template">
        <div class="animate-pulse flex flex-col gap-4">
            <div class="h-8 bg-slate-200 dark:bg-slate-800 rounded-lg w-1/3"></div>
            <div class="grid grid-cols-2 gap-4">
                <div class="h-32 bg-slate-200 dark:bg-slate-800 rounded-[2rem]"></div>
                <div class="h-32 bg-slate-200 dark:bg-slate-800 rounded-[2rem]"></div>
            </div>
        </div>
    </template>

    <script>
        document.body.addEventListener('htmx:beforeRequest', function(evt) {
            if(evt.detail.target.id === 'tab-content') {
                const skeleton = document.getElementById('skeleton-template').innerHTML;
                document.getElementById('tab-content').innerHTML = skeleton;
            }
        });

        document.body.addEventListener('htmx:configRequest', (event) => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
            event.detail.headers['X-Theme-Mode'] = document.body.classList.contains('dark') ? 'dark' : 'light';
        });

        document.body.addEventListener('celebrate', () => {
            // Konfigurasi durasi dan jumlah partikel
            const duration = 3 * 1000;
            const end = Date.now() + duration;

            (function frame() {
                // Meluncurkan confetti dari sisi kiri
                confetti({
                    particleCount: 3,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: ['#4f46e5', '#10b981'] // Warna Indigo & Emerald sesuai tema AgriSmart
                });
                // Meluncurkan confetti dari sisi kanan
                confetti({
                    particleCount: 3,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: ['#4f46e5', '#10b981']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        });
    </script>
</body>
</html>