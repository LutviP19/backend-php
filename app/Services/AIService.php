<?php

// Custom AI-Core Framework: Performa PHP Native dengan Intelegensi Buatan Terintegrasi

namespace App\Services;

// // Neuron AI
// use App\Neuron\BpAgent;
// use NeuronAI\Chat\Messages\UserMessage;
// // use App\Neuron\Output\Person;
// // use NeuronAI\Observability\AgentMonitoring; // Integrate with Inspector

// Ollama
use App\Neuron\OllamaExec;

class AIService {
    private $client;

    public function __construct() {
        // Ganti dengan API Key Anda atau ambil dari file .env
        // $this->client = BpAgent::client('YOUR_API_KEY');
    }

    public function generateInsight($dataPenjualan, $namaKlien) {

        try {
            $prompt = "Saya adalah asisten bisnis cerdas. Berikut adalah data penjualan dari klien saya, $namaKlien:\n";
            $prompt .= json_encode($dataPenjualan);
            $prompt .= "\n\nBerikan 3 poin analisis singkat: 1. Tren saat ini, 2. Masalah yang mungkin muncul, 3. Rekomendasi tindakan bisnis selanjutnya.";

            // // Sample-Code
            // $response = $this->client->chat()->create([
            //     'model' => 'gpt-4o', // Atau model terbaru di 2026
            //     'messages' => [
            //         ['role' => 'system', 'content' => 'Anda adalah analis bisnis senior yang berbicara dengan bahasa yang profesional namun mudah dimengerti.'],
            //         ['role' => 'user', 'content' => $prompt],
            //     ],
            //     'temperature' => 0.7,
            // ]);

            // return $response->choices[0]->message->content;

            // // Menggunakan BpAgent(Neuon AI)
            // $responseAi = BpAgent::make()->chat(
            //     new UserMessage($prompt)
            // );

            // $response = $responseAi->getContent();
            // // I'm a friendly AI Agent built with Neuron, how can I help you today?

            // Using OllamaExec
            $selectedModel = 'default-chat';
            $model = new OllamaExec($selectedModel);
            if (!$model->checkModelExists()) {
                echo "Error: Model belum terpasang di sistem.";
                exit;
            }

            // Calling the Wrapper class we created earlier
            $response = $model->ask($prompt, $selectedModel);

            // If the response is an array (error from cURL)
            if (is_array($response)) {
                echo "There is an error: " . $response['message'];
            } else {
                // Returns the AI's answer text
                echo $response;
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString();
        } finally {

            exit;
        }
    }
}