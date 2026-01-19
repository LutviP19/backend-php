<style>
@keyframes zoom-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-in {
    animation: zoom-in 0.4s ease-out forwards;
}
</style>

<div class="animate-in fade-in slide-in-from-bottom-4 duration-500" 
    x-init="$watch('openModal', value => {
        if (value) document.body.style.overflow = 'hidden';
        else document.body.style.overflow = 'auto';
    })" 
    x-data="{ 
            search: '', 
            status: '', 
            errors: {},
            open: false, // Select Dropdown
            openModal: false, // Modal logs
            label: 'Semua Status', // State untuk teks dropdown
            viewMode: 'grid',

            resetAssets() {
                // 1. Reset State Alpine
                this.search = '';
                this.status = '';
                this.label = 'Semua Status'; // Sekarang ini akan bekerja
                this.open = false;
                errors = {};

                // 2. Trigger HTMX untuk memuat ulang data bersih
                this.$nextTick(() => {
                    // Gunakan htmx.ajax untuk memastikan request bersih terkirim ke server
                    htmx.ajax('GET', '<?= url('/data/assets-render') ?>', {
                        target: '#asset-container',
                        values: { 
                            search: '', 
                            status_filter: '', 
                            view_mode: this.viewMode 
                        }
                    });
                });
            }
        }"
    @reset-filter.window="search = ''; status = ''; open: false; label= 'Semua Status';">
    
    <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-6 mb-8">
        <div class="text-left">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">
                Manajemen Asset
            </h1>
            <p class="text-sm md:text-base text-slate-500 italic mt-1">
                Pantau kesehatan traktor &amp; drone secara real-time.
            </p>
        </div>
        
        <button 
            hx-get="<?= url('/data/asset-add') ?>" 
            hx-target="#log-details-content" 
            @click="$dispatch('open-log-modal')" 
            class="w-full md:w-auto bg-indigo-600 text-white px-6 py-4 md:py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:scale-105 active:scale-95 transition-all flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Tambah Unit Baru
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col items-center">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 w-full">Status Armada</p>
            
            <div class="relative w-full h-[180px] max-w-[180px]">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Distribusi Kesehatan Unit (%)</p>
            <div class="flex flex-wrap gap-3 mb-4">
                <div class="flex items-center gap-1 text-[9px] font-bold text-emerald-500 uppercase tracking-tighter">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Normal
                </div>
                <div class="flex items-center gap-1 text-[9px] font-bold text-amber-500 uppercase tracking-tighter">
                    <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Waspada
                </div>
                <div class="flex items-center gap-1 text-[9px] font-bold text-rose-500 uppercase tracking-tighter">
                    <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div> Kritis
                </div>
            </div>
            <div class="h-[180px] w-full">
                <canvas id="healthChart"></canvas>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6 mb-8">
        <div class="text-left">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">
                Asset Alat Berat
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="flex bg-slate-100 p-1 rounded-2xl mr-2">
                <button 
                    @click="
                        viewMode = 'grid'; 
                        htmx.ajax('GET', '<?= url('/data/assets-render') ?>', {
                            target: '#asset-container',
                            values: { view_mode: 'grid', search: search, status_filter: status }
                        })
                    "
                    :class="viewMode === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                    <i class="fas fa-th-large"></i> Grid
                </button>

                <button 
                    @click="
                        viewMode = 'list'; 
                        htmx.ajax('GET', '<?= url('/data/assets-render') ?>', {
                            target: '#asset-container',
                            values: { view_mode: 'list', search: search, status_filter: status }
                        })
                    "
                    :class="viewMode === 'list' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                    <i class="fas fa-list"></i> List
                </button>
            </div>

            <div class="relative group flex-1 md:flex-none">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors duration-300">
                    <i class="fas fa-search text-sm"></i>
                </div>
                <input 
                    type="text" 
                    x-model="search"
                    name="search"
                    id="asset-search-input"
                    hx-get="<?= url('/data/assets-render') ?>"
                    hx-trigger="keyup changed delay:500ms, reload"
                    hx-target="#asset-container"
                    hx-include="[name='status_filter'], [name='view_mode']" 
                    hx-indicator="#search-loading" 
                    placeholder="Cari ID Unit..." 
                    autocomplete="off" 
                    class="pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm w-full md:w-48"
                >
                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                    <i id="search-loading" class="fas fa-circle-notch fa-spin text-indigo-500 text-xs htmx-indicator"></i>
                    <template x-if="search.length > 0">
                        <button @click="search = ''; $nextTick(() => htmx.trigger('#asset-search-input', 'keyup'))" class="text-slate-300 hover:text-rose-500 transition-colors">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </template>
                </div>
            </div>

            <input type="hidden" name="view_mode" :value="viewMode">

            <div class="relative">
                <button 
                    @click="open = !open"
                    @click.outside="open = false"
                    type="button"
                    class="flex items-center justify-between gap-3 px-5 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 hover:border-indigo-500 transition-all min-w-[160px]"
                >
                    <span class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" :class="status === '' ? 'bg-slate-300' : (status === 'ready' ? 'bg-emerald-500' : (status === 'working' ? 'bg-blue-500' : 'bg-rose-500'))"></div>
                        <span x-text="label"></span>
                    </span>
                    <i class="fas fa-chevron-down text-[10px] transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div 
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 mt-2 w-56 bg-white border border-slate-100 rounded-2xl shadow-xl shadow-slate-200/50 z-50 p-2"
                >
                    <button 
                        @click="status = ''; label = 'Semua Status'; open = false; $nextTick(() => htmx.trigger('#asset-search-input', 'reload'))"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all"
                    >
                        <div class="w-2 h-2 rounded-full bg-slate-300"></div> Semua Status
                    </button>

                    <button 
                        @click="status = 'ready'; label = 'Ready'; open = false; $nextTick(() => htmx.trigger('#asset-search-input', 'reload'))"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-all"
                    >
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Ready
                    </button>

                    <button 
                        @click="status = 'working'; label = 'Working'; open = false; $nextTick(() => htmx.trigger('#asset-search-input', 'reload'))"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-all"
                    >
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div> Working
                    </button>

                    <button 
                        @click="status = 'maintenance'; label = 'Maintenance'; open = false; $nextTick(() => htmx.trigger('#asset-search-input', 'reload'))"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all"
                    >
                        <div class="w-2 h-2 rounded-full bg-rose-500"></div> Maintenance
                    </button>
                </div>

                <input type="hidden" name="status_filter" :value="status">
            </div>

            <button 
                x-show="search.length > 0 || status !== ''"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-x-2"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                @click="resetAssets()"
                type="button"
                class="flex items-center gap-2 px-4 py-3 rounded-2xl text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 hover:text-rose-600 transition-all border border-rose-100 shadow-sm shadow-rose-100/50"
            >
                <i class="fas fa-sync-alt text-[10px] animate-none group-hover:rotate-180 transition-transform duration-500"></i>
                Reset Filter
            </button>
        </div>
    </div>

    <div id="asset-container" 
         hx-get="<?= url('/data/assets-render') ?>" 
         hx-trigger="load"
         hx-include="[name='view_mode'], #asset-search-input, [name='status_filter']"
         class="min-h-[400px]">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-indigo-500 text-3xl"></i>
                <p class="text-slate-400 text-sm mt-4 font-medium tracking-wide">Memuat data armada...</p>
            </div>
        </div>
    </div>

    <div id="asset-count-badge" class="hidden">
    </div>

    <template x-teleport="body">
        <div x-data="{ 
                openModal: false,
                count: 0,
                errors: {}, // Tempat menyimpan pesan error
            }"
            @open-log-modal.window="openModal = true; count = 0;" 
            x-show="openModal" 
            x-cloak 
            class="fixed inset-0 top-0 left-0 w-full h-full z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm shadow-2xl"
        >

            <div 
                x-show="openModal" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="bg-white max-w-lg max-h-[90vh] mx-4 overflow-x-hidden overflow-y-auto relative rounded-[2.5rem] shadow-2xl w-full z-100"
            >
                <div id="log-details-content">
                    <div class="p-20 flex flex-col items-center justify-center gap-4 text-center">
                        <div class="w-12 h-12 border-4 border-slate-100 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-loose">
                            Mengambil Data <br> Mohon Tunggu...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div 
            x-data="{ 
                show: false, 
                message: '', 
                type: 'success' 
            }"
            @toast.window="
                message = $event.detail.message; 
                type = $event.detail.type || 'success'; 
                show = true; 
                setTimeout(() => show = false, 3000)
            "
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-20px] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            class="fixed top-6 right-6 z-[10000] w-full max-w-[320px]"
            x-cloak
        >
            <div class="bg-white border border-slate-100 rounded-[2rem] p-4 shadow-2xl flex items-center gap-4 border-l-4"
                 :class="type === 'success' ? 'border-l-emerald-500' : 'border-l-rose-500'">
                
                <div :class="type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'" 
                     class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                    <i class="fas" :class="type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                </div>
                
                <div class="flex-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-0.5" x-text="type"></p>
                    <p class="text-xs font-bold text-slate-700" x-text="message"></p>
                </div>

                <button @click="show = false" class="text-slate-300 hover:text-slate-500 transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>
    </template>
