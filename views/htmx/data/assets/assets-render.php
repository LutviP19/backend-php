<?php 
if (empty($filtered)) {
    // Tampilan jika data tidak ditemukan
    echo '
    <div class="col-span-full py-20 flex flex-col items-center justify-center text-center animate-in fade-in zoom-in duration-500">
        <div class="relative mb-6">
            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center border-4 border-white shadow-sm">
                <i class="fas fa-tractor text-4xl text-slate-200"></i>
            </div>
            <div class="absolute -bottom-1 -right-1 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center text-rose-500 border-2 border-slate-50">
                <i class="fas fa-search text-xs"></i>
            </div>
        </div>

        <h3 class="text-xl font-bold text-slate-700 mb-2">Unit Tidak Ditemukan</h3>
        <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed italic">
            Maaf, kami tidak menemukan armada dengan ID <span class="text-indigo-600 font-bold">"'.htmlspecialchars($search).'"</span> atau status tersebut.
        </p>

        <button 
            @click="resetAssets()"
            class="mt-8 flex items-center gap-2 px-6 py-3 bg-indigo-50 text-indigo-600 rounded-2xl text-xs font-black hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-100"
        >
            <i class="fas fa-redo-alt"></i>
            Kembalikan Semua Data
        </button>
    </div>';
    exit;
}


if ($viewMode === 'list'): ?>
    <div class="bg-white border border-slate-100 rounded-[2.5rem] overflow-hidden shadow-sm shadow-slate-200/50">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/50 border-b border-slate-100">
                <tr>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Armada</th>
                    <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kesehatan</th>
                    <th class="px-6 py-5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="asset-container-body">
            <?php foreach ($filtered as $u): 
                
                // Di dalam loop foreach ($filtered as $u)
                $statusMap = [
                    'ready'       => ['label' => 'Ready', 'class' => 'text-emerald-500 bg-emerald-50'],
                    'maintenance' => ['label' => 'Maintenance', 'class' => 'text-rose-500 bg-rose-50'],
                    'working'     => ['label' => 'Working', 'class' => 'text-blue-500 bg-blue-50']
                ];

                $currentStatus = $statusMap[$u['status']];

                // Logic warna progress bar berdasarkan health
                $healthColor = 'bg-emerald-500';
                if ($u['health'] < 40) $healthColor = 'bg-rose-500';
                elseif ($u['health'] < 75) $healthColor = 'bg-amber-500';
                
            ?>
            <tr class="hover:bg-slate-50 transition-all group">
                <td class="px-8 py-4">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $u['icon'] ?> text-<?= $u['color'] ?>-500"></i>
                        <span class="font-bold text-slate-700 text-sm"><?= $u['asset_id'] ?></span>
                    </div>
                    <p class="text-xs text-slate-800 line-clamp-1"><?= $u['name'] ?></p>
                </td>
                <td class="px-6 py-4">
                    <span class="text-xs font-bold italic <?= $currentStatus['class'] ?>">
                        <?= ucfirst($currentStatus['label']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 w-48">
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="<?= $healthColor ?> h-full" style="width: <?= $u['health'] ?>%"></div> 
                    </div>
                    <span class="text-[11px] font-black <?= $healthTextColor ?> min-w-[35px] text-right">
                        <?= $u['health'] ?>%
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button 
                        hx-get="<?= url('/data/asset-edit?id=' . $u['id']) ?>" 
                        hx-target="#log-details-content" 
                        @click="$dispatch('open-log-modal')" 
                        class="text-indigo-600 font-bold text-xs hover:underline">
                        Kelola
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-in fade-in duration-500"  id="asset-container-body">
    <?php foreach ($filtered as $u): ?>
        <?php 
            // Logika Warna Status
            $statusStyle = [
                'ready'       => 'bg-emerald-50 text-emerald-600',
                'working'     => 'bg-blue-50 text-blue-600',
                'maintenance' => 'bg-rose-50 text-rose-600'
            ];
            $currentStyle = $statusStyle[$u['status']] ?? 'bg-slate-50 text-slate-600';

            // Logika Warna Progress Bar (Health)
            $healthColor = 'bg-emerald-500';
            if ($u['health'] < 40) $healthColor = 'bg-rose-500';
            elseif ($u['health'] < 75) $healthColor = 'bg-amber-500';
        ?>
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:border-indigo-500 hover:shadow-md transition-all duration-300 group animate-in zoom-in duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Unit ID</span>
                    <p class="text-sm font-bold text-slate-700"><?= $u['asset_id'] ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $currentStyle ?>">
                    <?= $u['status'] ?>
                </span>
            </div>

            <div class="h-32 bg-slate-50 rounded-2xl mb-5 flex items-center justify-center relative overflow-hidden">
                <i class="fas <?= $u['icon'] ?> text-5xl 
                    text-<?= $u['color'] ?>-500 
                    group-hover:text-<?= $u['color'] ?>-600 
                    group-hover:scale-110 transition-all duration-500">
                </i>
                
                <div class="absolute inset-0 bg-indigo-600/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <button 
                        hx-get="<?= url('/data/asset-logs?id=' . $u['id']) ?>" 
                        hx-target="#log-details-content"
                        @click="$dispatch('open-log-modal')" 
                        class="text-white text-xs font-bold px-4 py-2 border border-white/30 rounded-xl hover:bg-white hover:text-indigo-600 transition">
                        Lihat Log Servis
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 line-clamp-1"><?= $u['name'] ?></p>
                    <p class="text-[11px] text-slate-400 italic">Terakhir dicek: <?= date('d M Y', strtotime($u['updated_at'])) ?></p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Health Score</span>
                        <span class="text-xs font-black text-slate-700"><?= $u['health'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="h-full <?= $healthColor ?> transition-all duration-1000 ease-out" 
                             style="width: <?= $u['health'] ?>%"></div>
                    </div>
                </div>

                <button 
                    hx-get="<?= url('/data/asset-edit?id=' . $u['id']) ?>" 
                    hx-target="#log-details-content" 
                    @click="$dispatch('open-log-modal')" 
                    class="w-full py-3 bg-slate-50 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 rounded-2xl text-xs font-bold transition-colors">
                    Kelola Unit
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<span id="asset-count-info" hx-swap-oob="innerHTML">
    Total: <?= count($filtered) ?> Unit ditemukan
</span>


<?php
$stats = [
    'ready'   => (string)count(array_filter($filtered, fn($u) => $u['status'] === 'ready')),
    'maint'   => (string)count(array_filter($filtered, fn($u) => $u['status'] === 'maintenance')),
    'working' => (string)count(array_filter($filtered, fn($u) => $u['status'] === 'working')),
    'unitNames' => array_column($filtered, 'asset_id'),
    'unitHealths' => array_column($filtered, 'health'),
];
?>

<script id="chart-data-json" type="application/json" hx-swap-oob="innerHTML">
    <?= json_encode($stats, JSON_PRETTY_PRINT) ?>
</script>