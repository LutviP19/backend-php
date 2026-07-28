<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST Digital - <?= htmlspecialchars($bast['no_bast'] ?? 'BAST-001') ?></title>
    <script src="<?= assets('/js/cdn-tailwindcss.js') ?>"></script>
    <style>
        /* Ukuran & Margin Presisi Kertas A4 */
        @page {
            size: A4;
            margin: 15mm 20mm 15mm 20mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-white text-slate-800 font-sans p-4">

    <!-- HEADER SURAT -->
    <div class="border-b-2 border-slate-800 pb-4 mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold uppercase tracking-wider text-slate-900">BERITA ACARA SERAH TERIMA</h1>
            <p class="text-xs text-slate-500 font-semibold">Nomor: <?= htmlspecialchars($bast['no_bast'] ?? 'BAST/2026/07/001') ?></p>
        </div>
        <div class="text-right">
            <p class="text-sm font-bold text-indigo-600">KOPERASI DIGITAL</p>
            <p class="text-[10px] text-slate-400">Tanggal: <?= date('d F Y') ?></p>
        </div>
    </div>

    <!-- ISI SURAT -->
    <p class="text-xs text-slate-600 leading-relaxed mb-4">
        Pada hari ini <span class="font-semibold"><?= date('l, d F Y') ?></span>, telah dilakukan serah terima barang/layanan dengan rincian sebagai berikut:
    </p>

    <!-- TABEL DETAIL RINCIAN -->
    <table class="w-full text-xs border-collapse border border-slate-200 mb-8">
        <thead>
            <tr class="bg-slate-100 text-slate-700">
                <th class="border border-slate-200 px-3 py-2 text-left">No</th>
                <th class="border border-slate-200 px-3 py-2 text-left">Deskripsi Aset / Inventaris</th>
                <th class="border border-slate-200 px-3 py-2 text-center">Jumlah</th>
                <th class="border border-slate-200 px-3 py-2 text-left">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-slate-200 px-3 py-2">1</td>
                <td class="border border-slate-200 px-3 py-2 font-medium"><?= htmlspecialchars($bast['title'] ?? 'Alat Berat Excavator PC200') ?></td>
                <td class="border border-slate-200 px-3 py-2 text-center">1 Unit</td>
                <td class="border border-slate-200 px-3 py-2 text-emerald-600 font-bold">Baik / Layak Operasional</td>
            </tr>
        </tbody>
    </table>

    <!-- TANDA TANGAN DIGITAL -->
    <div class="grid grid-cols-2 gap-8 pt-12">
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-16">Pihak Pertama (Penyerah)</p>
            <p class="text-xs font-bold text-slate-800 underline"><?= htmlspecialchars($bast['penyerah'] ?? 'Admin Koperasi') ?></p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-16">Pihak Kedua (Penerima)</p>
            <p class="text-xs font-bold text-slate-800 underline"><?= htmlspecialchars($bast['penerima'] ?? 'Anggota Koperasi') ?></p>
        </div>
    </div>

</body>
</html>