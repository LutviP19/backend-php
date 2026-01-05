<?php
// Load Composer & Framework Core Anda
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/bootstrap.php'; // Sesuaikan dengan cara framework Anda loading

use App\Services\AIService;

// 1. Ambil Data Stok Kritis dari Database
$db = \App\Core\DB::getInstance(); // Sesuaikan dengan class DB framework Anda
$lowStock = $db->query("SELECT nama_barang, stok FROM inventory WHERE stok <= batas_minimum")->fetchAll();

if (empty($lowStock)) {
    exit("Tidak ada stok kritis hari ini.\n");
}

// 2. Minta AI membuatkan pesan singkat untuk WhatsApp
$ai = new AIService();
$dataPesan = ["barang_kritis" => $lowStock];
$instruksi = "Buat pesan WhatsApp singkat dan urgent untuk owner toko tentang stok barang yang mau habis ini. Gunakan emoji agar menarik.";

$pesanAI = $ai->askCustom($dataPesan, $instruksi);

// 3. Kirim via WhatsApp API (Contoh: Fonnte/Wablas)
$token = 'YOUR_WA_TOKEN';
$target = '08123456789'; // Nomor klien

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.fonnte.com/send',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => array(
    'target' => $target,
    'message' => $pesanAI,
  ),
  CURLOPT_HTTPHEADER => array("Authorization: $token"),
));

$response = curl_exec($curl);
curl_close($curl);

echo "Laporan terkirim ke WhatsApp klien.\n";


//=====================

// Ambil data stok rendah
$lowStock = $db->query("SELECT nama_barang, stok, batas_minimum FROM inventory WHERE stok <= batas_minimum")->fetchAll();

// Ambil data barang paling laku (misal 30 hari terakhir)
$topSelling = $db->query("SELECT nama_barang, SUM(qty) as total_terjual FROM sales_detail WHERE created_at > NOW() - INTERVAL 30 DAY GROUP BY nama_barang ORDER BY total_terjual DESC LIMIT 5")->fetchAll();

// Gabungkan data untuk dikirim ke AI
$inventoryData = [
    'stok_kritis' => $lowStock,
    'barang_populer' => $topSelling,
    'tanggal_laporan' => date('Y-m-d')
];

class Dashboard {
    // Di dalam file Controller Anda
    public function dashboard() {
        // 1. Ambil data mentah dari DB (Contoh query native)
        $db = new \PDO("mysql:host=localhost;dbname=bisnis_anda", "user", "pass");
        $stmt = $db->query("SELECT produk, COUNT(*) as jumlah, SUM(total) as pendapatan FROM sales GROUP BY produk");
        $dataSales = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Panggil AI Service
        $ai = new \App\Services\AIService();
        $insight = $ai->generateInsight($dataSales, "Bapak Budi");

        // 3. Kirim ke View
        return $this->view('admin/dashboard', [
            'data' => $dataSales,
            'ai_insight' => $insight
        ]);
    }

    public function generateInventoryInsight($data) {
        $prompt = "Analisis data inventaris berikut:\n" . json_encode($data) . "\n\n";
        $prompt .= "Tugas Anda:
        1. Identifikasi barang yang harus segera di-restock agar tidak kehilangan momen penjualan.
        2. Berikan saran strategi untuk barang yang stoknya masih banyak tapi penjualannya lambat.
        3. Prediksi risiko gudang untuk 2 minggu ke depan berdasarkan tren ini.
        
        Format jawaban dalam bahasa Indonesia yang ringkas dan poin-poin.";

        // Kirim ke API OpenAI/Gemini...
    }
}

//=====================