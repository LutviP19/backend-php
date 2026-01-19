<div class="p-8 antialiased">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Log Servis</h3>
            <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-[0.15em]"><?= htmlspecialchars($unitId) ?></p>
            <span class="text-[12px] font-semibold text-slate-600 text-sm tracking-[0.10em]"><?= htmlspecialchars($unitName) ?></span>
        </div>
        <button @click="openModal = false" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="space-y-4">
        <?php if (!empty($logs)): ?>
            <?php foreach ($logs as $log): ?>
            <div class="flex gap-4 p-4 rounded-2xl border border-slate-50 bg-slate-50/50 hover:bg-white hover:border-indigo-100 transition-all group">
                <div class="flex-none w-12 h-12 rounded-xl bg-white flex flex-col items-center justify-center shadow-sm border border-slate-100 group-hover:border-indigo-200">
                    <span class="text-[9px] font-bold text-slate-400 uppercase"><?= date('M', strtotime($log['date'])) ?></span>
                    <span class="text-sm font-extrabold text-slate-700"><?= date('d', strtotime($log['date'])) ?></span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase"><?= date('Y', strtotime($log['date'])) ?></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($log['task']) ?></p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full <?= $log['status'] === 'Selesai' ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight"><?= $log['status'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-12 px-6 bg-slate-50/50 rounded-[2rem] border-2 border-dashed border-slate-200">
                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-4">
                    <i class="fas fa-clipboard-check text-slate-200 text-2xl"></i>
                </div>
                <h4 class="text-sm font-extrabold text-slate-700 mb-1">Belum Ada Riwayat</h4>
                <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                    Unit ini belum pernah tercatat melakukan servis atau pemeliharaan berkala.
                </p>
                <button @click="/* Logic tambah log */" class="mt-5 px-5 py-2 bg-white border border-slate-200 text-[10px] font-bold text-slate-600 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                    Buat Jadwal Servis
                </button>
            </div>
        <?php endif; ?>
    </div>

    <button @click="openModal = false" class="w-full mt-8 py-4 bg-slate-900 text-white rounded-2xl font-semibold text-sm shadow-lg hover:shadow-indigo-500/20 hover:bg-indigo-600 transition-all">
        Tutup Detail
    </button>
</div>