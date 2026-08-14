<?php

namespace App\Models;

use App\Core\Database\BaseModel;
use PDO;
// use stdClass;

class Product extends BaseModel
{
    /**
     * The static keyword must be added to match the BaseModel
     */
    protected static string $table = 'products';
    protected static string $primaryKey = 'id';

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    /**
     * Retrieve all products whose stock is considered critical (critical_status = 1)
     * Output: Array of Objects
     */
    public static function getCriticalStock(int $threshold = 5): array
    {
        $items = static::query()
            ->where('status_kritis', '=', 1)
            ->where('stok', '<=', $threshold)
            ->orderBy('stok', 'ASC')
            ->get();

        return static::toObject($items);
    }

    /**
     * Filter products by enum category ('fertilizer','seed','pesticide','tool')
     * Output: Array of Objects
     */
    public static function getByKategori(string $kategori): array
    {
        $items = static::where('kategori', '=', $kategori)->get();

        return static::toObject($items);
    }
    
    /**
     * Retrieve product inventory statistics along with AI insights.
     * Fully compatible with MySQL and PostgreSQL.
     * @param string $category
     * @return object (stdClass)
     */
    public static function getInventoryStats(string $category = 'all'): object
    {
        // 1. Query Data Produk Berdasarkan Kategori
        $query = static::query()->select(['nama', 'stok']);

        if ($category !== 'all') {
            $query->where('kategori', '=', $category);
        }

        $result = $query->orderBy('created_at', 'DESC')->get();
        // Konversi ke object jika data hasil query berupa array
        $resultObj = static::toObject($result);

        $labels    = [];
        $values    = [];
        $totalStok = 0;

        foreach ($resultObj as $row) {            
            // Truncate nama produk jika lebih dari 12 karakter
            $labels[]  = strlen((string) $row->nama) > 12 ? substr((string) $row->nama, 0, 10) . '..' : $row->nama;
            $values[]  = (int) $row->stok;
            $totalStok += (int) $row->stok;
        }

        // 2. Query Total Stok Per Kategori
        $sqlCat = "SELECT kategori, SUM(stok) as ttl_stok FROM products GROUP BY kategori";
        $resultCatRaw = static::rawQuery($sqlCat);        

        // Map ke array of objects
        // $resultCat = array_map(fn($item) => (object) $item, $resultCatRaw);
        $resultCat = static::toObject($resultCatRaw);

        // Extra Data 'alat' jika diperlukan
        $sqlAlat = "SELECT COUNT(0) as ttl_alat FROM assets";
        $resultAlat = static::rawQuery($sqlAlat);
        // \write_log('debug', $resultAlat);

        $resultCat[] = (object) [
            'kategori' => 'alat',
            'ttl_stok' => $resultAlat[0]->ttl_alat
        ];

        $labelsCat    = [];
        $valuesCat    = [];
        $totalStokCat = 0;

        foreach ($resultCat as $row) {
            $labelsCat[]    = ucwords((string) $row->kategori);
            $valuesCat[]    = (int) $row->ttl_stok;
            $totalStokCat  += (int) $row->ttl_stok;
        }

        // 3. Logika AI Insight Sederhana
        $countValues = count($values);
        $avgStok     = $countValues > 0 ? $totalStok / $countValues : 0;
        $isKritis    = $avgStok < 20;

        $msg = $isKritis
            ? "AI Alert: Stok rata-rata kategori {$category} sangat rendah (" . round($avgStok, 1) . "). Segera cek gudang!"
            : "AI Insight: Perputaran stok {$category} terpantau sehat.";

        // 4. Return sebagai Object (stdClass)
        return (object) [
            'labels'       => $labels,
            'values'       => $values,
            'totalStok'    => $totalStok,
            'labelsCat'    => $labelsCat,
            'valuesCat'    => $valuesCat,
            'totalStokCat' => $totalStokCat,
            'isKritis'     => $isKritis,
            'msg'          => $msg
        ];
    }

    /**
     * Ambil data produk ter-paginasi beserta filter search & kategori.
     * Kompatibel penuh dengan MySQL & PostgreSQL.
     * @return array
     */
    public static function getPaginatedProducts(?string $search = null, ?string $kategori = null, int $currentPage = 1, int $perPage = 5): array
    {
        // 1. Inisialisasi QueryBuilder dari BaseModel
        $query = static::query();

        // 2. Terapkan Filter Search
        if (!empty($search)) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        // 3. Terapkan Filter Kategori
        if (!empty($kategori) && $kategori !== 'all') {
            $query->where('kategori', '=', $kategori);
        }

        // 4. Urutkan Data
        $query->orderBy('created_at', 'DESC');

        // 5. Eksekusi Paginate Bawaan QueryBuilder
        $result = $query->paginate($perPage, $currentPage);
        $totalPages = $result['meta']['last_page'];

        // 6. Susun Output
        return [
            'products'        => static::toObject($result['data']),
            'category'        => $kategori,
            'search'          => $search,
            'currentPage'     => $result['meta']['current_page'],
            'totalPages'      => $totalPages,
            'paginationItems' => static::getPaginationRange($currentPage, $totalPages),
            // 'meta'            => $result['meta'], // Metadata bawaan (total, per_page, dll)
        ];
    }

}