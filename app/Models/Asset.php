<?php

namespace App\Models;

use App\Core\Database\BaseModel;
use App\Core\Database\QueryBuilderV2;
// use PDO;

class Asset extends BaseModel
{
    protected static string $table = 'assets';

    /**
     * Mengambil data aktivitas/aset berpaginasi beserta meta total dan total halaman.
     *
     * @param string|null $category Slug kategori
     * @param string|null $search Kata kunci pencarian
     * @param int $page Halaman saat ini
     * @param int $limit Jumlah data per halaman
     * @return array{data: array, total_items: int, total_pages: int, page: int, limit: int}
     */
    public function getPaginatedActivities(?string $category = null, ?string $search = null, int $page = 1, int $limit = 10): array
    {
        // 1. Base Query & Where Conditions
        $baseFrom = " FROM activities a JOIN categories c ON a.category_id = c.id WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $baseFrom .= " AND c.slug = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $baseFrom .= " AND (a.title LIKE ? OR a.member LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 2. Hitung Total Data untuk Pagination
        $countSql = "SELECT COUNT(0) as total_data {$baseFrom}";
        
        $totalResult = $this->newCustomQuery()->queryRaw($countSql, $params);
        
        $totalItems = (int) ($totalResult[0]->total_data ?? 0);
        $totalPages = (int) ceil($totalItems / $limit);
        
        // Proteksi range halaman
        $currentPage = max(1, min($page, $totalPages ?: 1));
        $offset = ($currentPage - 1) * $limit;

        // 3. Ambil Data dengan Order, Limit, & Offset
        $dataSql = "SELECT 
                        a.id, 
                        a.title, 
                        a.member, 
                        c.display_name AS category_name, 
                        a.time, 
                        a.status, 
                        c.slug AS cat, 
                        COALESCE(a.icon, c.default_icon) AS icon, 
                        COALESCE(a.color, c.default_color) AS color 
                    {$baseFrom} 
                    ORDER BY a.time ASC 
                    LIMIT {$limit} OFFSET {$offset}";

        $rawResult = $this->newCustomQuery()->queryRaw($dataSql, $params);

        // 4. Transform hasil dari Object ke Array secara native (tanpa json_encode/decode)
        $data = array_map(static fn($item) => (array) $item, $rawResult);

        return [
            'data'        => $data,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'page'        => $currentPage,
            'limit'       => $limit,
        ];
    }


    /**
     * Mengambil data list asset ter-filter (Substitusi logic assets_render)
     */
    public function getFilteredAssets(array $params = []): array
    {
        $search = $params['search'] ?? '';
        $status = $params['status_filter'] ?? '';
        $page   = isset($params['page']) ? (int) $params['page'] : 1;
        $limit  = isset($params['limit']) ? (int) $params['limit'] : 5;
        $offset = ($page - 1) * $limit;

        $bindings = [];
        $queryStr = " FROM " . static::$table . " a LEFT JOIN asset_categories c ON a.category_id = c.id WHERE 1=1";

        if ($status !== '' && $status !== null) {
            $queryStr .= " AND a.status = ?";
            $bindings[] = $status;
        }

        if ($search && $search !== '') {
            // Pakai LIKE standar tanpa operator || atau CONCAT di SQL
            $queryStr .= " AND (a.name LIKE ? OR a.asset_id LIKE ?)";
            
            // Gabungkan wildcard % di PHP
            $searchTerm = '%' . $search . '%';
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        $queryStr .= " ORDER BY a.updated_at DESC, c.category_name ASC, a.asset_id ASC";

        $sql = "SELECT a.id, a.asset_id, a.name, a.status, a.health, a.icon, a.color, a.updated_at, c.category_name" . $queryStr;

        // Gunakan $this->newCustomQuery() agar mengalirkan $this->pdo milik instance
        $objectData = $this->newCustomQuery()->queryRaw($sql, $bindings);

        return json_decode(json_encode($objectData), true) ?? [];
    }

    /**
     * Mengambil riwayat maintenance log berdasarkan ID Asset (Substitusi logic assets_logs)
     */
    public function getMaintenanceLogs(int $assetId): array
    {
        $sql = "SELECT a.asset_id as unit_id, a.name as unit_name, l.maintenance_date as date, l.task, l.status 
                FROM asset_maintenance_logs l
                JOIN " . static::$table . " a ON l.asset_id = a.id
                WHERE a.id = ? 
                ORDER BY l.maintenance_date DESC";

        $objectData = $this->newCustomQuery()->queryRaw($sql, [$assetId]);

        return json_decode(json_encode($objectData), true) ?? [];
    }

    // =========================================================================
    // FUNGSI PENDUKUNG LAINNYA
    // =========================================================================

    /**
     * Filter Asset Berdasarkan Category ID
     */
    public function getByKategori(int $categoryId, int $limit = 10): array
    {
        $sql = "SELECT a.*, c.category_name 
                FROM " . static::$table . " a 
                LEFT JOIN asset_categories c ON a.category_id = c.id 
                WHERE a.category_id = ? 
                ORDER BY a.name ASC 
                LIMIT {$limit}";

        $objectData = $this->newCustomQuery()->queryRaw($sql, [$categoryId]);

        return json_decode(json_encode($objectData), true) ?? [];
    }

    /**
     * Hitung total asset ter-filter (untuk Pagination)
     */
    public function countFilteredAssets(array $params = []): int
    {
        $search = $params['search'] ?? '';
        $status = $params['status_filter'] ?? '';

        $bindings = [];
        $sql = "SELECT COUNT(0) as total FROM " . static::$table . " a WHERE 1=1";

        if ($status !== '' && $status !== null) {
            $sql .= " AND a.status = ?";
            $bindings[] = $status;
        }

        if ($search !== '' && $search !== null) {
            // Pakai LIKE standar tanpa operator || atau CONCAT di SQL
            $queryStr .= " AND (a.name LIKE ? OR a.asset_id LIKE ?)";
            
            // Gabungkan wildcard % di PHP
            $searchTerm = '%' . $search . '%';
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        $result = $this->newCustomQuery()->queryRaw($sql, $bindings);

        return (int) ($result->total ?? 0);
    }

    /**
     * Ambil detail asset berdasarkan VARCHAR asset_id (e.g. 'AST-001')
     */
    public function findByAssetId(string $id): ?array
    {
        $sql = "SELECT a.id, a.asset_id, a.category_id, a.name, a.status, a.health, a.icon, a.color, ac.category_name  
                FROM assets a LEFT JOIN asset_categories ac ON a.category_id = ac.id WHERE a.id = ? LIMIT 1";

        $result = $this->newCustomQuery()->queryRaw($sql, [$id]);

        return $result ? json_decode(json_encode($result[0]), true) : null;
    }

    /**
     * Helper untuk membentuk QueryBuilderV2 yang selalu membawa $this->pdo
     */
    public function newCustomQuery(): QueryBuilderV2
    {
        return QueryBuilderV2::table($this->getPdo(), static::$table);
    }
}