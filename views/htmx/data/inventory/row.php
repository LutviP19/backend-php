    <!-- <tr id="product-row-<?= $id ?>" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors fade-in-content"> -->
    <tr id="product-row-<?= $id ?>" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors row-updated">
        <td class="p-6">
            <div class="font-bold text-slate-800"><?= htmlspecialchars($nama) ?></div>
            <div class="text-[10px] text-slate-400 font-normal italic md:hidden"><?= htmlspecialchars($kategori) ?></div>
        </td>
        <td class="p-6">
            <span class="uppercase text-[10px] font-bold px-2 py-1 bg-slate-100 text-slate-500 rounded-md border border-slate-200">
                <?= htmlspecialchars($kategori) ?>
            </span>
        </td>
        <td class="p-6 font-mono text-slate-600 font-medium"><?= number_format($harga, 0, ',', '.') ?></td>
        <td class="p-6 text-center">
            <span class="<?= $stok <= 5 ? 'text-rose-600' : 'text-slate-800' ?> font-bold">
            <?= $stok ?>
            </span>
        </td>
        <td class="p-6">
            <div class="flex justify-center">
                <?php if ($stok <= 5): ?>
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
                     class="absolute right-0 mt-2 w-32 origin-top-right bg-white border border-slate-200 rounded-xl shadow-xl z-[70] overflow-hidden focus:outline-none"
                     style="display: none;">
                    
                    <div class="py-1">
                        <button hx-get="<?= url('/data/edit-product?id='. $id) ?>" 
                                hx-target="#edit-modal-content" 
                                @click="$dispatch('open-edit-modal')" 
                                class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2 transition-colors">
                            <i class="fas fa-edit w-4"></i> Edit
                        </button>
                        
                        <button @click="$dispatch('open-delete-modal', { 
                                    url: '<?= url('/data/delete-product?id=') ?>',
                                    id: '<?= $id ?>', 
                                    name: '<?= htmlspecialchars($nama) ?>' 
                                })"
                                class="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                            <i class="fas fa-trash-alt w-4"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </td>
    </tr>