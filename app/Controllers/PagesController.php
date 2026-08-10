<?php

namespace App\Controllers;

// use App\Core\Database\Model;
use App\Models\User;
// use App\Models\Role;
// use App\Core\Events\Event;
use App\Core\Http\{Request, Response};

// use App\Core\Message\Broker;
// use App\Core\Support\Config;
// use App\Core\Support\Session;
// // Events
// use App\Core\Events\EventDispatcher;
// use App\Services\OrderService;

// use function Amp\async;

class PagesController extends Controller
{
    //controller constructor.
    public function __construct()
    {
        // $this->csrf();
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
        // // $users = User::select()->get();
        $users = User::getAllUser();
        // dd($users);

        // // Testing Regenerate SessioId
        // $oldSessionId = session_id();
        // $headers = bp_session_regenerate_id($oldSessionId);
        // setHeaders($headers);

        // // Session::set('jwtId', generateUlid());
        // dd(Session::get('jwtId'));
        $server = \isSwoole() ? "OpenSwoole" : "PHP FPM";

        $this->view('spa.index', ['users' => $users, 'server' => $server]);
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function demoSpa(Request $request, Response $response)
    {
        $this->view('spa.pages.main');
    }

    // Sample print PDF
    public function printView(Request $request, Response $response, $id = null)
    {
        $id = $id ?: (int) $request->id;
        $produk = $request->produk ?? 'Excavator Komatsu PC200-8M0 & Sparepart';
        $petani = $request->petani ?? 'Petani';
        // $bastData = $this->bastModel->find($id);

        // Dummy data untuk $bastData = $this->bastModel->find($id);
        $bastData = [
            'id'           => $id ?? 1,
            'no_bast'      => 'BAST/KOP/' . date('Y/m') . '/' . str_pad($id ?? 1, 3, '0', STR_PAD_LEFT),
            'tanggal'      => date('Y-m-d'),
            'title'        => $produk,
            'kategori'     => 'assets', // assets, finance, atau inventory
            'jumlah'       => '1 Unit',
            'kondisi'      => 'Baik / Layak Operasional',
            'penyerah'     => 'Lutvi (Admin Aset Koperasi)',
            'penerima'     => $petani . ' (Anggota No. A-1092)',
            'keterangan'   => 'Serah terima unit alat berat dalam kondisi lengkap beserta kunci kontak dan dokumen STNK/BPKB.',
            'status'       => 'Selesai',
            'member'       => 'Budi Santoso',
            'time'         => date('d M Y, H:i') . ' WIB'
        ];

        // IMPORTANT FOR OPENSWOOLE: 
        // Force Swoole to close the HTTP connection as soon as the data is sent
        // This prevents the socket from getting stuck in the Event Loop
        if ($response && method_exists($response, 'setHeader')) {
            $response->setHeader('Connection', 'close');
        } else {
            if(! headers_sent()) {
                header('Connection: close');
            }
        }

        // Render View Murni tanpa Layout Dashboard Utama
        $this->view('pdf.bast-print-template', [
            'bast' => $bastData
        ]);
    }

}