</div>

<?php
$stats = [
    'ready'   => count(array_filter($filtered, fn($u) => $u['status'] === 'ready')),
    'maint'   => count(array_filter($filtered, fn($u) => $u['status'] === 'maintenance')),
    'working' => count(array_filter($filtered, fn($u) => $u['status'] === 'working')),
    'unitNames' => array_column($filtered, 'id'),
    'unitHealths' => array_column($filtered, 'health'),
];
?>

<script id="chart-data-json" type="application/json">
    <?= json_encode($stats) ?>
</script>

<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
<script src="<?= assets('/js/chart.js') ?>"></script>

<script>
/**
 * Pola Singleton: Menghindari error "Identifier has already been declared"
 * dan mengelola siklus hidup chart di lingkungan HTMX.
 */
window.AssetMonitor = window.AssetMonitor || {
    instances: {},

    // Konfigurasi Warna (Tailwind palette)
    colors: {
        ready: '#10b981',   // emerald-500
        maint: '#f43f5e',   // rose-500
        working: '#3b82f6', // blue-500
        amber: '#f59e0b',   // amber-500
        slate400: '#94a3b8',
        slate800: '#1e293b',
        grid: '#f1f5f9'
    },

    /**
     * Fungsi Pembersihan Aman (Mengatasi error "Canvas already in use")
     */
    safeDestroy(id, el) {
        // 1. Hancurkan lewat tracker internal kita
        if (this.instances[id]) {
            this.instances[id].destroy();
            delete this.instances[id];
        }
        // 2. Failsafe: Hancurkan lewat tracker internal Chart.js pada elemen tersebut
        const existingChart = Chart.getChart(el);
        if (existingChart) {
            existingChart.destroy();
        }
    },

    update(data) {
        if (!data) return;
        this.renderStatusChart(data);
        this.renderHealthChart(data);
    },

    renderStatusChart(data) {
        const el = document.getElementById('statusChart');
        if (!el) return;

        this.safeDestroy('statusChart', el);

        const total = (Number(data.ready) + Number(data.maint) + Number(data.working));

        this.instances['statusChart'] = new Chart(el, {
            type: 'doughnut',
            data: {
                labels: ['Ready', 'Maintenance', 'Working'],
                datasets: [{
                    data: [data.ready, data.maint, data.working],
                    backgroundColor: [this.colors.ready, this.colors.maint, this.colors.working],
                    borderWidth: 0,
                    cutout: '80%'
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: (chart) => {
                    const { ctx, width, height } = chart;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    
                    // Angka Utama
                    ctx.font = "bold 2rem sans-serif";
                    ctx.fillStyle = this.colors.slate800;
                    ctx.fillText(total.toLocaleString(), width / 2, height / 2 - 5);

                    // Label Unit
                    ctx.font = "bold 0.75rem sans-serif";
                    ctx.fillStyle = this.colors.slate400;
                    ctx.fillText("TOTAL UNIT", width / 2, height / 2 + 22);
                    ctx.restore();
                }
            }]
        });
    },

    renderHealthChart(data) {
        const el = document.getElementById('healthChart');
        if (!el) return;

        this.safeDestroy('healthChart', el);

        const barColors = data.unitHealths.map(v => 
            v < 40 ? this.colors.maint : (v < 75 ? this.colors.amber : this.colors.ready)
        );

        this.instances['healthChart'] = new Chart(el, {
            type: 'bar',
            data: {
                labels: data.unitNames,
                datasets: [{
                    label: 'Health %',
                    data: data.unitHealths,
                    backgroundColor: barColors,
                    borderRadius: 6,
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        max: 100,
                        grid: { color: this.colors.grid },
                        ticks: { callback: v => v + '%' }
                    }, 
                    x: { grid: { display: false } } 
                },
                plugins: { legend: { display: false } }
            },
            plugins: [{
                id: 'thresholdLine',
                afterDraw: (chart) => {
                    const { ctx, chartArea: { left, right }, scales: { y } } = chart;
                    const yPos = y.getPixelForValue(40);
                    ctx.save();
                    ctx.beginPath();
                    ctx.lineWidth = 2;
                    ctx.setLineDash([5, 5]);
                    ctx.strokeStyle = 'rgba(244, 63, 94, 0.4)';
                    ctx.moveTo(left, yPos);
                    ctx.lineTo(right, yPos);
                    ctx.stroke();
                    ctx.fillStyle = this.colors.maint;
                    ctx.font = 'bold 10px sans-serif';
                    ctx.fillText('CRITICAL (40%)', left + 5, yPos - 8);
                    ctx.restore();
                }
            }]
        });
    }
};

/**
 * HTMX Event Listeners (Mencegah duplikasi listener)
 */
if (!window.AssetMonitorBound) {
    document.body.addEventListener('htmx:afterSwap', function(evt) {
        if (evt.detail.target.id === 'asset-container') {
            const dataEl = document.getElementById('chart-data-json');
            if (dataEl && window.AssetMonitor) {
                try {
                    window.AssetMonitor.update(JSON.parse(dataEl.textContent));
                } catch (e) {
                    console.error("Gagal parsing JSON data:", e);
                }
            }
        }
    });

    document.body.addEventListener('htmx:swapError', (e) => {
        console.error("HTMX Swap Error:", e.detail);
    });

    window.AssetMonitorBound = true;
}

/**
 * Inisialisasi Awal (saat page load pertama kali)
 */
(function() {
    const dataEl = document.getElementById('chart-data-json');
    if (dataEl && window.AssetMonitor) {
        window.AssetMonitor.update(JSON.parse(dataEl.textContent));
    }
})();
</script>