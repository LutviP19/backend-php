<table class="w-full text-left">
    <thead class="bg-slate-50 border-b border-slate-100 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
        <tr>
            <th class="p-6">Nama Produk</th>
            <th class="p-6">Kategori</th>
            <th class="p-6">Harga</th>
            <th class="p-6 text-center">Stok</th>
            <th class="p-6 text-center">Status AI</th>
            <th class="p-6"></th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 text-sm italic">
        <?php foreach ($products as $p): ?>
        <tr class="hover:bg-slate-50/80 transition">
            <td class="p-6 font-bold text-slate-800"><?= $p['name'] ?></td>
            <td class="p-6 uppercase text-[10px] font-medium text-slate-500"><?= $p['cat'] ?></td>
            <td class="p-6 font-mono text-slate-600"><?= $p['price'] ?></td>
            <td class="p-6 text-center font-bold"><?= $p['stock'] ?></td>
            <td class="p-6">
                <div class="flex justify-center">
                    <?php if ($p['status'] == 'Kritis'): ?>
                        <span class="px-3 py-1 bg-rose-50 text-rose-600 rounded-full text-[10px] font-bold border border-rose-100">
                            <i class="fas fa-exclamation-circle mr-1"></i> RESTOCK SEGERA
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold border border-emerald-100">
                            <i class="fas fa-check-circle mr-1"></i> AMAN
                        </span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="p-6 text-right">
                <button class="text-slate-400 hover:text-indigo-600 transition p-2">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (empty($products)): ?>
    <div class="p-20 text-center text-slate-400">
        <i class="fas fa-box-open text-4xl mb-3"></i>
        <p>Tidak ada produk di kategori ini.</p>
    </div>
<?php endif; ?>