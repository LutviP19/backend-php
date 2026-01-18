<?php

namespace App\Controllers;

use App\Core\Http\{Request, Response};
use App\Core\Support\Session;
use App\Core\Database\QueryBuilder;
use App\Core\Validation\Validator;


class DashboardController extends Controller
{

    public function __construct()
    {
        parent::__construct();

        // Allow insomnia, etc...
        $user_agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
        $dev_agents = [
                        'insomnia',
                    ];
        $agentAllow = false;
        foreach ($dev_agents as $agent) { 
            if (str_contains(strtolower($user_agent), strtolower($agent))) {
                $agentAllow = true;
            }
        }
        // dd($agentAllow);

        // Handler reload manual
        if(!$agentAllow) {
            $ignore_uri = ['login', 'htmx'];
            if (request()->method() === 'GET' && ! in_array(request()->uri(), $ignore_uri) && !$this->__isHtmxRequest()) {
                response()->redirect('/htmx');
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
        $server = \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port')) ? "OpenSwoole" : "PHP FPM";

        $dataViews = $this->data_dashboard_activities($request, $response);
        // dd($dataViews);
        $this->view('index-htmx', array_merge(['server' => $server], $dataViews));
    }

    public function login(Request $request, Response $response)
    {
        $this->view('login');
    }

    public function loginAuth(Request $request, Response $response)
    {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';

        // Simulasi Cek Login
        if ($user === 'admin' && $pass === 'desa2026') {
            
            // 1. Kirim sinyal ke Alpine.js untuk munculkan Toast
            // Format: HX-Trigger: {"namaEvent": "isiData"}
            header('HX-Trigger: {"show-toast": "Login Berhasil! Mengalihkan..."}');

            // 2. Tunggu 1.5 detik (simulasi proses) lalu redirect
            // Catatan: Redirect HTMX dilakukan via header
            header('HX-Redirect: /htmx');
            
            exit();

        } else {
            // Jika gagal, kirim pesan error ke #error-area
            // Dan bisa juga trigger event khusus untuk suara 'tetot'
            header('HX-Trigger: {"play-error-sound": true}');
            echo "<i class='fas fa-exclamation-triangle mr-1'></i> Username atau Password salah!";
        }
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
        $dataViews = $this->assets_render($request,$response);
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
    protected function inventory_stats($category) 
    {
        $category = $_GET['category'] ?? 'all';

        // Logika Query: Ambil data produk sorting berdasarkan terbaru/terakhir diupdate
        $query = "SELECT nama, stok FROM products ";

        if ($category !== 'all') {
            $query .= " WHERE kategori = ? ";
        } else {
            $category = '1';
            $query .= " WHERE 1 = ? ";
        }
        $query .= " ORDER BY created_at DESC";
        $result = QueryBuilder::table('products')
                    ->execQuery($query, [$category], false, false, true);

        $labels = [];
        $values = [];
        $totalStok = 0;

        foreach ($result as $row) {
            // Potong nama jika terlalu panjang untuk label chart
            // $labels[] = strlen($row->nama) > 12 ? substr((string) $row->nama, 0, 10) . '..' : $row->nama;
            $labels[] = strlen((string) $row->nama) > 12 ? substr((string) $row->nama, 0, 10) . '..' : $row->nama;
            $values[] = (int)$row->stok;
            $totalStok += $row->stok;
        }

        // Ambil 5 produk terbaru untuk setiap kategori agar ada titik koordinat di chart
        $totalStokCat = 0;
        $resultCat = $labelsCat = $valuesCat = $datasets = [];
        $queryCat = "SELECT kategori, sum(stok) as ttl_stok FROM products GROUP BY kategori";
        $resultCat = QueryBuilder::table('products')
                    ->execQuery($queryCat, [], false, false, true);
        

        // Create an empty generic object (Dummy data)
        $alat = [
            'kategori' => 'alat',
            'ttl_stok' => 5
        ];
        array_push($resultCat, (object) $alat);
        // dd($resultCat);

        $colorMap = ['pupuk' => '#6366f1', 'benih' => '#10b981', 'pestisida' => '#f59e0b', 'alat' => '#f43f5e'];
        foreach ($resultCat as $row) {
            // Potong nama jika terlalu panjang untuk label chart
            $labelsCat[] = ucwords((string) $row->kategori);
            $valuesCat[] = (int)$row->ttl_stok;
            $totalStokCat += $row->ttl_stok;
        }

        

        /*

'datasets' => [
        [
            'label' => 'Pupuk',
            'data' => $resultData['pupuk'],
            'borderColor' => '#6366f1', // Indigo
            'backgroundColor' => '#6366f1',
        ],
        [
            'label' => 'Benih',
            'data' => $resultData['benih'],
            'borderColor' => '#10b981', // Emerald
            'backgroundColor' => '#10b981',
        ],
        [
            'label' => 'Pestisida',
            'data' => $resultData['pestisida'],
            'borderColor' => '#f59e0b', // Amber
            'backgroundColor' => '#f59e0b',
        ],
        [
            'label' => 'Alat',
            'data' => $resultData['alat'],
            'borderColor' => '#f43f5e', // Rose
            'backgroundColor' => '#f43f5e',
        ]
    ]
        */


        // // Balik urutan agar yang paling baru ada di kanan chart
        // $labels = array_reverse($labels);
        // $values = array_reverse($values);

        // Logika AI Insight sederhana
        $avgStok = count($values) > 0 ? $totalStok / count($values) : 0;
        $isKritis = $avgStok < 20; // Contoh: rata-rata stok di bawah 20 dianggap kritis
        $msg = $isKritis 
            ? "AI Alert: Stok rata-rata kategori $category sangat rendah ($avgStok). Segera cek gudang!" 
            : "AI Insight: Perputaran stok $category terpantau sehat.";

        $data = [
                    'labels' => $labels,
                    'values' => $values,
                    'totalStok' => $totalStok,
                    'labelsCat' => $labelsCat,
                    'valuesCat' => $valuesCat,
                    'totalStokCat' => $totalStokCat,
                    'isKritis' => $isKritis,
                    'msg' => $msg
                ];

        // dd($data, true);
        return $data;
    }

    public function inventory_list(Request $request, Response $response) 
    {
        $category = $request->category ?? 'all';

        // 1. Ambil Parameter
        // $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search   = $_GET['search'] ?? '';
        $kategori = $_GET['category'] ?? 'all';
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $stats = $this->inventory_stats($kategori);
        $data = $this->getProductsAll($search, $kategori, $currentPage);

        $data = array_merge($data, ['stats' => $stats]);

        $this->include('htmx.data.inventory.list', $data, true);
    }

    protected function getProductsAll($search, $kategori, $currentPage)
    {
        $limit    = 5; // rows perPage
        $offset   = ($currentPage - 1) * $limit;

        // 2. Bangun Query dengan Filter
        $queryStr = "WHERE 1=1";
        $params   = [];

        if ($search) {
            $queryStr .= " AND nama LIKE ?";
            $params['search'] = "%$search%";
        }
        if ($kategori && $kategori !== 'all') {
            $queryStr .= " AND kategori = ?";
            $params['kategori'] = $kategori;
        }

        // 3. Hitung Total Data (Untuk Pagination)
        $totalStmt = QueryBuilder::table('products')
                    ->execQuery("SELECT COUNT(0) as total_data FROM products $queryStr", array_values($params), false, true);
        $totalRows = $totalStmt->total_data;
        $totalPages = ceil($totalRows / $limit);
        $paginationItems = $this->__getPaginationRange($currentPage, $totalPages);

        // 4. Ambil Data dengan Limit
        $sql = "SELECT * FROM products $queryStr ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $products = QueryBuilder::table('products')->execQuery($sql, array_values($params), false, false, true);

        return [
                'products' => $this->getProducts($products), 
                'category' => $kategori,
                'search' => $search,
                'currentPage' => $currentPage, 
                'totalPages' => $totalPages,
                'paginationItems' => $paginationItems,
            ];
    }

    protected function getProducts($products)
    {
        // Di dalam inventory_list setelah query data
        // $kritisCount = count(array_filter($products, function($p) { return $p->stok <= 5; }));
        $kritisCount = count(array_filter($products, fn($p) => $p->stok <= 5));
        $isKritis = ($kritisCount > 0);
        if ($isKritis) {
            $msg = "AI mendeteksi $kritisCount produk dengan stok KRITIS! Segera lakukan pengadaan ulang.";
        } else {
            $msg = "Stok pada kategori ini terpantau AMAN. Belum diperlukan tindakan restock.";
        }

        // Kirimkan event ke Alpine.js dengan detail pesan dan status kritis
        header('HX-Trigger: {"update-ai-insight": {"msg": "' . $msg . '", "isKritis": ' . ($isKritis ? 'true' : 'false') . '}}');

        return $products;
    }

    public function edit_product(Request $request, Response $response) 
    {
        $id = e($_GET['id']);

        if (!is_numeric($id)) {
            header('Content-Type: text/html', true, 422);
            die("ID tidak valid.");
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData, ['id']);

        $product = QueryBuilder::table('products')->execQuery('SELECT * FROM products WHERE id = ? LIMIT 1', array_values($payload), false, true);

        $this->view('htmx.modals.inventory.form_edit',['data' => $product]);
    }
    // ===== END GET DATA

    // ===== CRUD DATA

    public function delete_product(Request $request, Response $response) 
    {
        // dd($request->all());

        // Validate Input
        Session::unset('errors'); // Clean Errors MessageBag
        $validator = new Validator();
        $validator->validate($request->all(), [
            'id'  => 'required|numeric',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            header('Content-Type: text/html', true, 422);
            die("ID tidak valid.");
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers',
        ]);
        $payload = $filter->sanitize($postData, ['id']);
        // dd($payload);
        // dd(array_values($payload));

        $auth = false;
        // dd($auth);
        if(false === $auth) {
            header("HTTP/1.1 403 Forbidden");
            die("Produk ini tidak bisa dihapus, mohon hubungi Admin.");
        }

        $callback = QueryBuilder::table('products')
                    ->execQuery('DELETE FROM products WHERE id = ?', array_values($payload));

        if(false === $callback) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Gagal menghapus data.");
        }

        // Berhasil: Kirim status 200 dengan body kosong
        // HTMX akan menghapus elemen <tr> target karena kita menggunakan hx-swap="outerHTML"
        http_response_code(200);
        exit;
    }

    public function update_product(Request $request, Response $response) 
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
            'id'  => 'required|numeric',
        ]);
        $errors = Session::get('errors');

