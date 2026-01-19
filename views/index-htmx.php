<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= token() ?>">
    <title>SmartStock AI - Koperasi Desa</title>
    
    <!-- <script src="https://cdn.tailwindcss.com"></script>    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script> -->

    <script src="<?= assets('js/htmx.min.js') ?>"></script> 
    <script defer src="<?= assets('/js/alpinejs3.min.js') ?>"></script>
    <!-- <script src="<?= assets('/js/cdn-tailwindcss.js') ?>"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', 
        }
    </script> -->

    <link rel="stylesheet" href="<?= assets('/assets/css/app.css') ?>">

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
    <link rel="stylesheet" href="<?= assets('/assets/fontawesome-web/css/all.min.css') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">    

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('router', {
                // Mapping Utama (Sinkronisasi Label)
                map: {
                    'dashboard': 'Dashboard',
                    'inventory': 'Pupuk & Benih',
                    'assets': 'Alat Berat',
                    'rental': 'Sewa Alat Berat',
                    'rental-drone': 'Sewa Drone'
                },
                // Fungsi pembantu untuk breadcrumb
                getLabel(segment) {
                    return this.map[segment] || segment.replace(/-/g, ' ');
                }
            })
        });
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Pengaturan Font Global Responsif */
        html {
            /* Base font 16px di desktop, akan mengecil secara halus di mobile */
            font-size: 16px;
        }

        @media (max-width: 768px) {
            html {
                font-size: 14px; /* Menurunkan base font agar semua elemen mengecil otomatis */
            }
            
            /* Membatasi ukuran teks yang terlalu besar di mobile */
            h1 { font-size: 1.5rem !important; }
            h2 { font-size: 1.25rem !important; }
            h3 { font-size: 1.1rem !important; }
            
            /* Mengoptimalkan padding tabel di mobile agar tidak sesak */
            td, th {
                padding: 12px 8px !important;
                font-size: 12px !important;
            }

            /* Memaksa elemen italic/kecil tidak terlalu dominan */
            .text-sm {
                font-size: 0.8rem !important;
            }
        }

        /* Utilitas tambahan untuk teks yang sangat panjang agar tidak merusak layout */
        .truncate-mobile {
            @apply truncate;
            max-width: 150px;
        }

        /* Container loading bar */
        #loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 9999;
            background-color: #4f46e5; /* warna dasar */
            display: none; /* sembunyi secara default */
        }

        /* Garis yang bergerak */
        #loading-bar::before {
            content: "";
            display: block;
            height: 100%;
            width: 0%;
            background-color: #4f46e5; /* warna indigo-600 */
            box-shadow: 0 0 10px #6366f1;
            transition: width 0.4s ease;
        }

        /* Tampilkan dan jalankan saat HTMX sedang request */
        .htmx-request#loading-bar,
        .htmx-request #loading-bar {
            display: block;
        }

        .htmx-request#loading-bar::before,
        .htmx-request #loading-bar::before {
            animation: progress-animation 2s infinite ease-in-out;
        }

        @keyframes progress-animation {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        .htmx-indicator {
            opacity: 0;
            transition: opacity 200ms ease-in;
        }
        .htmx-request.htmx-indicator, .htmx-request .htmx-indicator {
            opacity: 1;
        }
    </style>
    <style>
        /* Paksa transisi untuk properti margin-left */
        .transition-margin {
            transition-property: margin-left, width;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
    </style>
    <style>
        @keyframes pulse-bg {
            0% { background-color: #f1f5f9; }
            50% { background-color: #e2e8f0; }
            100% { background-color: #f1f5f9; }
        }
        .skeleton-row td div {
            height: 20px;
            border-radius: 4px;
            animation: pulse-bg 1.5s infinite ease-in-out;
        }
        /* Sembunyikan skeleton secara default */
        #skeleton-loader { display: none; }
        /* Tampilkan saat HTMX sedang proses */
        .htmx-request #skeleton-loader { display: table-row-group; }
        /* Sembunyikan data asli saat HTMX sedang proses */
        .htmx-request #inventory-data { display: none; }

        /* Animasi highlight kuning ke transparan */
        @keyframes highlightFade {
            0% { background-color: #fef08a; } /* Warna kuning indigo-50 atau yellow-200 */
            100% { background-color: transparent; }
        }

        .row-updated {
            animation: highlightFade 2s ease-out;
        }

        /* Transisi halus untuk konten yang baru masuk */
        .fade-in-content {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .htmx-swapping {
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.3s ease-out;
        }
        
        #inventory-table-body.htmx-request {
            opacity: 0.5;
            transition: opacity 0.2s ease;
        }

        /* Efek saat data sedang dimuat */
        .htmx-adding {
            opacity: 0;
        }

        #activity-table-body.htmx-request {
            opacity: 0.5;
            filter: blur(1px);
            transition: all 200ms ease-in;
        }
    </style>
</head>

<body class="bg-slate-50 font-[Inter] overflow-x-hidden">

    <div id="loading-bar" class="htmx-indicator progress-bar"></div>

    <div x-data="{ 
            sidebarOpen: true, 
            mobileMenuOpen: false,
            // Kita simpan path saat ini ke dalam state Alpine
            currentPath: window.location.pathname,

            isActive(path) {
                // Menghapus query string jika ada (seperti ?id=1) untuk perbandingan bersih
                const cleanPath = this.currentPath.split('?')[0];
                //console.log(cleanPath);

                // Memastikan path diakhiri dengan string rute tersebut
                // Contoh: '/htmx/rental2' akan cocok dengan 'rental2' tapi tidak dengan 'rental'
                return cleanPath.endsWith('/' + path) || cleanPath === path;

                // Cek apakah path yang diminta ada di dalam URL saat ini
                //console.log(this.currentPath.includes(path));
                //return this.currentPath.includes(path);
            },

            updatePath() {
                this.currentPath = window.location.pathname;
            }
        }" 
        @htmx:pushed-into-history.window="currentPath = window.location.pathname"
        @popstate.window="updatePath()"
        class="min-h-screen">

        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[45] lg:hidden">
        </div>

        <div class="md:hidden fixed top-4 left-4 z-[60]">
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="w-11 h-11 flex items-center justify-center bg-slate-900 text-white rounded-xl shadow-lg active:scale-95 transition-transform">
                <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'" style="width: 1.25rem; text-align: center;"></i>
            </button>
        </div>
    
        <aside
            :class="{
                'translate-x-0': mobileMenuOpen, 
                '-translate-x-full': !mobileMenuOpen,
                'md:translate-x-0': true,
                'md:w-64': sidebarOpen,
                'md:w-20': !sidebarOpen
            }"
            class="fixed inset-y-0 left-0 z-50 bg-slate-900 transition-all duration-300 ease-in-out flex flex-col shadow-2xl shadow-slate-900/50 w-64 md:flex text-white">
            
            <div class="h-20 flex items-center px-6 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-cube text-white"></i>
                    </div>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" x-transition class="font-bold text-white tracking-tight">
                        SmartStock <span class="text-indigo-400">AI</span>
                    </span>
                </div>
            </div>
    
            <nav class="flex-1 p-4 space-y-2 overflow-x-hidden overflow-y-auto">
                <button @click="mobileMenuOpen = false" 
                        hx-get="<?= url('/htmx/dashboard') ?>" 
                        hx-target="#main-content" 
                        hx-indicator="#loading-bar" 
                        hx-push-url="true"
                        :class="isActive('htmx') || isActive('htmx/dashboard') ? 'text-white bg-indigo-600 rounded-xl transition shadow-lg shadow-indigo-600/20' 
                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center p-3 rounded-xl transition">
                    <i class="fas fa-th-large w-6 text-center"></i>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Dashboard</span>
                </button>
                
                <div class="pt-4 pb-2 px-3">
                    <p x-show="sidebarOpen || (window.innerWidth < 768)" class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Inventory</p>
                </div>
    
                <button @click="mobileMenuOpen = false" 
                        hx-get="<?= url('/htmx/inventory') ?>" 
                        hx-target="#main-content" 
                        hx-indicator="#loading-bar" 
                        hx-push-url="true"
                        :class="isActive('htmx/inventory') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center p-3 rounded-xl transition">
                    <i class="fas fa-seedling w-6 text-center"></i>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Pupuk & Benih</span>
                </button>

                <button @click="mobileMenuOpen = false" 
                        hx-get="<?= url('/htmx/assets') ?>" 
                        hx-target="#main-content" 
                        hx-indicator="#loading-bar" 
                        hx-push-url="true"
                        :class="isActive('htmx/assets') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center p-3 rounded-xl transition">
                    <i class="fas fa-tractor w-6 text-center"></i>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Alat Berat</span>
                </button>

                <div class="pt-4 pb-2 px-3">
                    <p x-show="sidebarOpen || (window.innerWidth < 768)" class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Sewa Alat</p>
                </div>

                <button @click="mobileMenuOpen = false" 
                        hx-get="<?= url('/htmx/rental') ?>" 
                        hx-target="#main-content" 
                        hx-indicator="#loading-bar" 
                        hx-push-url="true"
                        :class="isActive('htmx/rental') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center p-3 rounded-xl transition">
                    <i class="fas fa-tractor w-6 text-center"></i>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Sewa Alat Berat</span>
                </button>

                <button @click="mobileMenuOpen = false" 
                        hx-get="<?= url('/htmx/rental-drone') ?>" 
                        hx-target="#main-content" 
                        hx-indicator="#loading-bar" 
                        hx-push-url="true"
                        :class="isActive('htmx/rental-drone') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center p-3 rounded-xl transition">
                    <i class="fas fa-tractor w-6 text-center"></i>
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Sewa Drone</span>
                </button>
            </nav>
    
            <button @click="sidebarOpen = !sidebarOpen" class="hidden md:flex p-4 bg-slate-950 text-slate-500 hover:text-white transition justify-center">
                <i class="fas" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>
        </aside>

        <button 
            @click="sidebarOpen = !sidebarOpen" 
            class="hidden md:flex fixed top-7 z-[100] bg-indigo-600 text-white w-6 h-6 rounded-full items-center justify-center border-2 border-slate-950 hover:bg-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-500/40"
            :style="sidebarOpen ? 'left: 15rem' : 'left: 4rem'">
            <i class="fas fa-chevron-left text-[10px] transition-transform duration-300" 
               :class="!sidebarOpen ? 'rotate-180' : ''"></i>
        </button>

        <div :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'" class="transition-margin min-h-screen flex flex-col">
            <header class="h-20 bg-white/80 backdrop-blur-md border-b sticky top-0 px-8 flex items-center justify-between z-40 ml-[30px] md:ml-0 transition-all duration-300">
                <div class="relative w-96 group" x-data="{ showResults: true }" @click.away="showResults = false">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" 
                           name="search"
                           @focus="showResults = true"
                           placeholder="Cari pupuk, petani, atau alat..." 
                           hx-post="<?= url('/backend/search') ?>" 
                           hx-trigger="keyup changed delay:500ms" 
                           hx-target="#search-results"
                           class="w-full bg-slate-100 border-none rounded-2xl py-2.5 pl-12 pr-4 focus:ring-2 focus:ring-indigo-500 transition-all">
                    
                   <div id="search-results" 
                         x-show="showResults" 
                         x-transition
                         class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden z-50">
                    </div>
                </div>

                <div class="flex items-center gap-2 md:gap-5 text-sm font-medium">
    
                    <button class="relative w-10 h-10 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition-all shrink-0">
                        <i class="far fa-bell text-lg"></i>
                        <span class="absolute top-2 right-2 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white">
                            3
                        </span>
                    </button>

                    <div class="hidden md:block w-px h-6 bg-slate-200"></div>

                    <div class="flex items-center gap-3 md:gap-4">
                        <div class="text-right hidden lg:block">
                            <p class="text-slate-900 leading-tight font-bold">Admin Koperasi</p>
                            <p class="text-indigo-500 text-[9px] font-black tracking-widest uppercase">Premium Partner</p>
                        </div>
                        
                        <div class="relative shrink-0">
                            <img src="https://ui-avatars.com/api/?name=AK&background=6366f1&color=fff" 
                                 class="w-10 h-10 rounded-full border-2 border-indigo-100 object-cover aspect-square shadow-sm">
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full shadow-sm animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-8 flex-1">
                <nav x-data="{ 
                        segments: [],
                        updateSegments() {
                            this.segments = window.location.pathname.split('/').filter(p => p && p !== 'htmx' && p !== 'smartstock');
                        }
                    }" 
                    x-init="updateSegments()"
                    @htmx:after-settle.window="updateSegments()"
                    class="flex items-center gap-2 mb-6 text-[10px] font-black uppercase tracking-[0.15em] text-indigo-600/80">
                    
                    <a hx-get="<?= url('/htmx') ?>" hx-push-url="true" hx-target="#main-content" class="cursor-pointer hover:text-indigo-900 transition flex items-center gap-2">
                       <i class="fas fa-home text-[9px]"></i> DASHBOARD
                    </a>

                    <template x-for="(segment, index) in segments" :key="index">
                        <div x-show="segment !== 'dashboard'" class="flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[7px] text-slate-300"></i>
                            <span x-text="$store.router.getLabel(segment)" 
                                  :class="index === segments.length - 1 ? 'text-slate-400' : 'text-indigo-600/80'"></span>
                        </div>
                    </template>
                </nav>

                <div id="main-content" hx-indicator="#loading-bar">
                    <?php 
                    // dd($paged_data);
                    $this->include('htmx.dashboard', 
                        [
                        'isHome' => true, 
                        'total_items' => $total_items, 
                        'total_pages' => $total_pages, 
                        'page' => $page, 
                        'offset' => $offset, 
                        'paged_data' => $paged_data, 
                    ]); ?>
                </div>
            </main>

        </div>
    </div>

    
    <?php $this->include('htmx.modals.inventory.add'); ?>
    <?php $this->include('htmx.modals.inventory.edit'); ?>

    <!-- Global Modals -->
    <?php $this->include('htmx.modals.confirm_delete'); ?>
    <?php $this->include('htmx.modals.alert'); ?>

    <script>
        document.body.addEventListener('htmx:afterOnLoad', function(evt) {
            // Cari elemen yang punya x-data dengan fungsi updatePath
            const sidebar = document.querySelector('[x-data]');
            if (sidebar && sidebar.__x_data_stack) {
                // Panggil fungsi updatePath milik Alpine secara manual
                sidebar._x_dataStack[0].updatePath();
            }
        });
    </script>
</body>
</html>