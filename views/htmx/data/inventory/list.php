<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
    <tr id="product-row-<?= $p->id ?>" class="hover:bg-slate-50/80 transition group">
        <td class="p-6">
            <div class="font-bold text-slate-800"><?= $p->nama ?></div>
            <div class="text-[10px] text-slate-400 font-normal italic md:hidden"><?= $p->kategori ?></div>
        </td>
        <td class="p-6">
            <span class="uppercase text-[10px] font-bold px-2 py-1 bg-slate-100 text-slate-500 rounded-md border border-slate-200">
                <?= $p->kategori ?>
            </span>
        </td>
        <td class="p-6 font-mono text-slate-600 font-medium"><?= number_format($p->harga, 0, ',', '.') ?></td>
        <td class="p-6 text-center">
            <span class="<?= $p->stok <= 5 ? 'text-rose-600' : 'text-slate-800' ?> font-bold">
                <?= $p->stok ?>
            </span>
        </td>
        <td class="p-6">
            <div class="flex justify-center">
                <?php if ($p->stok <= 5): ?>
                    <span class="px-3 py-1 bg-rose-50 text-rose-600 rounded-full text-[10px] font-bold border border-rose-100 shadow-sm animate-pulse">
                        <i class="fas fa-exclamation-circle mr-1"></i> RESTOCK SEGERA
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold border border-emerald-100 shadow-sm">
                        <i class="fas fa-check-circle mr-1"></i> AMAN
                    </span>
                <?php endif; ?>
            </div>
        </td>
        <td class="p-6 text-right">
            <div x-data="{ open: false }" @click.away="open = false" class="relative inline-block text-left">
                
                <button @click="open = !open" 
                        class="text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all p-2 focus:outline-none"
                        :class="open ? 'bg-indigo-50 text-indigo-600' : ''">
                    <i class="fas fa-ellipsis-v"></i>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute top-0 right-2 mt-2 w-32 origin-top-right bg-white border border-slate-200 rounded-xl shadow-xl z-[70] overflow-hidden focus:outline-none"
                     style="display: none;">
                    
                    <div class="py-1">
                        <button hx-get="<?= url('/data/edit-product?id='. $p->id) ?>" 
                                hx-target="#edit-modal-content" 
                                @click="$dispatch('open-edit-modal')" 
                                class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2 transition-colors">
                            <i class="fas fa-edit w-4"></i> Edit
                        </button>
                        
                        <button @click="$dispatch('open-delete-modal', { 
                                    url: '<?= url('/data/delete-product?id=') ?>',
                                    id: '<?= $p->id ?>', 
                                    name: '<?= htmlspecialchars($p->nama) ?>' 
                                })"
                                class="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                            <i class="fas fa-trash-alt w-4"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="p-20 text-center text-slate-400">
            <div class="flex flex-col items-center justify-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-2xl"></i>
                </div>
                <p class="font-medium">Tidak ada produk di kategori ini.</p>
                <p class="text-xs italic">Coba pilih kategori lain atau tambah produk baru.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>

<tr style="display:none">
    <td>
        <div id="pagination-container" hx-swap-oob="true" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500 font-medium">
                    Halaman <span class="text-slate-800 font-bold"><?= $currentPage ?></span> dari <span class="text-slate-800 font-bold"><?= $totalPages ?></span>
                </p>
                
                <div class="flex items-center gap-1">
                    <button hx-get="<?= url('/data/get-products?page=' . max(1, $currentPage - 1) .'&category='. urlencode($category) .'&search='. urlencode($search)) ?>"
                            hx-target="#inventory-table-body"
                            hx-include="[name='search'], [name='filter_kategori']"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all disabled:opacity-50"
                            <?= $currentPage == 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>

                    <?php foreach ($paginationItems as $item): ?>
                        <?php if ($item === '...'): ?>
                            <span class="px-2 text-slate-400 text-xs">...</span>
                        <?php else: ?>
                            <button hx-get="<?= url('/data/get-products?page=' .$item .'&category='. urlencode($category) .'&search='. urlencode($search)) ?>"
                                    hx-target="#inventory-table-body"
                                    hx-include="[name='search'], [name='filter_kategori']"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all 
                                    <?= $item == $currentPage 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' 
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300' ?>">
                                <?= $item ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <button hx-get="<?= url('/data/get-products?page=' . min($totalPages, $currentPage + 1) .'&category='. urlencode($category) .'&search='. urlencode($search)) ?>"
                            hx-target="#inventory-table-body"
                            hx-include="[name='search'], [name='filter_kategori']"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-indigo-600 hover:border-indigo-100 transition-all"
                            <?= $currentPage == $totalPages ? 'disabled' : '' ?>>
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <script id="chart-data-json" type="application/json" hx-swap-oob="innerHTML">
            <?= json_encode($stats) ?>
        </script>
    </td>
</tr>