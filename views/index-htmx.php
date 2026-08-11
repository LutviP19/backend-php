<!DOCTYPE html>
<html lang="id" x-data="{ isDark: $persist(false) }" :class="isDark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= token() ?>">
    <title>SmartStock AI - Koperasi Desa</title>

    <script src="<?= assets('js/htmx.min.js') ?>"></script> 
    <script>
        // Tell HTMX to execute the <script> tag in the partial response
        htmx.config.evalIndicator = true;
    </script>
    <script defer src="<?= assets('/js/persist@3.min.js') ?>"></script>
    <script defer src="<?= assets('/js/alpinejs3.min.js') ?>"></script>
    
    <link rel="stylesheet" href="<?= assets('/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= assets('/assets/fontawesome-web/css/all.min.css') ?>">    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">    

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('router', {
                // Main Mapping (Label Synchronization)
                map: {
                    'dashboard': 'Dashboard',
                    'inventory': 'Pupuk & Benih',
                    'assets': 'Alat Berat & Drone',
                    'rental': 'Sewa Alat Berat',
                    'rental-drone': 'Sewa Drone'
                },
                // Helper function for breadcrumb
                getLabel(segment) {
                    return this.map[segment] || segment.replace(/-/g, ' ');
                }
            })
        });
    </script>
</head>