        if ($errors) {
            header('Content-Type: application/json', true, 422);
            dd($errors, true);
            die("Gagal menyimpan data.");
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
        
        $lastId = QueryBuilder::table('products')
                    ->execQuery('UPDATE products SET  nama = ?,  kategori = ?,  stok = ?, harga = ?,  status_kritis = ?, updated_at = NOW() WHERE id = ?', array_values($payload));

        // dd($lastId);
        if(false === $lastId) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Gagal menyimpan data.");
        }

        $this->include('htmx.data.inventory.row', $payload);
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
            header('Content-Type: application/json', true, 422);
            dd($errors, true);
            die("Gagal menyimpan data.");
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
        
        $lastId = QueryBuilder::table('products')
                    ->execQuery('INSERT INTO products (nama, kategori, stok, harga, status_kritis) 
                                VALUES (?, ?, ?, ?, ?)', array_values($payload), true);

        // dd($lastId);
        if(false === $lastId || !is_numeric($lastId)) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Gagal menyimpan data.");
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

        $search = isset($_GET['search']) ? strtolower((string) $_GET['search']) : '';
        $category = $_GET['category'] ?? '';

        $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit    = 5; // Jumlah data per halaman
        $offset   = ($page - 1) * $limit;

        // Filter Data
        $queryStr = " FROM activities a JOIN categories c ON a.category_id = c.id WHERE 1=1";
        $params = [];
        if ($category && $category !== 'all') {
            $queryStr .= " AND c.slug = ?";
            $params['kategori'] = $category;
        }
        if ($search && $search !== '') {
            $queryStr .= " AND (a.title LIKE ? OR a.member LIKE ?)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }        

        $queryStr .= " ORDER BY a.time ASC";

        // 3. Hitung Total Data (Untuk Pagination)
        $totalStmt = QueryBuilder::table('activities')
                    ->execQuery("SELECT COUNT(0) as total_data $queryStr", array_values($params), false, true);
        $total_items = $totalStmt->total_data;
        $total_pages = ceil($total_items / $limit);
        $page        = max(1, min($page, $total_pages ?: 1)); // Proteksi range halaman

        // 4. Ambil Data dengan Limit
        $sql = "SELECT  a.id, a.title, a.member, c.display_name AS category_name, a.time, a.status, COALESCE(a.icon, c.default_icon) AS icon, COALESCE(a.color, c.default_color) AS color $queryStr LIMIT $limit OFFSET $offset";
        $object_data = QueryBuilder::table('activities')->execQuery($sql, array_values($params), false, false, true);

        // Convert Object menjadi Array
        $json_string = json_encode($object_data);
        $paged_data = json_decode($json_string, true);

        // --- ROUTER LOGIC ---
        // Ambil path setelah /data-chart/
        // Contoh: /data-chart/activities -> $endpoint = 'activities'
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url((string) $request_uri, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $endpoint = end($parts);


        $dataViews = [
            // 'filtered' => $filtered, 
            'total_items' => $total_items, 
            'total_pages' => $total_pages, 
            'page' => $page, 
            'offset' => $offset, 
            'paged_data' => (array) $paged_data, 
        ];

        // Hanya mengambil data tabelnya saja
        if(!isset($_GET['search']) && !isset($_GET['category'])) {
            return $dataViews;
        }

        // A. Endpoint untuk Tabel Log Aktivitas
        if ($endpoint === 'activities') {
            // Opsional: Berikan delay 300ms agar efek loading terlihat halus
            usleep(300000);

            $this->include('htmx.data.dashboard.row_activities',  $dataViews);
        }

        // B. Endpoint untuk Data Chart (JSON)
        if ($endpoint === 'stats') {
            header('Content-Type: application/json');

            // $lastIncome = rand(30000000, 90000000);
            $lastIncome = random_int(30000000, 90000000);

            // Tanpa Filter
            $data = [
                'last_income' => number_format($lastIncome, 0, '', '.') ,
                'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                // 'stock_critical' => [3, 5, 2, 8, 4, 2, rand(1, 6)],
                'stock_critical' => [3, 5, 2, 8, 4, 2, random_int(1, 6)],
                'utility' => [88, 12]
            ];

            // Logika: Jika kategori 'inventory', kembalikan angka khusus inventory
            if ($category === 'inventory') {
                $data = [
                            'last_income' => number_format($lastIncome, 0, '', '.') ,
                            'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                            // 'stock_critical' => [3, 5, 2, 8, 4, 2, rand(1, 6)],
                            'stock_critical' => [3, 5, 2, 8, 4, 2, random_int(1, 6)],
                            'utility' => [88, 12]
                        ];
            }

            // Logika: Search
            if ($search !== '') {
                $data = [
                            'last_income' => number_format($lastIncome, 0, '', '.') ,
                            'income' => [15000000, 22000000, 18000000, 28000000, 24000000, 32000000, $lastIncome], // Data dalam juta
                            // 'stock_critical' => [3, 5, 2, 8, 4, 2, rand(1, 6)],
                            'stock_critical' => [3, 5, 2, 8, 4, 2, random_int(1, 6)],
                            'utility' => [88, 12]
                        ];
            }


            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
    }
    // ===== END GET DATA CHART


    // ===== GET DATA EXPORT
    public function data_dashboard_export(Request $request, Response $response) 
    {
        dd($request->all());
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
    
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
        exit;
    }
    // ===== END GET DATA EXPORT


    // ===== GET DATA ASSETS
    public function assets_render(Request $request, Response $response) 
    {
        // dd($request->all());
        // $search = $_GET['search'] ?? '';
        // $status = $_GET['status_filter'] ?? '';
        // $viewMode   = $_GET['view_mode'] ?? 'grid';

        $search = $request->search ?? '';
        $status = $request->status_filter ?? '';
        $viewMode   = $request->view_mode ?? 'grid';

        $page     = isset($request->page) ? (int)$request->page : 1;
        $limit    = 5; // Jumlah data per halaman
        $offset   = ($page - 1) * $limit;


        // $categoryVisuals = [
        //     'heavy-equipment' => [
        //         'icon'  => 'fa-tractor',
        //         'color' => 'emerald',
        //         'label' => 'Alat Berat'
        //     ],
        //     'technology' => [
        //         'icon'  => 'fa-plane-up',
        //         'color' => 'indigo',
        //         'label' => 'Teknologi'
        //     ],
        //     'support' => [
        //         'icon'  => 'fa-faucet-drip',
        //         'color' => 'blue',
        //         'label' => 'Pendukung'
        //     ],
        //     'warehouse' => [
        //         'icon'  => 'fa-dolly',
        //         'color' => 'slate',
        //         'label' => 'Gudang'
        //     ],
        //     'logistics' => [
        //         'icon'  => 'fa-truck',
        //         'color' => 'amber',
        //         'label' => 'Logistik'
        //     ]
        // ];

        // $statusMapping = [
        //     'ready' => [
        //         'label' => 'Tersedia',
        //         'bg'    => 'bg-emerald-50',
        //         'text'  => 'text-emerald-600',
        //         'border'=> 'border-emerald-100',
        //         'dot'   => 'bg-emerald-500'
        //     ],
        //     'working' => [
        //         'label' => 'Beroperasi',
        //         'bg'    => 'bg-blue-50',
        //         'text'  => 'text-blue-600',
        //         'border'=> 'border-blue-100',
        //         'dot'   => 'bg-blue-500'
        //     ],
        //     'maintenance' => [
        //         'label' => 'Perbaikan',
        //         'bg'    => 'bg-rose-50',
        //         'text'  => 'text-rose-600',
        //         'border'=> 'border-rose-100',
        //         'dot'   => 'bg-rose-500'
        //     ]
        // ];

        // // Ambil visual berdasarkan kategori (Slug)
        // $visual = $categoryVisuals[$asset['category_slug']] ?? $categoryVisuals['heavy-equipment'];

        // // Ambil visual berdasarkan status
        // $status = $statusMapping[$asset['status']] ?? $statusMapping['ready'];


        // // Di dalam loop while($asset = mysqli_fetch_assoc($result))
        // $cat    = AssetHelper::$categories[$asset['category_slug']] ?? AssetHelper::$categories['heavy-equipment'];
        // $status = AssetHelper::$statuses[$asset['status']] ?? AssetHelper::$statuses['ready'];
        // $health = AssetHelper::getHealthInfo($asset['health']);

        // // Tambahkan efek kedip jika kesehatan kritis
        // $blinkClass = $health['is_critical'] ? 'animate-pulse' : '';


        // $sql = "SELECT a.asset_id, a.name, a.status, a.health, a.icon, a.color, a.updated_at, c.category_name 
        //         FROM assets a 
        //         LEFT JOIN asset_categories c ON a.category_id = c.id
        //         ORDER BY c.category_name ASC, a.asset_id ASC, a.updated_at DESC;";

        // Filter Data
        $queryStr = " FROM assets a LEFT JOIN asset_categories c ON a.category_id = c.id WHERE 1=1";
        $params = [];
        if ($status && $status !== '') {
            $queryStr .= " AND a.status = ?";
            $params['status'] = $status;
        }
        if ($search && $search !== '') {
            $queryStr .= " AND (a.name LIKE ? OR a.asset_id LIKE ?)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }        

        $queryStr .= " ORDER BY a.updated_at DESC, c.category_name ASC, a.asset_id ASC";


        // // 3. Hitung Total Data (Untuk Pagination)
        // $totalStmt = QueryBuilder::table('activities')
        //             ->execQuery("SELECT COUNT(0) as total_data $queryStr", array_values($params), false, true);
        // $total_items = $totalStmt->total_data;
        // $total_pages = ceil($total_items / $limit);
        // $page        = max(1, min($page, $total_pages ?: 1)); // Proteksi range halaman


        // 4. Ambil Data dengan Limit
        // $sql = "SELECT a.asset_id, a.name, a.status, a.health, a.icon, a.color, a.updated_at, c.category_name $queryStr LIMIT $limit OFFSET $offset";
        $sql = "SELECT a.id, a.asset_id, a.name, a.status, a.health, a.icon, a.color, a.updated_at, c.category_name $queryStr";
        $object_data = QueryBuilder::table('activities')->execQuery($sql, array_values($params), false, false, true);

        // Convert Object menjadi Array
        $json_string = json_encode($object_data);
        $filtered = json_decode($json_string, true);


        // dd($viewMode);
        $dataViews = [
            'filtered' => $filtered, 
            'viewMode' => $viewMode, 
        ];

        // Hanya mengambil data tabelnya saja
        if($_SERVER['REQUEST_METHOD'] === 'POST' || (!isset($_GET['search']) && !isset($_GET['status_filter']) && !isset($_GET['view_mode']))) {
            return $dataViews;
        }

        $this->include('htmx.data.assets.assets-render',  $dataViews);
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

        $unitId = $payload['id'];

        $sql = "SELECT a.asset_id as unit_id, a.name as unit_name, l.maintenance_date as date, l.task, l.status 
            FROM asset_maintenance_logs l
            JOIN assets a ON l.asset_id = a.id
            WHERE a.id = ? 
            ORDER BY l.maintenance_date DESC";
        $object_data = QueryBuilder::table('asset_maintenance_logs')->execQuery($sql, [$unitId], false, false, true);

        // Convert Object menjadi Array
        $json_string = json_encode($object_data);
        $logs = json_decode($json_string, true);

        // // Dummy data log - di proyek nyata, ini diambil dari database
        // $logs = [
        //     ['date' => '2025-12-10', 'task' => 'Ganti Oli Mesin', 'status' => 'Selesai'],
        //     ['date' => '2025-11-25', 'task' => 'Pengecekan Hidrolik', 'status' => 'Selesai'],
        //     ['date' => '2025-11-01', 'task' => 'Ganti Filter Udara', 'status' => 'Selesai'],
        // ];

        

        // $logs = [];
        $unitId = $logs[0] ? $logs[0]['unit_id'] : '';
        $unitName = $logs[0] ? $logs[0]['unit_name'] : '';
        $this->include('htmx.modals.assets.logs', ['unitId' => $unitId, 'unitName' => $unitName, 'logs' => $logs]);
    }

    public function assets_edit(Request $request, Response $response) 
    {
        // $id = $_GET['id'] ?? '';

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $postData = $filter->filter($request->all(), [
            'id'  => 'trim|sanitize_numbers'
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);

        $unitId = $payload['id'];

        // // Dummy data asset - di proyek nyata ambil dari database
        // $asset = ['id' => $id, 'name' => 'Excavator Cat 320', 'health' => 85, 'status' => 'ready'];

        $sql = "SELECT a.id, a.asset_id, a.category_id, a.name, a.status, a.health, a.icon, a.color, ac.category_name  
                FROM assets a LEFT JOIN asset_categories ac ON a.category_id = ac.id WHERE a.id = ? LIMIT 1";
        $object_data = QueryBuilder::table('assets')->execQuery($sql, [$unitId], false, true, false);

        // Convert Object menjadi Array
        $json_string = json_encode($object_data);
        $asset = json_decode($json_string, true);

        $this->include('htmx.modals.assets.edit', ['id' => $unitId, 'asset' => $asset]);
    }

    public function assets_update(Request $request, Response $response) 
    {
        // $id = $_POST['id'] ?? '';
        // $action = $_POST['action'] ?? 'edit';

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
            header('Content-Type: application/json', true, 422);
            dd($errors, true);
            die("Gagal menyimpan data.");
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

        $sql = "UPDATE assets 
                SET  asset_id = ?,  name = ?,  status = ?, health = ?, category_id = ?, icon = ?, color = ?, updated_at = NOW() 
                WHERE id = ?";
        $lastId = QueryBuilder::table('assets')->execQuery($sql, array_values($params));

        // dd($lastId);
        if(false === $lastId) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Gagal menyimpan data.");
        }

        // http_response_code(200);
        $dataViews = $this->assets_render($request, $response);
        // dd($dataViews);
        $this->include('htmx.data.assets.assets-render',  $dataViews);
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
            header('Content-Type: application/json', true, 422);
            dd($errors, true);
            die("Gagal menyimpan data.");
        }

        $filter = new \App\Core\Validation\Filter();
        // Filter & Sanitize Input
        $request->status_kritis = isset($request->status_kritis) ? 1 : 0;
        $postData = $filter->filter($request->all(), [
            'asset_id' => 'trim|sanitize_string',
            'name'  => 'trim|sanitize_string',
            'category_id'  => 'trim|sanitize_numbers',
            'status'  => 'trim|sanitize_string',
            'health'  => 'trim|sanitize_numbers',
            'icon'  => 'trim|sanitize_string',
            'color'  => 'trim|sanitize_string',
            'view_mode'  => 'trim|sanitize_string',
            'action'  => 'trim|sanitize_string',
            'status_kritis'  => 'trim|sanitize_string',
        ]);
        $payload = $filter->sanitize($postData);
        // dd($payload);
        $viewMode = $payload['view_mode'];
        unset($payload['action']);
        unset($payload['status_kritis']);
        unset($payload['view_mode']);
        // dd($payload);
        // dd(array_values($payload));

        // 3. Insert Data Baru
        $lastId = QueryBuilder::table('assets')
                    ->execQuery('INSERT INTO assets (asset_id, name, category_id, color, icon, status, health) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($payload), true);

        // dd($lastId);
        if(false === $lastId || !is_numeric($lastId)) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Gagal menyimpan data.");
        }
        // Push id ke payload
        $payload['id'] = $lastId;


        // Tampilkan row
        $dataViews['filtered'] = [0 => $payload];
        $dataViews['viewMode'] = $viewMode;
        // dd($dataViews['filtered']);
        $this->include('htmx.data.assets.assets-row',  $dataViews);

    }
    // ===== END GET DATA ASSETS

    private function __getPaginationRange($currentPage, $totalPages) 
    {
        $delta = 1; // Jumlah halaman di kiri & kanan halaman aktif
        $range = [];
        $rangeWithDots = [];
        $l = null;
    
        for ($i = 1; $i <= $totalPages; $i++) {
            // Tampilkan: Halaman 1, Halaman Terakhir, dan Halaman di sekitar Current
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $delta && $i <= $currentPage + $delta)) {
                $range[] = $i;
            }
        }
    
        foreach ($range as $i) {
            if ($l) {
                if ($i - $l === 2) {
                    $rangeWithDots[] = $l + 1;
                } else if ($i - $l !== 1) {
                    $rangeWithDots[] = '...';
                }
            }
            $rangeWithDots[] = $i;
            $l = $i;
        }
    
        return $rangeWithDots;
    }

    private function __isHtmxRequest() {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }
}

class AssetHelper {
    // 1. Mapping Kategori (Ikon & Warna Dasar)
    public static $categories = [
        'heavy-equipment' => ['icon' => 'fa-tractor', 'color' => 'emerald', 'label' => 'Alat Berat'],
        'technology'      => ['icon' => 'fa-plane-up', 'color' => 'indigo', 'label' => 'Teknologi'],
        'support'         => ['icon' => 'fa-faucet-drip', 'color' => 'blue', 'label' => 'Pendukung'],
        'warehouse'       => ['icon' => 'fa-dolly', 'color' => 'slate', 'label' => 'Gudang'],
        'logistics'       => ['icon' => 'fa-truck', 'color' => 'amber', 'label' => 'Logistik']
    ];

    // 2. Mapping Status (Badge UI)
    public static $statuses = [
        'ready'       => ['label' => 'Tersedia', 'css' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
        'working'     => ['label' => 'Beroperasi', 'css' => 'bg-blue-50 text-blue-600 border-blue-100'],
        'maintenance' => ['label' => 'Perbaikan', 'css' => 'bg-rose-50 text-rose-600 border-rose-100']
    ];

    /**
     * Menghitung visual Health Bar dan efek khusus
     */
    public static function getHealthInfo($health) {
        $info = [
            'color'    => 'emerald',
            'is_critical' => false,
            'label'    => 'Sehat'
        ];

        if ($health <= 25) {
            $info['color'] = 'rose';
            $info['is_critical'] = true;
            $info['label'] = 'Kritis';
        } elseif ($health <= 65) {
            $info['color'] = 'amber';
            $info['label'] = 'Butuh Perhatian';
        }

        return $info;
    }
}