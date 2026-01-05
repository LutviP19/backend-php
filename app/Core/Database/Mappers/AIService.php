<?php

// Custom AI-Core Framework: Performa PHP Native dengan Intelegensi Buatan Terintegrasi

namespace App\Services;

use OpenAI;

class AIService {
    private $client;

    public function __construct() {
        // Ganti dengan API Key Anda atau ambil dari file .env
        $this->client = OpenAI::client('YOUR_API_KEY');
    }

    public function generateInsight($dataPenjualan, $namaKlien) {
        $prompt = "Saya adalah asisten bisnis cerdas. Berikut adalah data penjualan dari klien saya, $namaKlien:\n";
        $prompt .= json_encode($dataPenjualan);
        $prompt .= "\n\nBerikan 3 poin analisis singkat: 1. Tren saat ini, 2. Masalah yang mungkin muncul, 3. Rekomendasi tindakan bisnis selanjutnya.";

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o', // Atau model terbaru di 2026
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah analis bisnis senior yang berbicara dengan bahasa yang profesional namun mudah dimengerti.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        return $response->choices[0]->message->content;
    }
}