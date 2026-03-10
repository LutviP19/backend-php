<?php 

namespace App\Neuron;

use PDO;

class OllamaExec {
    private $model;
    private $ollamaPath;
    private $host;
    private $db_file;

    public function __construct($model = 'gemma3', $host = 'localhost') {

        // Applies to all methods in this class
        if (PHP_SAPI !== 'cli') {
            // Only run if not called via terminal (CLI usually has 0)
            set_time_limit(0);
            ini_set('max_execution_time', 0);
        }

        $this->model = $model;
        $this->host = $host;
        $this->db_file = database_path('vector_store.db');

        // Use absolute path if 'ollama' is not in the web user's PATH
        // Example: '/usr/local/bin/ollama' or simply 'ollama'
        $this->ollamaPath = 'ollama'; 

        // Init DB - Create FTS tables
        $this->createFtsDb();
    }

    /**
     * Checking whether the model is available in the system
     */
    public function checkModelExists() {
        $command = "{$this->ollamaPath} list 2>&1";
        $output = shell_exec($command);
        
        if ($output === null) return false;

        // Check if the model name is in the output list.
        return str_contains($output, $this->model);
    }

    public function getEmbedding($text) {
        // Use Ollama API endpoint (more stable for embedding)
        $ch = curl_init('http://localhost:11434/api/embeddings');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => env('EMBEDDING_MODEL', 'embeddinggemma'),
            'prompt' => $this->cleanForIndex($text),
            'keep_alive' => '24h' // Keep the model in RAM for 24 hours
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        return $data['embedding']; // This is an array containing ~768 numbers
    }

