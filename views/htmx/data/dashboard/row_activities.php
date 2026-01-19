<?php

function renderPaginationButtons($page, $total_pages) {
    $prev = $page - 1;
    $next = $page + 1;
    $disabledPrev = ($page <= 1) ? 'disabled opacity-50' : '';
    $disabledNext = ($page >= $total_pages) ? 'disabled opacity-50' : '';

    echo "<button hx-get='data-chart.php/activities?page={$prev}' hx-include='#search-input, [name=\"category\"]' hx-target='#activity-table-body' class='w-9 h-9 border rounded-xl hover:bg-slate-50' {$disabledPrev}><i class='fas fa-chevron-left text-xs'></i></button>";
    
    for ($i = 1; $i <= $total_pages; $i++) {
        $activeClass = ($i == $page) ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50';
        echo "<button hx-get='data-chart.php/activities?page={$i}' hx-include='#search-input, [name=\"category\"]' hx-target='#activity-table-body' class='w-9 h-9 rounded-xl font-bold text-xs transition-all {$activeClass}'>{$i}</button>";
    }

    echo "<button hx-get='data-chart.php/activities?page={$next}' hx-include='#search-input, [name=\"category\"]' hx-target='#activity-table-body' class='w-9 h-9 border rounded-xl hover:bg-slate-50' {$disabledNext}><i class='fas fa-chevron-right text-xs'></i></button>";
}


if (empty($paged_data)) {
    //echo '<tr><td colspan="4" class="px-8 py-10 text-center text-slate-400 italic">Data tidak ditemukan.</td></tr>';
?>

<tr>
    <td colspan="4" class="px-8 py-20">
        <div class="flex flex-col items-center justify-center text-center animate-in fade-in zoom-in duration-500">
            <div class="relative mb-6">
                <div class="absolute inset-0 bg-indigo-100 rounded-full blur-2xl opacity-50 animate-pulse"></div>
                <div class="relative w-20 h-20 bg-indigo-50 rounded-[2rem] flex items-center justify-center border border-indigo-100 shadow-inner">
                    <i class="fas fa-robot text-3xl text-indigo-500"></i>
                </div>
                <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-rose-50 rounded-full flex items-center justify-center border-2 border-white text-rose-500 shadow-sm">
                    <i class="fas fa-search text-xs"></i>
                </div>
            </div>

            <h4 class="text-lg font-bold text-slate-800 mb-2">Data Tidak Ditemukan</h4>
            <p class="text-slate-500 text-sm max-w-xs leading-relaxed">
                Asisten AI tidak dapat menemukan aktivitas untuk kata kunci <span class="font-bold text-indigo-600">"<?= htmlspecialchars($search) ?>"</span> di kategori ini.
            </p>

            <button 
                @click="$dispatch('reset-filters')" 
                class="mt-6 px-6 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-indigo-200 transition-all shadow-sm flex items-center gap-2"
            >
                <i class="fas fa-undo-alt text-indigo-500"></i>
                Bersihkan Semua Filter
            </button>
        </div>
    </td>
</tr>

<tr style="display:none">
    <td>
    <p id="pagination-info" hx-swap-oob="true" class="text-slate-400 italic">Tidak ada data untuk ditampilkan</p>'
    <div id="pagination-controls" hx-swap-oob="true" class="flex items-center gap-2"></div>
    </td>
</tr>

<?php
    exit; // Data empty
}

// Render Fragment HTML
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
// 4. OOB Swap untuk Footer
?>
<tr style="display:none">
    <td>
    <p id="pagination-info" hx-swap-oob="true" class="text-xs font-medium text-slate-500">
        Menampilkan halaman <?= $page ?> dari <?= $total_pages ?> halaman
    </p>

    <div id="pagination-controls" hx-swap-oob="true" class="flex items-center gap-2">
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
    </td>
</tr>