<body class="bg-slate-50 font-[Inter] overflow-x-hidden">

    <div id="loading-bar" class="htmx-indicator progress-bar"></div>

    <div x-data="{ 
            sidebarOpen: true, 
            mobileMenuOpen: false,
            currentPath: window.location.pathname,

            isActive(path) {
                // Remove query strings if present (such as ?id=1) for clean comparison
                const cleanPath = this.currentPath.split('?')[0];
                //console.log(cleanPath);

                return cleanPath.endsWith('/' + path) || cleanPath === path;
            },

            updatePath() {
                this.currentPath = window.location.pathname;
            }
        }" 
        @htmx:pushed-into-history.window="updatePath()"
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
                        Smart<span class="text-amber-400">Stock</span> <span class="text-indigo-400">AI</span>
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
                    <span x-show="sidebarOpen || (window.innerWidth < 768)" class="ml-3 font-medium">Alat Berat & Drone</span>
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
                    <i class="fas fa-plane-up w-6 text-center"></i>
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
            <header class="h-20 bg-white dark:bg-dark-navy-header backdrop-blur-md border-b border-slate-200 dark:border-slate-800/60 top-0 px-8 flex items-center justify-between z-40 ml-[30px] md:ml-0 transition-colors duration-300">
                <!-- Search Bar -->
                <div class="relative w-96 group" x-data="{ showResults: true }" @click.away="showResults = false">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                    <input type="text" 
                        name="search"
                        @focus="showResults = true"
                        placeholder="Cari pupuk, petani, atau alat..." 
                        hx-post="<?= url('/backend/search') ?>" 
                        hx-trigger="keyup changed delay:500ms" 
                        hx-target="#search-results"
                        class="w-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 border-none rounded-2xl py-2.5 pl-12 pr-4 focus:ring-2 focus:ring-indigo-500 transition-all">
                    
                <div id="search-results" 
                        x-show="showResults" 
                        x-transition
                        class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50">
                    </div>
                </div>

                <!-- User Action Controls -->
                <div class="flex items-center gap-2 md:gap-4 text-sm font-medium">

                    <!-- TOGGLE DARK MODE BUTTON (Alpine.js) -->
                    <div>
                        <button @click="isDark = !isDark" 
                                type="button"
                                title="Ganti Mode Tampilan"
                                class="w-10 h-10 flex items-center justify-center text-slate-400 dark:text-slate-400 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-all shrink-0">
                            <!-- Icon Bulan (Tampil saat Light Mode) -->
                            <i x-show="!isDark" class="far fa-moon text-lg"></i>
                            <!-- Icon Matahari (Tampil saat Dark Mode) -->
                            <i x-show="isDark" x-cloak class="far fa-sun text-lg text-amber-400"></i>
                        </button>
                    </div>

                    <!-- 1. Dropdown Notifikasi -->
                    <div class="relative" x-data="{ openNotifications: false }" @click.away="openNotifications = false">
                        <button @click="openNotifications = !openNotifications"
                                class="relative w-10 h-10 flex items-center justify-center text-slate-400 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-full transition-all shrink-0">
                            <i class="far fa-bell text-lg"></i>
                            <span class="absolute top-2 right-2 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white dark:border-slate-900">
                                3
                            </span>
                        </button>

                        <!-- Panel Notifikasi -->
                        <div x-show="openNotifications"
                            x-cloak
                            style="display: none;"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                            class="absolute right-0 mt-3 w-[calc(100vw-2rem)] sm:w-120 max-w-[480px] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-50">
                            
                            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">Notifikasi</h3>
                                    <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold text-[11px] rounded-full">3 Baru</span>
                                </div>
                                <button class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline transition-all">
                                    Tandai semua dibaca
                                </button>
                            </div>

                            <div class="max-h-[380px] overflow-y-auto divide-y divide-slate-50 dark:divide-slate-700/40">
                                <a href="#" class="flex items-start gap-4 p-4 hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition-colors group">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <i class="fas fa-truck-loading text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="font-semibold text-xs text-slate-800 dark:text-slate-200">Pengiriman Diproses</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">5 menit yang lalu</span>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                            Pengiriman Pupuk Urea sebanyak <span class="font-semibold text-slate-700 dark:text-slate-300">50 Sak</span> ke Kelompok Tani B telah diproses.
                                        </p>
                                    </div>
                                </a>

                                <a href="#" class="flex items-start gap-4 p-4 hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition-colors group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <i class="fas fa-wallet text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="font-semibold text-xs text-slate-800 dark:text-slate-200">Pembayaran Diterima</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">1 jam yang lalu</span>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                            Pembayaran simpanan wajib sebesar <span class="font-semibold text-slate-700 dark:text-slate-300">Rp 250.000</span> dari Bpk. Budi berhasil.
                                        </p>
                                    </div>
                                </a>
                            </div>

                            <a href="#" class="block p-3.5 text-center text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-slate-50 dark:bg-slate-800 hover:bg-indigo-50/50 dark:hover:bg-slate-700/50 transition-colors border-t border-slate-100 dark:border-slate-700">
                                Lihat Semua Notifikasi <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <div class="hidden md:block w-px h-6 bg-slate-200 dark:bg-slate-700"></div>

                    <!-- 2. Dropdown Profil User -->
                    <div class="relative" x-data="{ openProfile: false }" @click.away="openProfile = false">
                        <button @click="openProfile = !openProfile" 
                                class="flex items-center gap-3 md:gap-4 p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition-all focus:outline-none">
                            <div class="text-right hidden lg:block">
                                <p class="text-slate-900 dark:text-slate-100 leading-tight font-bold">Admin Koperasi</p>
                                <p class="text-indigo-500 dark:text-indigo-400 text-[9px] font-black tracking-widest uppercase">Premium Partner</p>
                            </div>
                            
                            <div class="relative shrink-0">
                                <img src="https://ui-avatars.com/api/?name=AK&background=6366f1&color=fff" 
                                    class="w-10 h-10 rounded-full border-2 border-indigo-100 dark:border-indigo-900/50 object-cover aspect-square shadow-sm">
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full shadow-sm animate-pulse"></div>
                            </div>
                            
                            <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" 
                            :class="openProfile ? 'rotate-180' : ''"></i>
                        </button>

                        <!-- Panel Profil Menu -->
                        <div x-show="openProfile"
                            x-cloak
                            style="display: none;"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                            class="absolute right-0 mt-3 w-60 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-2 z-50">
                            
                            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 lg:hidden">
                                <p class="text-slate-900 dark:text-slate-100 font-bold">Admin Koperasi</p>
                                <p class="text-indigo-500 dark:text-indigo-400 text-[9px] font-black tracking-widest uppercase">Premium Partner</p>
                            </div>

                            <div class="py-1">
                                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50/60 dark:hover:bg-slate-700/50 transition-all font-medium">
                                    <i class="far fa-user w-4 text-center"></i>
                                    <span>Pengaturan Profil</span>
                                </a>
                                
                                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50/60 dark:hover:bg-slate-700/50 transition-all font-medium">
                                    <i class="fas fa-sliders-h w-4 text-center"></i>
                                    <span>Pengaturan Sistem</span>
                                </a>
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-700 pt-1 mt-1">
                                <a href="<?= url('/logout') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-all font-medium">
                                    <i class="fas fa-sign-out-alt w-4 text-center"></i>
                                    <span>Keluar (Logout)</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <main class="p-8 flex-1">
                <nav x-data="{ 
                        segments: [],
                        updateSegments() {
                            this.segments = window.location.pathname.split('/').filter(p => p && p !== 'htmx');
                        }
                    }" 
                    x-init="updateSegments()"
                    @htmx:after-settle.window="updateSegments()"
                    class="flex items-center gap-2 mb-6 text-[10px] font-black uppercase tracking-[0.15em] text-indigo-600/80">
                    
                    <a hx-get="<?= url('/htmx/dashboard') ?>" hx-push-url="true" hx-target="#main-content" class="cursor-pointer hover:text-indigo-900 transition flex items-center gap-2">
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
                    $this->include(
                        'htmx.dashboard',
                        [
                            'isHome' => true,
                            'total_items' => $total_items,
                            'total_pages' => $total_pages,
                            'page' => $page,
                            'offset' => $offset,
                            'paged_data' => $paged_data,
                        ]
                    ); ?>
                </div>
            </main>

        </div>
    </div>

    
    <?php $this->include('htmx.modals.inventory.add'); ?>
    <?php $this->include('htmx.modals.inventory.edit'); ?>

    <!-- Global Modals -->
    <?php $this->include('htmx.modals.confirm_delete'); ?>
    <?php $this->include('htmx.modals.alert'); ?>

    <script src="<?= assets('/js/app.js') ?>"></script>
</body>
</html>