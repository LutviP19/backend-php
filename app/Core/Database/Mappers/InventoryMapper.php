<?php
namespace App\Core\Database\Mappers;

class InventoryMapper {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Mengambil data ringkasan stok untuk dianalisis AI
     */
    public function getSummaryForAI() {
        // 1. Ambil barang yang stoknya di bawah minimum
        $sqlKritis = "SELECT name, stok_sekarang, min_stock, unit_price 
                      FROM products 
                      WHERE stok_sekarang <= min_stock";
        $stokKritis = $this->db->query($sqlKritis)->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Hitung 'Burn Rate' (Kecepatan barang keluar) 7 hari terakhir
        // Ini membantu AI memprediksi kapan stok benar-benar habis
        $sqlBurnRate = "SELECT p.name, SUM(l.quantity) as total_keluar
                        FROM inventory_log l
                        JOIN products p ON l.product_id = p.id
                        WHERE l.type = 'out' AND l.created_at > NOW() - INTERVAL 7 DAY
                        GROUP BY p.id
                        ORDER BY total_keluar DESC LIMIT 5";
        $fastMoving = $this->db->query($sqlBurnRate)->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Identifikasi barang 'Mati' (Tidak ada penjualan dalam 30 hari)
        $sqlSlowMoving = "SELECT name, stok_sekarang, unit_price 
                          FROM products 
                          WHERE id NOT IN (
                              SELECT product_id FROM inventory_log 
                              WHERE type = 'out' AND created_at > NOW() - INTERVAL 30 DAY
                          ) AND stok_sekarang > 0 LIMIT 5";
        $slowMoving = $this->db->query($sqlSlowMoving)->fetchAll(\PDO::FETCH_ASSOC);

        // Gabungkan dalam satu paket data yang bersih
        return [
            'kritis' => $stokKritis,
            'paling_laku_7_hari_terakhir' => $fastMoving,
            'stok_mengendap_30_hari' => $slowMoving,
            'total_nilai_aset_kritis' => array_sum(array_column($stokKritis, 'unit_price'))
        ];
    }
}


// Menghitung kecepatan stok keluar 7 hari terakhir
$query = "SELECT 
            p.name, 
            p.min_stock,
            (SELECT SUM(quantity) FROM inventory_log 
             WHERE product_id = p.id AND type = 'out' 
             AND created_at > NOW() - INTERVAL 7 DAY) as kelancaran_7_hari,
            p.stok_sekarang
          FROM products p 
          WHERE p.stok_sekarang <= p.min_stock";

          

// Inisialisasi Database & Mapper
$db = new \PDO("mysql:host=localhost;dbname=nama_db", "user", "pass");
$mapper = new \App\Mappers\InventoryMapper($db);
$aiService = new \App\Services\AIService();

// 1. Ambil data yang sudah diringkas oleh Mapper
$dataUntukAI = $mapper->getSummaryForAI();

// 2. Kirim data ringkas ke AI (Hemat Token & Lebih Cepat)
if (!empty($dataUntukAI['kritis']) || !empty($dataUntukAI['stok_mengendap_30_hari'])) {
    
    $pesan = $aiService->generateInventoryInsight($dataUntukAI);
    
    // 3. Kirim hasil ke WhatsApp Klien
    $wa->send('08123456789', $pesan);
}