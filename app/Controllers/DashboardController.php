<?php

namespace App\Controllers;

use App\Core\Http\{Request, Response};
use App\Core\Support\Session;
// use App\Core\Database\QueryBuilderV2 as QueryBuilder;
use App\Core\Validation\Validator;
use App\Core\Http\IdempotencyHandler;
use App\Core\Security\IdempotencyManager;
use App\Models\Product;
use App\Models\Asset;

class DashboardController extends Controller
{
    private $assetModel = null;

    public function __construct()
    {
        parent::__construct();

        // $this->assetModel = new Asset();

        // // Allow insomnia, etc...
        // $user_agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
        // $dev_agents = [
        //                 'insomnia',
        //             ];
        // $agentAllow = false;
        // foreach ($dev_agents as $agent) {
        //     if (str_contains(strtolower($user_agent), strtolower($agent))) {
        //         $agentAllow = true;
        //     }
        // }
        // // dd($agentAllow);

        // // Handler reload manual
        // if (!$agentAllow) {
        //     $ignore_uri = ['login', 'logout', 'htmx'];
        //     $uri = trim(request()->uri(), '/');
        //     // dd($uri);
        //     // dd(isHtmx());

        //     if ((request()->method() === 'GET' && ! in_array($uri, $ignore_uri)) || ! isHtmx()) {
        //         // dd($uri);
        //         response()->redirect('/htmx');
        //     }
        // }

        // 1. Allow dev tools (Insomnia, Postman, dll)
        $user_agent = strtolower(trim($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $dev_agents = ['insomnia', 'postman'];

        $agentAllow = false;
        foreach ($dev_agents as $agent) {
            if (str_contains($user_agent, $agent)) {
                $agentAllow = true;
                break;
            }
        }

        // 2. Handler Reload Manual / Direct Browser Access
        if (!$agentAllow) {
            $uri = trim(request()->uri(), '/');

            // Retrieve the main path from the URI (example: "htmx/inventory" -> "htmx")
            $segment1 = explode('/', $uri)[0] ?? '';

            // Ignore excluded routes
            $ignore_uri = ['login', 'logout'];

            // KONDISI REDIRECT:
            // 1. Request berupa GET
            // 2. URI bukan daftar yang diabaikan (login/logout)
            // 3. URI berada di dalam ruang lingkup htmx (misal: htmx/inventory, htmx/assets)
            // 4. URI bukan persis halaman root "htmx"
            // 5. Bukan AJAX HTMX Request (berarti user menekan F5 / hard refresh di browser)
            if (
                request()->method() === 'GET' &&
                ! in_array($uri, $ignore_uri) &&
                $segment1 === 'htmx' &&
                $uri !== 'htmx' &&
                ! isHtmx()
            ) {
                // Eksekusi redirect dan kembalikan response langsung
                response()->redirect('/htmx')->send();

                // Pada OpenSwoole, lemparkan exception ringan/exit handler framework
                // agar proses instansiasi controller terhenti tanpa mematikan Worker Swoole.
                if (function_exists('fastcgi_finish_request')) {
                    exit; // Aman untuk FPM
                } else {
                    throw new \App\Core\Exceptions\RedirectException('/htmx'); // Aman untuk OpenSwoole
                }
            }
        }

    }

    /**
     * Show the home page.
     *
     * @param App\Core\Http\Request $request
     * @param App\Core\Http\Response $response
     * @return void
     */
    public function index(Request $request, Response $response)
    {
        // $users = Model::table('users')->select(['*'])->get();
        // dd($users);
        // Session::set('users', generateUlid());
        $server = \isSwoole() ? "OpenSwoole" : "PHP FPM";

        $dataViews = $this->data_dashboard_activities($request, $response);
        // dd($dataViews);
        $this->view('index-htmx', array_merge(['server' => $server], $dataViews));
    }

    public function login(Request $request, Response $response)
    {
        // dd(Session::all());
        if (Session::has('userdata')) {
            $response->redirect('/htmx')->send();
        }

        $this->view('login');
    }

    public function loginAuth(Request $request, Response $response)
    {
        $user = $request->username ?? '';
        $pass = $request->password ?? '';

        if ($user === 'admin' && $pass === 'desa2026') {

            Session::set('userdata', ['username' => $user]);

            // Using custom header() method in the custom Response
            return $response
                ->header('HX-Trigger', json_encode([
                    'show-toast' => 'Login Berhasil! Mengalihkan...'
                ]))
                ->header('HX-Redirect', '/htmx');
        }

        return $response
            ->header('HX-Trigger', json_encode([
                'play-error-sound' => true
            ]))
            ->setContent("<i class='fas fa-exclamation-triangle mr-1'></i> Username atau Password salah!");
    }

    public function logout(Request $request, Response $response)
    {
        try {
            // Make sure Session destroy safe for OpenSwoole
            if (session_status() === PHP_SESSION_ACTIVE) {
                Session::destroy();
            }
        } catch (\Throwable $e) {
            // Log session errors if any, without shutting down the Swoole worker
            error_log("Session destroy error: " . $e->getMessage(), 0, \logs_path('error_log_php.log'));
        }

        // Use a $response instance passed to the method
        return $response->redirect('/login');
    }

    public function dashboard(Request $request, Response $response)
    {
        $dataViews = $this->data_dashboard_activities($request, $response);
        $this->view('htmx.dashboard', $dataViews);
    }

    public function inventory(Request $request, Response $response)
    {
        $data = $this->getProductsAll('', 'all', 1);
        $stats = $this->inventory_stats('all');

        $data = array_merge($data, ['stats' => $stats]);
        // dd($data, true);
        $this->view('htmx.inventory', $data);
    }

    public function assets(Request $request, Response $response)
    {
        $dataViews = $this->assets_render($request);
        $this->view('htmx.assets', $dataViews);
    }

    public function rental(Request $request, Response $response)
    {
        $this->view('htmx.rental');
    }

    public function rental2(Request $request, Response $response)
    {
        $this->view('htmx.rental2');
    }

    // ===== GET DATA
    protected function inventory_stats($category = 'all')
    {
        return Product::getInventoryStats($category);
    }

    public function inventory_list(Request $request, Response $response)
    {
        $category = $request->category ?? 'all';

        // 1. Ambil Parameter
        // $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search   = $request->search ?? '';
        $kategori = $request->category ?? 'all';
        $currentPage = isset($request->page) ? (int) $request->page : 1;

        $stats = $this->inventory_stats($kategori);
        $data = $this->getProductsAll($search, $kategori, $currentPage);

        $data = array_merge($data, ['stats' => $stats]);

        $this->include('htmx.data.inventory.list', $data, true);
    }

    protected function getProductsAll($search, $kategori, $currentPage)
    {
        $products = Product::getPaginatedProducts($search, $kategori, (int) $currentPage, 5);
        $this->getProducts($products['products']);
        return $products;
    }

    protected function getProducts($products)
    {
        // Di dalam inventory_list setelah query data
        $kritisCount = count(array_filter($products, fn ($p) => $p->stok <= 5));
        $isKritis = ($kritisCount > 0);
        if ($isKritis) {
            $msg = "AI mendeteksi $kritisCount produk dengan stok KRITIS! Segera lakukan pengadaan ulang.";
        } else {
            $msg = "Stok pada kategori ini terpantau AMAN. Belum diperlukan tindakan restock.";
        }

        $triggerPayload = json_encode([
            'update-ai-insight' => [
                'msg'      => $msg,
                'isKritis' => (bool) $isKritis
            ]
        ], JSON_UNESCAPED_SLASHES);

        // Cek dan set di lingkungan OpenSwoole
        if (\isSwoole()) {
            /** @var \OpenSwoole\Http\Response|null $swooleResponse */
            $swooleResponse = $GLOBALS['swoole_response']
                ?? (function_exists('app') && app()->has('swoole_response') ? app('swoole_response') : null);

            if ($swooleResponse) {
                $swooleResponse->header('HX-Trigger', $triggerPayload);
            }
        } else {
            // Fallback untuk PHP-FPM / Standard HTTP
            if (!headers_sent()) {
                header('HX-Trigger: ' . $triggerPayload);
            }
        }
    }

    public function edit_product(Request $request, Response $response)
    {
        $id = $request->id ?? null;

        if (!is_numeric($id)) {
            // header('Content-Type: text/html', true, 422);
            // die("ID tidak valid.");
            htmx_response("ID tidak valid.");
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData, ['id']);

        $product = Product::find($payload['id']);

        $this->view('htmx.modals.inventory.form_edit', ['data' => $product]);
    }
    // ===== END GET DATA

    // ===== CRUD DATA
    public function delete_product(Request $request, Response $response)
    {
        Session::unset('errors');
        $validator = new Validator();
        $validator->validate($request->all(), [
            'id' => 'required|numeric',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            htmx_response("ID tidak valid.", 422);
        }

        $filter = new \App\Core\Validation\Filter();
        $postData = $filter->filter($request->all(), [
            'id' => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData, ['id']);

        $auth = false; // Change it according to your auth logic
        if (false === $auth) {
            htmx_response("Produk ini tidak bisa dihapus, mohon hubungi Admin.", 403);
        }

        $callback = Product::deleteById($id);
        if (false === $callback || !is_numeric($callback)) {
            htmx_response("Gagal menghapus data.", 500);
        }

        // SUCCESSFUL: Send status 200 with empty body for HTMX to delete the row (outerHTML swap)
        htmx_response("", 200);
    }

    public function update_product(Request $request, Response $response)
    {
        // dd($request->all());

        // // Tes apakah header terbaca:
        // $key = IdempotencyHandler::extractIdempotencyKey($request);
        // htmx_response($key);

        // 1. Check & Lock Request Idempotency
        $idempotencyData = IdempotencyHandler::simpleCheck($request);


        if ($idempotencyData) {
            // A. Jika request SUDAH selesai sebelumnya (COMPLETED) -> Replay Cache Response
            if (!empty($idempotencyData['cached'])) {
                if (!headers_sent()) {
                    header('X-Cache-Idempotent: true');
                }
                return $idempotencyData['response'];
            }

            // B. Jika request SEDANG diproses (PROCESSING) -> Return partial view error
            if (!headers_sent()) {
                http_response_code(429);
            }

            $resultDefault =    ['errors' => [
                                    'idempotency' => $idempotencyData['message'] ?? 'Permintaan Anda sedang diproses.'],
                                ];
            // return $this->include('error.406', [
            //     'resultDefault' =>  $resultDefault
            // ]);

            // dd($idempotencyData['message'] ?? 'Permintaan Anda sedang diproses.');
            htmx_response($resultDefault['errors']);
        }

        // 2. Ekstraksi Key
        $idempotencyKey = IdempotencyHandler::extractIdempotencyKey($request);

        try {
            // Validate Input
            Session::unset('errors'); // Clean Errors MessageBag
            $validator = new Validator();
            $validator->validate($request->all(), [
                'nama' => 'required|string|min:3|max:100',
                'kategori'  => 'required|string',
                'stok'  => 'required|numeric',
                'harga'  => 'required|numeric',
                'status_kritis'  => 'optional|numeric',
                'id'  => 'required|numeric',
            ]);
            $errors = Session::get('errors');

            if ($errors) {
                // SANGAT PENTING: Lepas lock agar user bisa memperbaiki input & submit ulang
                if ($idempotencyKey) {
                    IdempotencyManager::unlock($idempotencyKey);
                }

                htmx_response($errors, 422);
            }

            $filter = new \App\Core\Validation\Filter();
            // Filter & Sanitize Input
            $request->status_kritis = isset($request->status_kritis) ? 1 : 0;
            $postData = $filter->filter($request->all(), [
                'nama' => 'trim|sanitize_string',
                'kategori'  => 'trim|sanitize_string',
                'stok'  => 'trim|sanitize_numbers',
                'harga'  => 'trim|sanitize_numbers',
                'status_kritis'  => 'trim|sanitize_numbers',
                'id'  => 'trim|sanitize_numbers',
            ]);
            $payload = $filter->sanitize($postData, ['nama', 'kategori', 'stok', 'harga', 'status_kritis', 'id']);
            // dd($payload);
            // dd(array_values($payload));

            $id = $payload['id'];
            $lastId = Product::updateById($id, array_diff_key($payload, ['id' => true]));

            // dd($lastId);
            if (false === $lastId || !is_numeric($lastId)) {
                htmx_response("Gagal menyimpan data.", 500);
            }

            $htmlOutput = (string) $this->include('htmx.data.inventory.row', $payload);

            // 5. Simpan Response HTML Akhir ke Cache Replay
            if ($idempotencyKey) {
                IdempotencyManager::saveResponse(
                    $idempotencyKey,
                    $htmlOutput,
                    200,
                    300 // TTL 5 Menit
                );
            }


            return $htmlOutput;
        } catch (\Throwable $e) {
            // 6. Lepas lock jika terjadi Exception/System Error
            if ($idempotencyKey) {
                IdempotencyManager::unlock($idempotencyKey);
            }

            throw $e;
        }
    }

    public function debugTable()
    {

        // //=====================
        // // 1. Buat koneksi kustom/berbeda
        // $customPdo = \App\Core\Database\Connection::custom(
        //     'mysql',
        //     'backend_php', // Test Ganti Database
        //     '127.0.0.1',
        //     env('DB_PORT'),
        //     env('DB_USERNAME'),
        //     env('DB_PASSWORD'),
        //     config('database.mysql.options')
        // );

        // // Cara paksa Inject Langsung ke BaseModel sebelum load Model
        // // \App\Core\Database\BaseModel::setConnection($customPdo);

        // // 2. Inject PDO kustom melalui method setConnection
        // $productModel = new \App\Models\Product();
        // $productModel::setConnection($customPdo);

        // // 3. Jalankan QueryBuilder (Aman & Menggunakan $customPdo)
        // $activeProducts = $productModel
        //     ->where('status_kritis', '=', '1')
        //     ->orderBy('created_at', 'DESC')
        //     ->paginate(10, 1);
        // //=====================

        $activeProducts = \App\Models\Product::where('status_kritis', '=', '1')
                            ->orderBy('created_at', 'DESC')
                            ->paginate(10, 1);

        // \endResponse(
        //     $activeProducts
        // );

        json_response($activeProducts);
    }

    public function save_product(Request $request, Response $response)
    {
        // dd($request->all());

        // Validate Input
        Session::unset('errors'); // Clean Errors MessageBag
        $validator = new Validator();
        $validator->validate($request->all(), [
            'nama' => 'required|string|min:3|max:100',
            'kategori'  => 'required|string',
            'stok'  => 'required|numeric',
            'harga'  => 'required|numeric',
            'status_kritis'  => 'optional|numeric',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            htmx_response($errors, 422);
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $request->status_kritis = isset($request->status_kritis) ? 1 : 0;
        $postData = $filter->filter($request->all(), [
            'nama' => 'trim|sanitize_string',
            'kategori'  => 'trim|sanitize_string',
            'stok'  => 'trim|sanitize_numbers',
            'harga'  => 'trim|sanitize_numbers',
            'status_kritis'  => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData, ['nama', 'kategori', 'stok', 'harga', 'status_kritis']);
        // dd($payload);
        // dd(array_values($payload));

        $lastId = Product::create($payload);

        // dd($lastId);
        if (false === $lastId || !is_numeric($lastId)) {
            htmx_response("Gagal menyimpan data.", 500);
        }

        $payload['id'] = $lastId;
        $this->include('htmx.data.inventory.row', $payload);
    }
    // ===== END CRUD DATA

    // ===== GET DATA CHART
    public function data_dashboard_activities(Request $request, Response $response)
    {
        // Set header agar browser tahu ini konten dinamis
        // header('Content-Type: text/html; charset=utf-8');

        $search = isset($request->search) ? strtolower((string) $request->search) : '';
        $category = $request->category ?? '';

        $page     = isset($request->page) ? (int) $request->page : 1;
        $limit    = 5; // Jumlah data per halaman
        $offset   = ($page - 1) * $limit;

        // Panggil method model
        $this->assetModel = new Asset();
        $result = $this->assetModel->getPaginatedActivities($category, $search, $page, $limit);
        // Akses data
        $paged_data  = $result['data'];
        $total_items = $result['total_items'];
        $total_pages = $result['total_pages'];
        $page = $result['page'];

        // --- ROUTER LOGIC ---
        // Ambil path setelah /data-chart/
        // Contoh: /data-chart/activities -> $endpoint = 'activities'
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url((string) $request_uri, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $endpoint = end($parts);

        $dataViews = [
            // 'filtered' => $filtered,
            'search' => $search,
            'category' => $category,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'page' => $page,
            'offset' => $offset,
            'paged_data' => (array) $paged_data ?? [],
        ];

        // Hanya mengambil data tabelnya saja
        if (!isset($request->search) && !isset($request->category)) {
            return $dataViews;
        }

        // A. Endpoint untuk Tabel Log Aktivitas
        if ($endpoint === 'activities') {
            // Opsional: Berikan delay 300ms agar efek loading terlihat halus
            usleep(300000);

            $this->include('htmx.data.dashboard.row_activities', $dataViews);
        }

        // B. Endpoint untuk Data Chart (JSON)
        if ($endpoint === 'stats') {

            $lastIncome = random_int(30000000, 90000000);
            $active = rand(0, 100);
            $maintenance = 100 - $active;
            $utility = [$active, $maintenance];
            $activeUnits = rand(1, 20); // Dummy jumlah unit dinamis
            $stock_warning = random_int(1, 6);

            // Tanpa Filter
            $data = [
                'last_income' => number_format($lastIncome, 0, '', '.') ,
                'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                'stock_critical' => [3, 5, 2, 8, 4, 2, $stock_warning],
                'stock_warning' => $stock_warning . " Produk Warning",
                'utility' => $utility,
                "utility_count" => $activeUnits . " Unit", // Data dinamis teks tengah
                "utility_label" => "Aktif"
            ];

            // Logika: Jika kategori 'inventory', kembalikan angka khusus inventory
            if ($category === 'inventory') {
                $data = [
                            'last_income' => number_format($lastIncome, 0, '', '.') ,
                            'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                            'stock_critical' => [3, 5, 2, 8, 4, 2, $stock_warning],
                            'stock_warning' => $stock_warning . " Produk Warning",
                            'utility' => $utility,
                            "utility_count" => $activeUnits . " Unit", // Data dinamis teks tengah
                            "utility_label" => "Aktif"
                        ];
            }

            // Logika: Search
            if ($search !== '') {
                $data = [
                            'last_income' => number_format($lastIncome, 0, '', '.') ,
                            'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                            'stock_critical' => [3, 5, 2, 8, 4, 2, $stock_warning],
                            'stock_warning' => $stock_warning . " Produk Warning",
                            'utility' => $utility,
                            "utility_count" => $activeUnits . " Unit", // Data dinamis teks tengah
                            "utility_label" => "Aktif"
                        ];
            }

            return json_response($data);
        }
    }
    // ===== END GET DATA CHART


    // ===== GET DATA EXPORT
    public function data_dashboard_export(Request $request, Response $response)
    {
        dd($request->all());
        $search = $request->search ?? '';
        $category = $request->category ?? '';

        // 1. Ambil data dari database berdasarkan filter yang sama dengan tabel
        // $data = $db->query("SELECT ... WHERE category = '$category' AND title LIKE '%$search%'");

        // 2. Set Header untuk Download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Laporan_Koperasi_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');

        // Header Kolom CSV
        fputcsv($output, ['ID', 'Aktivitas', 'Anggota', 'Kategori', 'Waktu', 'Status']);

        // Dummy Data Loop (Ganti dengan hasil query database Anda)
        $filtered_data = [
            ['1', 'Sewa Traktor', 'Sukirman', 'Alat Berat', '10:00', 'Selesai']
        ];

        foreach ($filtered_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        // exit;
        customExit();
    }
    // ===== END GET DATA EXPORT


    // ===== GET DATA ASSETS
    public function assets_render($request)
    {
        $search = $request->search ?? '';
        $status = $request->status_filter ?? '';
        $viewMode   = $request->view_mode ?? 'grid';

        $page     = isset($request->page) ? (int)$request->page : 1;
        $limit    = 5; // Jumlah data per halaman
        $offset   = ($page - 1) * $limit;

        $this->assetModel = new Asset();
        $filtered = $this->assetModel->getFilteredAssets([
            'search'        => $search,
            'status_filter' => $status,
            'page'          => $page,
            'limit'         => $limit,
        ]);
        // \json_response($filtered);

        return [
            'search' => $search,
            'filtered' => $filtered,
            'viewMode' => $request->view_mode ?? 'grid',
        ];
    }

    public function assets_render_view(Request $request, Response $response)
    {
        $dataViews = $this->assets_render($request);
        // json_response($dataViews);

        $this->include('htmx.data.assets.assets-render', $dataViews);
    }

    public function assets_logs(Request $request, Response $response)
    {
        // $unitId = $_GET['id'] ?? 'Unknown';

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers'
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);

        // Use null coalescing (??) to be safe from undefined index
        $unitId = $payload['id'] ?? null;

        $logs   = [];
        if ($unitId) {
            $this->assetModel = new Asset();
            $logs       = $this->assetModel->getMaintenanceLogs((int) $unitId);
        }

        $unitId = $unitName = '';
        if (isset($logs[0])) {
            $unitCode = $logs[0]['unit_id'] ?? '';
            $unitId = $logs[0]['unit_id'] ?? '';
            $unitName = $logs[0]['unit_name'] ?? '';
        }

        $this->include('htmx.modals.assets.logs', ['unitId' => $unitId, 'unitName' => $unitName, 'logs' => $logs]);
    }

    public function assets_edit(Request $request, Response $response)
    {
        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers'
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);

        $unitId = $payload['id'] ?? null;

        $this->assetModel = new Asset();
        $asset = $this->assetModel->findByAssetId($unitId);
        // json_response($asset);

        if ($unitId && $asset) {
            $this->include('htmx.modals.assets.edit', ['id' => $unitId, 'asset' => $asset]);
        }

        return;
    }

    public function assets_update(Request $request, Response $response)
    {
        // dd($request->all());

        // Validate Input
        Session::unset('errors'); // Clean Errors MessageBag
        $validator = new Validator();
        $validator->validate($request->all(), [
            'asset_id' => 'required|string|min:3|max:100|unique:assets,asset_id,'.$request->id,
            'name' => 'required|string|min:3|max:100',
            'status'  => 'required|string',
            'health'  => 'required|numeric',
            'category_id'  => 'required|numeric',
            'icon'  => 'required|string',
            'color'  => 'required|string',
            'id'  => 'required|numeric',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            htmx_response($errors, 422);
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'asset_id' => 'trim|sanitize_string',
            'name'  => 'trim|sanitize_string',
            'status'  => 'trim|sanitize_string',
            'health'  => 'trim|sanitize_numbers',
            'category_id'  => 'trim|sanitize_numbers',
            'icon'  => 'trim|sanitize_string',
            'color'  => 'trim|sanitize_string',
            'action'  => 'trim|sanitize_string',
            'id'  => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);

        $params = [
            'asset_id' => $payload['asset_id'],
            'name' => $payload['name'],
            'status' => $payload['status'],
            'health' => $payload['health'],
            'category_id' => $payload['category_id'],
            'icon' => $payload['icon'],
            'color' => $payload['color'],
            'id' => $payload['id'],
        ];
        // dd($params);

        $id = (int) $params['id'];
        $dataToUpdate = array_diff_key($params, ['id' => true]);
        // \write_log('debug', $dataToUpdate);

        $lastId = Asset::updateById($id, $dataToUpdate);

        // dd($lastId);
        if (false === $lastId || !is_numeric($lastId)) {
            htmx_response("Gagal menyimpan data.", 500);
        }

        return;
    }

    public function assets_add(Request $request, Response $response)
    {
        $this->include('htmx.modals.assets.add');
    }

    public function assets_store(Request $request, Response $response)
    {
        // dd($request->all());

        // Validate Input
        Session::unset('errors'); // Clean Errors MessageBag
        $validator = new Validator();
        $validator->validate($request->all(), [
            'asset_id' => 'required|string|min:3|max:100|unique:assets,asset_id',
            'name' => 'required|string|min:3|max:100',
            'category_id'  => 'required|numeric',
            'status'  => 'required|string',
            'health'  => 'required|numeric',
            'icon'  => 'required|string',
            'color'  => 'required|string',
            'view_mode'  => 'required|string',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            htmx_response($errors, 422);
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $request->status_kritis = isset($request->status_kritis) ? 1 : 0;
        $postData = $filter->filter($request->all(), [
            'asset_id' => 'trim|sanitize_string',
            'name'  => 'trim|sanitize_string',
            'category_id'  => 'trim|sanitize_numbers',
            'status'  => 'trim|sanitize_string',
            'icon'  => 'trim|sanitize_string',
            'color'  => 'trim|sanitize_string',
            'health'  => 'trim|sanitize_numbers',
            'action'  => 'trim|sanitize_string',
            'status_kritis'  => 'trim|sanitize_string',
            'view_mode'  => 'trim|sanitize_string',
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);
        $viewMode = $payload['view_mode'];
        unset($payload['action']);
        unset($payload['status_kritis']);
        unset($payload['view_mode']);
        // dd($payload);
        // dd(array_values($payload));

        $lastId = Asset::create($payload);

        // dd($lastId);
        if (false === $lastId || !is_numeric($lastId)) {
            htmx_response("Gagal menyimpan data.", 500);
        }

        // Push id ke payload
        $payload['id'] = $lastId;

        // Tampilkan row
        $dataViews['filtered'] = [0 => $payload];
        $dataViews['viewMode'] = $viewMode;
        // dd($dataViews['filtered']);
        $this->include('htmx.data.assets.assets-row', $dataViews);

    }
    // ===== END GET DATA ASSETS
}
