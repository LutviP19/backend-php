<div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Inventaris Produk</h1>
            <p class="text-slate-500 italic">Kelola stok keperluan bertani dengan bantuan prediksi AI.</p>
        </div>
        <button @click="$dispatch('open-add-modal')" 
        class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:scale-105 transition-all active:scale-95">
            <i class="fas fa-plus mr-2"></i> Tambah Produk
        </button>
    </div>

    <div x-data="{ 
            activeCat: 'all',
            searchQuery: '',
            isKritis: false, // State untuk mendeteksi kondisi bahaya
            pillStyles: {
                'all': 'left: 0%; width: 25%;',
                'pupuk': 'left: 25%; width: 25%;',
                'benih': 'left: 50%; width: 25%;',
                'pestisida': 'left: 75%; width: 25%;'
            }
        }"
        @update-ai-insight.window="
            isKritis = $event.detail.isKritis; 
            document.getElementById('ai-message').innerText = $event.detail.msg;
        ">
        <input type="hidden" name="category" :value="activeCat">
        
        <div id="ai-insight" 
             :class="isKritis ? 'bg-rose-50 border-rose-100 shadow-rose-100' : 'bg-indigo-50 border-indigo-100 shadow-indigo-100'"
             class="mb-6 p-4 border rounded-2xl flex items-center gap-4 transition-all duration-500 shadow-sm">
            
            <div :class="isKritis ? 'bg-rose-600' : 'bg-indigo-600'"
                 class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-lg transition-colors duration-500">
                <i class="fas fa-robot text-white text-sm" :class="isKritis ? 'animate-pulse' : 'animate-bounce'"></i>
            </div>

            <div>
                <h4 class="text-[10px] font-black uppercase tracking-widest mb-0.5 transition-colors duration-500"
                    :class="isKritis ? 'text-rose-900' : 'text-indigo-900'">
                    AI Inventory Insight
                </h4>
                
                <p id="ai-message" 
                   :class="isKritis ? 'text-rose-700' : 'text-indigo-700'"
                   class="text-xs md:text-sm font-medium transition-colors duration-500">
                    Menganalisis data kategori <span class="font-bold underline" x-text="activeCat"></span>...
                </p>
            </div>
        </div>

        <div class="relative mb-6 group" x-data="{ searchQuery: '' }">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <input type="text" 
                   name="search" 
                   autocomplete="off" 
                   placeholder="Cari nama produk..." 
                   x-model="searchQuery" 
                   class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 outline-none transition-all shadow-sm text-sm"
                   hx-get="<?= url('/data/get-products') ?>" 
                   hx-trigger="keyup changed delay:500ms, search" 
                   hx-target="#inventory-table-body" 
                   hx-include="[name='category']" 
                   hx-indicator="#search-spinner">
                   
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center gap-3">
                    <div id="search-spinner" class="htmx-indicator">
                        <i class="fas fa-circle-notch animate-spin text-indigo-500 text-sm"></i>
                    </div>

                    <button x-show="searchQuery.length > 0"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            @click="searchQuery = ''; 
                                    $nextTick(() => { $el.closest('.relative').querySelector('input').dispatchEvent(new Event('search')) })" 
                            type="button"
                            class="text-slate-400 hover:text-rose-500 transition-colors focus:outline-none">
                        <i class="fas fa-times-circle text-lg"></i>
                    </button>
                </div>
        </div>

        <div class="relative flex mb-8 bg-slate-200 p-1.5 rounded-2xl w-full select-none shadow-inner">
            <div class="absolute inset-y-1.5 bg-indigo-600 rounded-xl shadow-md transition-all duration-300 ease-out"
                 :style="pillStyles[activeCat]"></div>
            
            <template x-for="cat in ['all', 'pupuk', 'benih', 'pestisida']">
                <button @click="activeCat = cat"
                        :hx-get="'<?= url('/data/inventory-list/?category=') ?>' + cat" 
                        hx-include="[name='search']" 
                        hx-target="#inventory-table-body" 
                        class="relative z-10 flex-1 px-5 py-2 text-xs md:text-sm font-bold transition-colors duration-300 capitalize"
                        :class="activeCat === cat ? 'text-white' : 'text-slate-500'">
                    <span x-text="cat === 'all' ? 'Semua' : cat"></span>
                </button>
            </template>
        </div>
    </div>

    <div class="overflow-auto bg-white rounded-2xl shadow-sm border border-slate-200">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="p-4 font-bold text-slate-700">Nama Barang</th>
                    <th class="p-4 font-bold text-slate-700">Kategori</th>
                    <th class="p-4 font-bold text-slate-700">Harga</th>
                    <th class="p-4 font-bold text-slate-700">Stok</th>
                    <th class="p-4 font-bold text-slate-700">Status AI</th>
                    <th class="p-4 font-bold text-slate-700"></th>
                </tr>
            </thead>
            
            <tbody id="skeleton-loader">
                <?php for($i=0; $i<5; $i++): ?>
                <tr class="skeleton-row border-b border-slate-100">
                    <td class="p-4"><div class="w-3/4"></div></td>
                    <td class="p-4"><div class="w-1/2"></div></td>
                    <td class="p-4"><div class="w-1/4"></div></td>
                    <td class="p-4"><div class="w-1/4"></div></td>
                    <td class="p-4"><div class="w-1/4"></div></td>
                    <td class="p-4"><div class="w-1/4"></div></td>
                </tr>
                <?php endfor; ?>
            </tbody>

            <tbody id="inventory-table-body">
                <?php $this->include('htmx.data.inventory.list', $data); ?>
            </tbody>
        </table>
    </div>

    <div id="pagination-container" lass="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-500 font-medium">
                Halaman <span class="text-slate-800 font-bold"><?= $data['currentPage'] ?></span> dari <span class="text-slate-800 font-bold"><?= $data['totalPages'] ?></span>
            </p>
            
            <div class="flex items-center gap-1">
                <button hx-get="<?= url('/data/get-products?page=' . max(1, $data['currentPage'] - 1) .'&category='. urlencode($data['category']) .'&search='. urlencode($data['search'])) ?>"
                        hx-target="#inventory-table-body"
                        hx-include="[name='search'], [name='filter_kategori']"
                        class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:text-indigo-600 disabled:opacity-30"
                        <?= $data['currentPage'] == 1 ? 'disabled' : '' ?>>
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </button>

                <?php foreach ($data['paginationItems'] as $item): ?>
                    <?php if ($item === '...'): ?>
                        <span class="px-2 text-slate-400 text-xs">...</span>
                    <?php else: ?>
                        <button hx-get="<?= url('/data/get-products?page=' .$item .'&category='. urlencode($category) .'&search='. urlencode($search)) ?>"
                                hx-target="#inventory-table-body"
                                hx-include="[name='search'], [name='filter_kategori']"
                                class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all 
                                <?= $item == $data['currentPage'] 
                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' 
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300' ?>">
                            <?= $item ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>

                <button hx-get="<?= url('/data/get-products?page=' . min($data['totalPages'], $data['currentPage'] + 1) .'&category='. urlencode($data['category']) .'&search='. urlencode($data['search'])) ?>"
                        hx-target="#inventory-table-body"
                        hx-include="[name='search'], [name='filter_kategori']"
                        class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:text-indigo-600 disabled:opacity-30"
                        <?= $data['currentPage'] == $data['totalPages'] ? 'disabled' : '' ?>>
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>


</div>


<script>
    document.body.addEventListener('htmx:swapError', function(evt) {
        console.error("Swap Error detail:", evt.detail);
        
        // Opsional: Gunakan toast atau alert agar admin tahu ada masalah teknis
        // alert("Gagal memuat data tabel. Struktur data tidak sesuai.");
    });

    document.body.addEventListener('htmx:afterSwap', function(evt) {
        // Jika yang diupdate adalah tabel, cari data pagination baru
        if (evt.detail.target.id === 'inventory-table-body') {
            const newPagination = document.querySelector('#hidden-pagination-data');
            if (newPagination) {
                document.querySelector('#pagination-container').innerHTML = newPagination.innerHTML;
            }
        }
    });
</script>