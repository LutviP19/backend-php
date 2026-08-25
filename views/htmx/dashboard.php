<style>
/* Container chart harus relatif agar overlay bisa absolut */
.chart-container {
    position: relative;
}

/* Overlay transparan */
.chart-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(2px);
    display: none; /* Sembunyi secara default */
    align-items: center;
    justify-content: center;
    z-index: 20;
    border-radius: 1.5rem;
    transition: all 0.3s ease;
}

/* Munculkan saat HTMX melakukan request */
.htmx-request .chart-overlay {
    display: flex;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Ringkasan Koperasi</h1>
            <p class="text-slate-500 italic">Data diperbarui secara real-time oleh asisten AI.</p>
        </div>
        <div class="flex gap-3">
            <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-download mr-2"></i> Ekspor Laporan
            </button>
            <button class="px-4 py-2 bg-indigo-600 rounded-xl shadow-lg shadow-indigo-200 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <i class="fas fa-plus mr-2"></i> Transaksi Baru
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <div class="md:col-span-8 bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="relative z-10 h-full flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Tren Pendapatan (Mingguan)</p>
                        <h2 class="text-4xl font-black text-slate-800 tracking-tighter">Rp <span id="lastIncome">50.073.055</span></h2>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                </div>
                
                <div class="chart-container relative flex-grow h-48 w-full" id="income-chart-wrapper">
                    <div class="chart-overlay htmx-indicator">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-circle-notch fa-spin text-indigo-600 text-xl"></i>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Updating...</span>
                        </div>
                    </div>
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="md:col-span-4 bg-slate-900 p-8 rounded-[2rem] text-white shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4">
                <span class="flex h-3 w-3">
                  <span class="animate-ping absolute top-0 right-0 inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
            </div>
            <i class="fas fa-robot text-indigo-400 text-3xl mb-6"></i>
            <h3 class="text-xl font-bold mb-4">AI Advisor Insight</h3>
            <span class="text-slate-400 text-sm leading-relaxed italic">
                "Berdasarkan data musim tanam tahun lalu, permintaan <strong>Pupuk Phonska</strong> diprediksi melonjak 40% minggu depan. Segera lakukan restock untuk menghindari kekosongan."
            </span>
            <button class="mt-6 w-full py-3 bg-indigo-600 hover:bg-indigo-500 rounded-xl font-bold text-sm transition">
                Terapkan Saran
            </button>
        </div>

        <div class="md:col-span-4 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <span class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-tractor"></i>
                </span>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg uppercase">8 Unit Aktif</span>
            </div>
            <div class="mt-6">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Utilitas Alat Berat</p>
                <h4 class="text-2xl font-bold text-slate-800">85.4%</h4>
            </div>
        </div>

        <div class="md:col-span-4 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <span class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded-lg uppercase">Stok Kritis</span>
            </div>
            <div class="mt-6">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Item Perlu Perhatian</p>
                <h4 class="text-2xl font-bold text-slate-800">03 <span class="text-sm font-normal text-slate-400 tracking-normal">Produk</span></h4>
            </div>
        </div>

        <div class="md:col-span-4 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <h4 class="text-sm font-bold text-slate-800 mb-4">Aktivitas Terakhir</h4>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                    <div class="flex-1">
                        <p class="text-xs font-bold text-slate-700">Sewa Traktor - Bp. Sukirman</p>
                        <p class="text-[10px] text-slate-400 italic">Baru saja</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    <div class="flex-1">
                        <p class="text-xs font-bold text-slate-700">Restock Pupuk Urea (50 Sak)</p>
                        <p class="text-[10px] text-slate-400 italic">2 jam yang lalu</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mt-6">
    
        <div class="md:col-span-4 bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Utilitas Alat Berat</p>
                    <h4 id="utility-cutout" class="text-2xl font-bold text-slate-800">85.4%</h4>
                </div>
                <span class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-tractor"></i>
                </span>
            </div>
            <div class="chart-container relative h-40 w-full" id="utility-chart-wrapper">
                <div class="chart-overlay htmx-indicator">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-circle-notch fa-spin text-indigo-600 text-xl"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Updating...</span>
                    </div>
                </div>
                <canvas id="utilityChart"></canvas>
                <!-- Dibuat Dinamis dengan ID -->
                <div class="absolute inset-0 flex items-center justify-center flex-col pt-2 pointer-events-none">
                    <span id="utility-count" class="text-xs font-bold text-emerald-600">8 Unit</span>
                    <span id="utility-label" class="text-[9px] text-slate-400 uppercase">Aktif</span>
                </div>
            </div>
            <div class="mt-4 flex justify-between text-[10px] font-bold text-slate-500 uppercase tracking-tighter">
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Operasional
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 rounded-full bg-slate-200"></div> Maintenance
                </div>
            </div>
        </div>

        <div class="md:col-span-8 bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Tren Stok Kritis (7 Hari Terakhir)</p>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter">Pengawasan Inventaris</h2>
                </div>
                <span id="stock-warning" class="px-3 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-bold uppercase">
                    3 Produk Warning
                </span>
            </div>
            <div class="chart-container h-48 w-full" id="stock-chart-wrapper">
                <div class="chart-overlay htmx-indicator">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-circle-notch fa-spin text-indigo-600 text-xl"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Updating...</span>
                    </div>
                </div>
                <canvas id="stockChart"></canvas>
            </div>
        </div>

    </div>

    <div class="mt-6">
        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            
            <div class="p-8 border-b border-slate-100 bg-white/50 backdrop-blur-sm">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <h3 class="text-xl font-bold text-slate-800 tracking-tight">Log Aktivitas Operasional</h3>
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <p class="text-slate-500 text-sm font-medium">Pemantauan real-time aset koperasi</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3" 
                         x-data="{ 
                            search: '', 
                            selected: '', 
                            label: 'Semua Kategori',
                            resetAll() {
                                    this.search = '';
                                    this.selected = '';
                                    this.label = 'Semua Kategori';
                                    /*$nextTick(() => {
                                        htmx.trigger('#search-input', 'keyup'); // Reset Tabel 
                                        htmx.trigger('#chart-updater', 'update-charts');  // Reset Chart Instan
                                    });*/
                                    // Opsi Bubling Gunakan $nextTick untuk memastikan DOM sudah bersih
                                    this.$nextTick(() => {
                                        // Picu request tabel secara manual dengan parameter bersih
                                        htmx.ajax('GET', '<?= url('/data/data-dashboard/activities') ?>', {
                                            target: '#activity-table-body',
                                            values: { search: '', category: '' }
                                        });

                                        // Picu request chart secara manual
                                        htmx.ajax('GET', '<?= url('/data/data-dashboard/stats') ?>', {
                                            target: '#chart-updater', // stats biasanya hx-swap='none'
                                            values: { search: '', category: '' }
                                        });
                                        
                                        // Terakhir, fokuskan kembali ke input (Opsional)
                                        document.getElementById('search-input').focus();
                                    });
                                }
                            }"
                            @reset-filters.window="resetAll()" 
                         >
                        
                        <div class="relative group flex-1 md:flex-none">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors duration-300">
                                <i class="fas fa-search text-sm"></i>
                            </div>
                            <input 
                                id="search-input"
                                type="text" 
                                name="search"
                                x-model="search"
                                hx-get="<?= url('/data/data-dashboard/activities') ?>" 
                                hx-trigger="keyup[target.value.length > 0] changed delay:500ms, search-reset from:body" 
                                hx-target="#activity-table-body"
                                hx-include="[name='category']"
                                hx-indicator="#search-loading" 
                                hx-on::after-request="htmx.trigger('#chart-updater', 'update-charts')" 
                                autocomplete="off" 
                                placeholder="Cari anggota atau aktivitas..." 
                                class="w-full md:w-72 pl-11 pr-12 py-2.5 bg-white border border-slate-200 rounded-2xl text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all"
                            >
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                <i id="search-loading" class="fas fa-circle-notch fa-spin text-indigo-500 text-xs htmx-indicator"></i>
                                <template x-if="search.length > 0">
                                    <button 
                                    @click.stop="resetAll()" 
                                    hx-vals='{"search": "", "category": ""}' 
                                    hx-on::after-request="htmx.trigger('#chart-updater', 'update-charts')" 
                                    class="text-slate-300 hover:text-rose-500 transition-colors">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative w-full md:w-56" x-data="{ open: false }" @click.away="open = false">
                            <input type="hidden" name="category" x-model="selected" 
                                   hx-get="<?= url('/data/data-dashboard/activities') ?>" 
                                   hx-target="#activity-table-body" 
                                   hx-trigger="change"
                                   hx-include="#search-input"
                                   id="category-hidden-input">

                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-500/5 transition-all">
                                <span class="flex items-center gap-2.5">
                                    <div class="w-2 h-2 rounded-full" :class="selected ? 'bg-indigo-500' : 'bg-slate-300'"></div>
                                    <span x-text="label" class="truncate"></span>
                                </span>
                                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="absolute z-50 w-full mt-2 bg-white border border-slate-100 shadow-2xl shadow-indigo-500/10 rounded-2xl overflow-hidden p-1.5">
                                
                                <div class="space-y-0.5">
                                    <template x-for="item in [
                                        {id: '', label: 'Semua Kategori', icon: 'fa-layer-group'},
                                        {id: 'finance', label: 'Simpan Pinjam', icon: 'fa-wallet'},
                                        {id: 'inventory', label: 'Inventaris', icon: 'fa-box'},
                                        {id: 'assets', label: 'Alat Berat', icon: 'fa-tractor'}
                                    ]">
                                        <button 
                                            @click="selected = item.id; 
                                                    label = item.label; 
                                                    open = false; 
                                                    $nextTick(() => { 
                                                        htmx.trigger('#category-hidden-input', 'change');
                                                        htmx.trigger('#chart-updater', 'update-charts');
                                                    });"
                                            class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-all text-left"
                                            :class="selected === item.id ? 'bg-indigo-50 text-indigo-600 font-bold' : 'text-slate-600 hover:bg-slate-50'">
                                            <i class="fas text-xs opacity-70" :class="item.icon"></i>
                                            <span x-text="item.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <button 
                            x-show="search.length > 0 || selected !== ''"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-x-2"
                            @click.stop="resetAll()" 
                            hx-vals='{"search": "", "category": ""}' 
                            hx-on::after-request="htmx.trigger('#chart-updater', 'update-charts')" 
                            type="button"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 transition-all border border-rose-100"
                            >
                            <i class="fas fa-sync-alt text-[10px]"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Aktivitas</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kategori</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Waktu</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody id="activity-table-body" class="divide-y divide-slate-100">
                        <?php
                        // Render Data
                        foreach ($paged_data as $item) {
                            ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-<?= $item['color'] ?>-50 text-<?= $item['color'] ?>-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas <?= $item['icon'] ?>"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700"><?= $item['title'] ?></p>
                                        <p class="text-[11px] text-slate-400"><?= $item['member'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">
                                    <?= $item['cat'] === 'assets' ? 'Alat Berat' : ($item['cat'] === 'finance' ? 'Simpan Pinjam' : 'Inventaris') ?>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-xs text-slate-500 font-medium"><?= $item['time'] ?></td>
                            <td class="px-6 py-5 text-right">
                                <span class="inline-flex items-center gap-1 text-<?= $item['status'] === 'Selesai' ? 'emerald' : 'amber' ?>-600 font-bold text-[11px]">
                                    <?php if ($item['status'] === 'Proses'): ?><i class="fas fa-spinner fa-spin text-[9px]"></i><?php endif; ?>
                                    <?= $item['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php
                        }
                                        ?>
                    </tbody>
                </table>

                <div class="p-6 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/30">
                    <p class="text-xs font-medium text-slate-500" id="pagination-info">
                        Menampilkan halaman <?= $page ?> dari <?= $total_pages ?> halaman
                    </p>
                    
                    <div class="flex items-center gap-2" id="pagination-controls">
                    <?php if ($page > 1): ?>
                        <button hx-get="<?= url('/data/data-dashboard/activities?page=' . ($page - 1)) ?>" hx-include="#search-input, [name='category']" hx-target="#activity-table-body" class="w-9 h-9 flex items-center justify-center border border-slate-200 rounded-xl hover:bg-slate-50 transition-all text-slate-400">
                            <i class="fas fa-chevron-left text-[10px]"></i>
                        </button>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <button hx-get="<?= url('/data/data-dashboard/activities?page=' . $i) ?>" hx-include="#search-input, [name='category']" hx-target="#activity-table-body" class="w-9 h-9 flex items-center justify-center rounded-xl font-bold text-xs transition-all <?= $i == $page ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <button hx-get="<?= url('/data/data-dashboard/activities?page=' . ($page + 1)) ?>" hx-include="#search-input, [name='category']" hx-target="#activity-table-body" class="w-9 h-9 flex items-center justify-center border border-slate-200 rounded-xl hover:bg-slate-50 transition-all text-slate-400">
                            <i class="fas fa-chevron-right text-[10px]"></i>
                        </button>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div hx-get="<?= url('/data/data-dashboard/stats') ?>" 
     hx-trigger="every 60s, update-charts" 
     hx-swap="none" 
     class="hidden" 
     hx-include="#search-input, [name='category']" 
     hx-indicator="#income-chart-wrapper, #utility-chart-wrapper, #stock-chart-wrapper" 
     id="chart-updater">
</div>

<script src="<?= assets('/js/chart.js') ?>"></script>
<script>
// Objek pusat untuk manajemen chart
window.ChartManager = window.ChartManager || {
    instances: {},
    
    // Helper untuk membuat gradient agar tidak menulis ulang kodenya
    getGradient(ctx, colorRGB) {
        const g = ctx.createLinearGradient(0, 0, 0, 200);
        g.addColorStop(0, `rgba(${colorRGB}, 1)`);
        g.addColorStop(1, `rgba(${colorRGB}, 0.05)`);
        return g;
    },

    init() {
        // 1. Bar Chart (Pendapatan)
        this.create('incomeChart', 'bar', {
            labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            datasets: [{
                data: [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, 50073055], // Data dummy awal
                backgroundColor: (ctx) => this.getGradient(ctx.chart.ctx, '99, 102, 241'), // Indigo
                borderRadius: 10
            }]
        }, { scales: { y: { display: false }, x: { grid: { display: false } } } });

        // 2. Donut Chart
        this.create('utilityChart', 'doughnut', {
            labels: ['Aktif', 'Maintenance'],
            datasets: [{
                data: [85.4, 14.6],
                backgroundColor: ['#10b981', '#f1f5f9'],
                borderWidth: 0
            }]
        }, { cutout: '80%' });

        // 3. Line Chart (Stock)
        this.create('stockChart', 'line', {
            labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            datasets: [{
                data: [5, 8, 4, 12, 9, 3, 3],
                borderColor: '#e11d48', // rose-600
                fill: true,
                backgroundColor: (ctx) => this.getGradient(ctx.chart.ctx, '225, 29, 72'),
                tension: 0.4
            }]
        });
    },

    create(id, type, data, extraOptions = {}) {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        // Hancurkan instance lama jika ada (Prevent Memory Leak)
        if (this.instances[id]) this.instances[id].destroy();

        this.instances[id] = new Chart(canvas, {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                ...extraOptions
            }
        });
    },

    // Method aman untuk meng-update data dari HTMX (tanpa re-create)
    updateData(id, newData) {
        if (!newData) return;

        const chart = this.instances[id];

        // Case 1: Chart sudah ada & Canvas masih menempel di DOM
        if (chart && document.body.contains(chart.canvas)) {
            chart.data.datasets[0].data = [...newData];
            chart.update();
            return;
        }

        // Case 2: Canvas hilang/di-swap oleh HTMX -> Re-create spesifik chart ini dengan newData
        const canvas = document.getElementById(id);
        if (canvas) {
            // Hancurkan instance lama jika menggantung
            if (this.instances[id]) this.instances[id].destroy();

            // Panggil kembali fungsi create sesuai tipe chart-nya
            // (Contoh re-init dinamis sederhana)
            this.init(); 
            if (this.instances[id]) {
                this.instances[id].data.datasets[0].data = [...newData];
                this.instances[id].update('none');
            }
        }
    }
};

// Gabungkan semua listener HTMX dalam satu blok agar hemat memory
document.body.addEventListener('htmx:afterSwap', (evt) => {
    window.ChartManager.init();
    
    // Logic pembersihan pagination
    if (evt.detail.target.id === 'activity-table-body') {
        const hasPagination = evt.detail.xhr.responseText.includes('pagination-controls');
        if (!hasPagination) {
            document.getElementById('pagination-info')?.replaceChildren();
            document.getElementById('pagination-controls')?.replaceChildren();
        }
    }
});

document.body.addEventListener('htmx:afterRequest', (evt) => {
    if (evt.detail.target.id === 'chart-updater') {
        const rawResponse = evt.detail.xhr.responseText;
        if (!rawResponse) return;

        try {
            const res = JSON.parse(rawResponse);
            const payload = res.data;

            if (!payload) return;

            // Update data tiap chart
            if (payload.income && window.ChartManager?.instances?.['incomeChart']) {
                const chart = window.ChartManager.instances['incomeChart'];
                chart.data.datasets[0].data = [...payload.income]; // Gunakan spread operator agar reference array diperbarui
                chart.update('none'); // Update tanpa animasi ulang seluruhnya agar responsif
            }
            
            if (payload.utility && window.ChartManager?.instances?.['utilityChart']) {
                const chart = window.ChartManager.instances['utilityChart'];
                
                // 1. Update data chart-nya
                chart.data.datasets[0].data = [...payload.utility];
                
                // 2. Hitung cutout dinamis dari nilai pertama (misal: 88 -> '88%')
                const calculatedCutout = `${payload.utility[0]}%`;
                const cutoutEl = document.getElementById('utility-cutout');
                if (cutoutEl) cutoutEl.textContent = calculatedCutout;
                // chart.options.cutout = calculatedCutout;
                
                
                // 3. Render ulang
                chart.update('none');
            }

            if (payload.stock_critical && window.ChartManager?.instances?.['stockChart']) {
                const chart = window.ChartManager.instances['stockChart'];
                chart.data.datasets[0].data = [...payload.stock_critical];
                chart.update('none');
            }

            // stock_warning
            if (payload.stock_warning) {
                const stockEl = document.getElementById('stock-warning');
                if (stockEl) stockEl.textContent = payload.stock_warning;
            }

            // 2. Update Teks Tengah Utility Chart secara Dinamis
            if (payload.utility_count) {
                const countEl = document.getElementById('utility-count');
                if (countEl) countEl.textContent = payload.utility_count;
            }
            if (payload.utility_label) {
                const labelEl = document.getElementById('utility-label');
                if (labelEl) labelEl.textContent = payload.utility_label;
            }

            // Update teks lastIncome
            if (payload.last_income) {
                const el = document.getElementById('lastIncome');
                if (el) el.textContent = payload.last_income;
            }
        } catch (e) {
            console.error('Gagal memproses JSON HTMX:', e);
        }
    }
});

// Jalankan saat pertama kali load jika di dashboard
<?php if (isset($isHome)): ?>
    document.addEventListener('DOMContentLoaded', () => window.ChartManager.init());
<?php endif; ?>
</script>