    /**
     * Sending prompts directly via the Ollama CLI
     */
    public function ask($prompt, $selectedModel) {

        // --- LOGIKA SMART SWITCH ---
        $currentActive = $this->getActiveModel($this->host);

        // Jika ada model aktif DAN model itu bukan yang kita pilih sekarang
        if ($currentActive && $currentActive !== $selectedModel) {
            $this->unloadModel($this->host, $currentActive);
            // Beri jeda 1 detik agar CPU i5 sempat "bernapas" setelah unload
            sleep(1); 
        }

        // 1. Validate model existence before heavy execution
        if (!$this->checkModelExists()) {
            return [
                "error" => true, 
                "message" => "Model '{$this->model}' tidak ditemukan. Silakan jalankan 'ollama pull {$this->model}' di terminal."
            ];
        }

        // Default prompt
        $finalPrompt = $prompt;

        // Only use FTS5 feature for AI Chat Agent || Assistant
        if (str_contains($this->model, 'chat') || str_contains($this->model, 'asisten')) {

            // Connect to DB
            $db = new PDO('sqlite:'.$this->db_file);

            // --- STEP 1: Full-Text Search (Enhanced) ---
            $cleanQuery = preg_replace('/[^A-Za-z0-9 ]/', '', $prompt);
            $contextText = "";
            $results = [];

            if (!empty(trim($cleanQuery))) {
                try {
                    // 1. FTS5 Query Preparation
                    $words = explode(' ', trim($cleanQuery));
                    // Adding * to each word to support partial search (prefix matching)
                    // Use AND to make your search more specific, or leave a space for OR.
                    $ftsQuery = implode(' AND ', array_map(fn ($w) => $w . '*', $words));

                    // A. FTS5 STRATEGY
                    $stmt = $db->prepare("
                                            SELECT m.id, m.content, m.tags 
                                            FROM kearifan_lokal m
                                            JOIN kearifan_lokal_fts f ON m.id = f.rowid
                                            WHERE kearifan_lokal_fts MATCH ? 
                                            ORDER BY bm25(kearifan_lokal_fts) ASC -- ASC karena BM25 negatif (makin kecil makin relevan)
                                            LIMIT 3
                                        ");
                    $stmt->execute([$ftsQuery]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Save the ID you have obtained so that it is not duplicated in the fallback.
                    $excludeIds = array_column($results, 'id');

                    // B. STRATEGI FUZZY FALLBACK
                    if (count($results) < 3) {
                        $needed = 3 - count($results);
                        $placeholders = count($excludeIds) > 0 ? "AND id NOT IN (" . implode(',', array_fill(0, count($excludeIds), '?')) . ")" : "";

                        $sqlLike = "SELECT id, content, tags FROM kearifan_lokal 
                                            WHERE (content LIKE ? OR tags LIKE ?) 
                                            $placeholders 
                                            LIMIT $needed";

                        $stmtLike = $db->prepare($sqlLike);

                        // Combine the LIKE and excluded ID parameters
                        $params = ["%$cleanQuery%", "%$cleanQuery%"];
                        if (!empty($excludeIds)) {
                            $params = array_merge($params, $excludeIds);
                        }

                        $stmtLike->execute($params);
                        $results = array_merge($results, $stmtLike->fetchAll(PDO::FETCH_ASSOC));
                    }

                    // 2. Result Format for AI Prompt
                    $contextArr = [];
                    foreach ($results as $row) {
                        $tagLabel = !empty($row['tags']) ? "[Tags: " . $row['tags'] . "]" : "[No Tags]";
                        $contextArr[] = "- $tagLabel " . $row['content'];
                    }
                    $contextText = implode("\n", $contextArr);

                } catch (PDOException $e) {
                    $results = [];
                }
            }

            // --- STEP 2: Augment Prompt ---
            // Build Prompt dynamically
            if (!empty($contextText)) {
                $finalPrompt = "Gunakan data referensi berikut untuk menjawab pertanyaan.\n" . $contextText . "\nPertanyaan: " . $prompt;
            }
        }

        // 2. Escape prompt for shell security
        $userPrompt = escapeshellarg($finalPrompt);

        // 3. Build command (Use $user Prompt, not $final Prompt!)
        // Add quotes around the model and prompt for extra security
        $command = "{$this->ollamaPath} run {$this->model} {$userPrompt} 2>&1";

        // 4. Execution
        $output = shell_exec($command);

        if ($output === null) {
            return ["error" => true, "message" => "Gagal mengeksekusi Ollama binary."];
        }

        return $this->processResponse($output);
    }

    public function addEmbeded($content, $tags) {
        $status = false;
        $tags = strtolower(trim($tags)) ?? ''; // Keep tags in lowercase for consistency
        $content = $this->cleanForIndex($content) ?? '';

        if($content === '') {
            return $status;
        }

        // automatic sentence correction before entering FTS Index
        $content = $this->refineContent($content);

        // Connect to DB
        $db = new PDO('sqlite:'.$this->db_file);

        try {
            $db->beginTransaction();

            // 1. Save to DB
            $stmt = $db->prepare("INSERT INTO kearifan_lokal (content, tags) VALUES (?, ?)");
            $stmt->execute([$content, $tags]);
            $newId = $db->lastInsertId();

            // 2. Update FTS5 Incrementally (New rows only)
            // Make sure the tags column is already in your FTS5 schema!
            $stmtFts = $db->prepare("INSERT INTO kearifan_lokal_fts (rowid, content, tags) VALUES (?, ?, ?)");
            $stmtFts->execute([$newId, $content, $tags]);

            $db->commit();
            $status = true;
        } catch (Exception $e) {
            $db->rollBack();
        }

        return $status;
    }

    public function editEmbeded($id, $content, $tags) {
        $status = false;
        $id = (int)$id ?? null;
        $tags = strtolower(trim($tags)) ?? ''; // Keep tags in lowercase for consistency
        $content = $this->cleanForIndex($content) ?? '';        

        if(is_null($id) || $content === '') {
            return $status;
        }

        // automatic sentence correction before entering FTS Index
        $content = refineContent($content);

        // Connect to DB
        $db = new PDO('sqlite:'.$this->db_file);

        try {
            $db->beginTransaction();
    
            // 1. Get OLD data before updating (Mandatory to delete clean FTS5 index)
            $stmtOld = $db->prepare("SELECT content, tags FROM kearifan_lokal WHERE id = ?");
            $stmtOld->execute([$id]);
            $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
    
            if ($old) {
                // 2. Remove the OLD index from the FTS5 table
                // FTS5 needs precise old data to remove the word pointer from the index
                $stmtDel = $db->prepare("INSERT INTO kearifan_lokal_fts(kearifan_lokal_fts, rowid, content, tags) 
                                         VALUES('delete', ?, ?, ?)");
                $stmtDel->execute([$id, $old['content'], $old['tags']]);
            }
    
            // 3. Update data in the MASTER table
            $stmtUpd = $db->prepare("UPDATE kearifan_lokal SET content = ?, tags = ? WHERE id = ?");
            $stmtUpd->execute([$content, $tags, $id]);
    
            // 4. Insert NEW index into FTS5 table
            $stmtIns = $db->prepare("INSERT INTO kearifan_lokal_fts(rowid, content, tags) VALUES(?, ?, ?)");
            $stmtIns->execute([$id, $content, $tags]);
    
            $db->commit();            
            $status = true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
        }

        return $status;
    }

    public function deleteEmbeded($id, $content, $tags) {
        $status = false;
        $id = (int)$id ?? null;

        // Connect to DB
        $db = new PDO('sqlite:'.$this->db_file);

        try {
            
            $db->beginTransaction();

            // 1. Delete from Master table
            $db->prepare("DELETE FROM kearifan_lokal WHERE id = ?")->execute([$id]);
            
            // 2. Delete from the FTS table (Important for synchronous lookups)
            // In FTS5, the rowid is usually the same as the id in the master table if inserted simultaneously.
            $stmtFts = $db->prepare("DELETE FROM kearifan_lokal_fts WHERE rowid = ?");
            $stmtFts->execute([$id]);

            $db->commit();            
            $status = true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
        }

        return $status;
    }

    public function resyncEmbeded() {
        $status = false;

        // Connect to DB
        $db = new PDO('sqlite:'.$this->db_file);

        try {
            $db->beginTransaction();
    
            // 1. Clean & Rebuild FTS5 table with complete column schema (Content + Tags)
            $db->exec("DROP TABLE IF EXISTS kearifan_lokal_fts");
            $db->exec("CREATE VIRTUAL TABLE kearifan_lokal_fts USING fts5(
                content, 
                tags, 
                content='kearifan_lokal', 
                content_rowid='id'
            )");
    
            // 2. Directly move data from Master to FTS at the Database level
            // This doesn't use up any PHP RAM at all!
            $db->exec("INSERT INTO kearifan_lokal_fts(rowid, content, tags) 
                       SELECT id, content, tags FROM kearifan_lokal");
    
            $db->commit();
    
            // Calculate total rows for feedback
            $count = $db->query("SELECT count(*) FROM kearifan_lokal_fts")->fetchColumn();
    
            $status = true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
        }

        return $status;
    }

    protected function cleanForIndex($text) {
        // 1. Remove URLs (http, https, ftp, and www)
        // Remove links to focus the model on informative content
        $urlPattern = '/\b(?:https?|ftp):\/\/\S+|www\.\S+/i';
        $text = preg_replace($urlPattern, '', $text);

        // 2. Decode HTML entities (Example: &nbsp; to spaces)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3. Remove HTML tags
        $text = strip_tags($text);
        
        // 4. Fix spaces after punctuation for accurate LLM tokenization
        // Change "Text.This" to "Text.This"
        $text = preg_replace('/([.!?])(?=[^\s])/', '$1 ', $text);

        // 5. NORMALIZE SPACE (Only horizontal spaces, not newlines)
        // [ \t\f] is space and tab, not \r or \n
        $text = preg_replace('/[ \t\f]+/', ' ', $text);
        
        // 6. Limit excessive newlines (Maximum 2 consecutive newlines to avoid empty spaces)
        $text = preg_replace("/(\r\n|\n|\r){3,}/", "\n\n", $text);
        
        // 7. Remove the remaining non-printable characters using Unicode properties
        $text = preg_replace('/[^\PC\s]/u', '', $text);
        
        return trim($text);
    }

    // Function to check what model is currently active in RAM
    protected function getActiveModel($host) {
        $res = @file_get_contents("http://$host:11434/api/ps");
        $data = json_decode($res, true);
        // Retrieves the name of the first active model (if any)
        return $data['models'][0]['name'] ?? null;
    }

    // Function to unload certain models from RAM
    protected function unloadModel($host, $modelName) {
        $ch = curl_init("http://$host:11434/api/generate");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => $modelName,
            "keep_alive" => 0,
            "stream" => false
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_exec($ch);
        curl_close($ch);
    }

    protected function createFtsDb() {
        try {
            // Setup FTSs
            $db = new PDO('sqlite:'.$this->db_file);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec("PRAGMA journal_mode = WAL;");
    
            // 1. Master Table (Main Data)
            $db->exec("CREATE TABLE IF NOT EXISTS kearifan_lokal (
                id INTEGER PRIMARY KEY, 
                content TEXT, 
                tags TEXT,
                vector BLOB,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
    
            // 2. FTS5 Virtual Table (For Quick Search)
            // We include 'tags' so they can also be searched via FTS
            $db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS kearifan_lokal_fts USING fts5(
                content, 
                tags, 
                content='kearifan_lokal', 
                content_rowid='id'
            )");
        } catch (Exception $e) {
            $error = $e->getMessage();
            $timestamp = date('Y-m-d H:i:s');
    
            $content = "\n==========================================START\n";
            $content .= "WAKTU  : $timestamp\n";
            $content .= "------------------------------------------\n";
            $content .= "ERROR  :\n$error\n";
            $content .= "==========================================END\n\n";

            \App\Core\Support\Log::error('Create SQLite DB: ' . $content, 'App\Neuron\OllamaExec.createFtsDb');
        }
    }

    // automatic sentence correction before entering the FTS Index
    protected function refineContent($text) {
        $jsonFile = storage_path('/ollama/dictionary.json');
        $dictionary = [];

        // Check if the file exists, if it exists load its contents
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $dictionary = json_decode($jsonContent, true) ?? [];
        }

        // If the file fails to load or is empty, use the minimal default to avoid errors
        if (empty($dictionary)) {
            $dictionary = [
                ' nggak ' => ' tidak ',
                ' gak '    => ' tidak ',
                ' krn '    => ' karena ',
                ' yg '     => ' yang ',
                ' bgt '    => ' sangat ',
                ' dngn '   => ' dengan ',
                ' sbg '    => ' sebagai ',
                ' kpd '    => ' kepada ',
                ' tdk '    => ' tidak ',
                ' sdh '    => ' sudah ',
                ' blm '    => ' belum '
            ];
        }

        // Use Regex Boundary (\b) to only replace complete words
        // This prevents the word 'very' from changing to 'baveryet' because there is 'very'
        foreach ($dictionary as $slang => $formal) {
            // \b is word boundary, /i is case-insensitive
            $pattern = '/\b' . preg_quote($slang, '/') . '\b/i';
            $text = preg_replace($pattern, $formal, $text);
        }

        return !empty($text) ? ucfirst($text) : $text;
    }

    /**
     * Cleans output of ANSI escape codes and CLI progress residue
     */
    private function processResponse($output) {
        // 1. Remove ANSI Escape Sequences (Color & Cursor Movement)
        $cleanOutput = preg_replace('/\x1b[[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/', '', $output);
        
        // 2. Remove Braille Characters (Noise Spinner Ollama: U+2800 to U+28FF)
        // This character usually appears as "⠙ ⠹ ⠸"
        $cleanOutput = preg_replace('/[\x{2800}-\x{28FF}]/u', '', $cleanOutput);
        
        // 3. Remove excess whitespace at the beginning due to noise removal
        $cleanOutput = ltrim($cleanOutput);

        // 4. If there is still "faint text" or other control characters (such as \r or \b)
        $cleanOutput = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanOutput);
        
        // Removed progress strings like "loading model..." that sometimes leaked to stdout.
        $cleanOutput = preg_replace('/pulling.*?\d+%/i', '', $cleanOutput);
        
        return trim($cleanOutput) ?: "AI tidak memberikan respon.";
    }
}