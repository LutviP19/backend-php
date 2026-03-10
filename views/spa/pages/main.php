<?php
// Mendapatkan endpoint dari URL (Contoh sederhana)
$path = $_SERVER['REQUEST_URI'];

// 1. Response untuk Tab Inventaris
if (strpos($path, '/demo/inventory') !== false) {
    ?>
<div class="animate-in fade-in duration-500">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-bold text-slate-800 dark:text-white text-lg">Stok Pupuk & Benih</h3>
        <span class="text-xs font-medium text-slate-400 dark:text-slate-500">Terakhir update: Just now</span>
    </div>

    <div class="space-y-4">
        <?php 
        $items = [
            ['name' => 'Pupuk Urea Pro', 'stock' => 85, 'color' => 'bg-emerald-500'],
            ['name' => 'Benih Padi Unggul', 'stock' => 40, 'color' => 'bg-amber-500'],
            ['name' => 'Pestisida Cair', 'stock' => 12, 'color' => 'bg-rose-500']
        ];

        foreach ($items as $item): 
            // Logika Stok Kritis (di bawah 20%)
            $isLow = $item['stock'] < 20;
            // Jika kritis, gunakan warna merah, jika tidak gunakan warna aslinya
            $barColor = $isLow ? 'bg-rose-600' : $item['color'];
            // Tambahkan efek glow/berdenyut jika stok kritis
            $pulseClass = $isLow ? 'animate-pulse' : '';
        ?>
            <div class="bg-white dark:bg-white/5 p-4 rounded-2xl border <?= $isLow ? 'border-rose-200 dark:border-rose-900/50' : 'border-slate-100 dark:border-white/10' ?> flex items-center justify-between shadow-sm transition-all">
                
                <div class="flex flex-col">
                    <span class="text-sm font-semibold <?= $isLow ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' ?>">
                        <?= $item['name'] ?>
                    </span>
                    <?php if ($isLow): ?>
                        <span class="text-[10px] font-bold uppercase text-rose-500 tracking-tight">Stok Hampir Habis!</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-32 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="<?= $barColor ?> <?= $pulseClass ?> h-full transition-all duration-1000" 
                             style="width: <?= $item['stock'] ?>%">
                        </div>
                    </div>
                    
                    <span class="text-xs font-bold <?= $isLow ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' ?> w-8 text-right">
                        <?= $item['stock'] ?>%
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
    <?php
    exit;
}

// 2. Response untuk Tab Prediksi AI
if (strpos($path, '/demo/prediction') !== false) {
    ?>
<div class="animate-in slide-in-from-bottom-4 duration-500 text-center py-10">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-2xl mb-6 transition-colors">
        <i class="fas fa-brain text-2xl"></i>
    </div>

    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2 transition-colors">
        Analisis AI AgriSmart
    </h3>

    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-sm mx-auto mb-8 transition-colors">
        Berdasarkan tren cuaca, AI memprediksi penggunaan pupuk akan meningkat 
        <span class="text-emerald-600 dark:text-emerald-400 font-bold">20%</span> bulan depan.
    </p>

    <div class="p-6 bg-indigo-900 dark:bg-indigo-950 rounded-3xl text-left relative overflow-hidden border border-transparent dark:border-indigo-500/30 transition-all">
        <div class="relative z-10">
            <p class="text-indigo-300 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest mb-2">
                Rekomendasi Tindakan
            </p>
            <p class="text-white dark:text-indigo-50 text-sm font-medium leading-relaxed">
                Segera pesan 50 sak Pupuk Urea sebelum tanggal 15 Januari untuk menghindari kenaikan harga musiman.
            </p>
        </div>
        
        <i class="fas fa-bolt absolute -right-4 -bottom-4 text-white/5 dark:text-indigo-500/10 text-8xl"></i>
    </div>
</div>
    <?php
    exit;
}

// 2. Response untuk Tab Prediksi AI
if (strpos($path, '/demo/suppliers') !== false) {
// Data dummy supplier
$suppliers = [
    [
        'name' => 'PT. Tani Makmur Sentosa',
        'type' => 'Pupuk & Pestisida',
        'rating' => 4.9,
        'status' => 'Buka',
        'location' => 'Surabaya',
        'image' => 'https://ui-avatars.com/api/?name=TMS&background=4f46e5&color=fff'
    ],
    [
        'name' => 'CV. Benih Unggul Nusantara',
        'type' => 'Benih Padi & Jagung',
        'rating' => 4.7,
        'status' => 'Tutup',
        'location' => 'Malang',
        'image' => 'https://ui-avatars.com/api/?name=BUN&background=10b981&color=fff'
    ]
];
?>
<div class="animate-in fade-in zoom-in-95 duration-500">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="font-bold text-slate-800 dark:text-white text-lg transition-colors">Partner Supplier Terverifikasi</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Dapatkan harga terbaik dari supplier terdekat</p>
        </div>
        <button class="text-indigo-600 dark:text-indigo-400 text-xs font-bold hover:underline transition-colors">Lihat Semua</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($suppliers as $s): ?>
            <div class="bg-white dark:bg-white/5 p-5 rounded-[2rem] border border-slate-100 dark:border-white/10 shadow-sm hover:shadow-md dark:hover:bg-white/10 transition-all group">
                <div class="flex items-start gap-4">
                    <img src="<?= $s['image'] ?>" class="w-12 h-12 rounded-2xl shadow-inner dark:opacity-90" alt="logo">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                <?= $s['name'] ?>
                            </h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full transition-colors 
                                <?= $s['status'] == 'Buka' 
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' 
                                    : 'bg-slate-50 dark:bg-white/5 text-slate-400 dark:text-slate-500' ?>">
                                <?= $s['status'] ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-3"><?= $s['type'] ?> • <?= $s['location'] ?></p>
                        
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center text-amber-400 text-[10px]">
                                <i class="fas fa-star mr-1"></i>
                                <span class="font-bold text-slate-700 dark:text-slate-300"><?= $s['rating'] ?></span>
                            </div>
                            
                            <button hx-get="/demo/contact-supplier?id=1" 
                                    hx-swap="outerHTML"
                                    class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-indigo-600 dark:hover:bg-indigo-600 hover:text-white transition-all">
                                Hubungi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
    exit;
}

// 3. Response untuk Form Subscribe (POST)
if (strpos($path, '/subscribe') !== false) {
    $email = $_POST['email'] ?? '';
    // Memberitahu HTMX untuk memicu event 'celebrate' di browser
    header('HX-Trigger: celebrate');
    // Simulasi loading 1 detik
    sleep(1);
    echo '
    <div class="animate-in zoom-in duration-500 py-4">
        <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold mb-2">Berhasil Terdaftar!</h2>
        <p class="text-slate-400">Terima kasih '.$email.'. Cek inbox Anda segera.</p>
    </div>
    ';
    exit;
}