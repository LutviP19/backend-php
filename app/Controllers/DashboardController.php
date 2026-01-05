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

        // Handler reload manual
        $ignore_uri = ['login', 'htmx'];
        if (request()->method() === 'GET' && ! in_array(request()->uri(), $ignore_uri) && !$this->__isHtmxRequest()) {
            response()->redirect('/htmx');
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

        $this->view('index-htmx', ['server' => $server]);
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
        $this->view('htmx.dashboard');
    }

    public function inventory(Request $request, Response $response)
    {
        $data = $this->getProductsAll('', 'all', 1);
        // dd($data, true);
        $this->view('htmx.inventory', $data);
    }

    public function assets(Request $request, Response $response)
    {
        $this->view('htmx.assets');
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
    public function inventory_list(Request $request, Response $response) 
    {
        $category = $request->category ?? 'all';

        // 1. Ambil Parameter
        // $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search   = isset($_GET['search']) ? $_GET['search'] : '';
        $kategori = isset($_GET['category']) ? $_GET['category'] : 'all';
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $data = $this->getProductsAll($search, $kategori, $currentPage);

        $this->include('htmx.data.inventory.list', $data, true);
    }

    protected function getProductsAll($search, $kategori, $currentPage)
    {
        // $totalPages = 10;
        $limit    = 2;
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
        $kritisCount = count(array_filter($products, function($p) { return $p->stok <= 5; }));
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
            'harga'  => 'required|float',
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