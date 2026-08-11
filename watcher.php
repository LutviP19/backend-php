<?php
/**
 * Custom File Watcher & Auto-Reload for OpenSwoole Server
 *
 * Run these files separately in terminal:
 * php watcher.php
 */

// 1. The folder you want to monitor
$watchDirectories = [
    __DIR__ . '/app',
    __DIR__ . '/config',
    __DIR__ . '/routes',    
    __DIR__ . '/servers',
    __DIR__ . '/views',    
    __DIR__ . '/public', // Place to store .js, .css, etc. files.
];

// 2. File extension that triggers Auto-Reload
$allowedExtensions = ['php', 'js', 'css', 'html', 'json', 'sql'];

// 3. Folders that MUST be ignored (prevents infinite loop/high CPU)
$ignoredDirectories = ['vendor', 'storage', 'node_modules', 'examples', '.git', '.vscode'];

$lastMtime = time();

echo "\033[32m[Watcher Started]\033[0m Memantau perubahan file (" . implode(', ', $allowedExtensions) . ")...\n";

while (true) {
    // Give a 1 second pause for each checking interval so that the CPU doesn't spike
    sleep(1);
    clearstatcache();

    $fileChanged = false;

    foreach ($watchDirectories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            // Ignore ignored directories
            $filePath = $file->getPathname();
            foreach ($ignoredDirectories as $ignored) {
                if (strpos($filePath, DIRECTORY_SEPARATOR . $ignored) !== false) {
                    continue 2;
                }
            }

            // Check if extension is appropriate & mtime is newer than last check
            if ($file->isFile() && in_array($file->getExtension(), $allowedExtensions, true)) {
                if ($file->getMTime() > $lastMtime) {
                    $relativePath = str_replace(__DIR__ . '/', '', $filePath);
                    echo "\033[33m[Auto-Reload]\033[0m Perubahan terdeteksi di: \033[36m{$relativePath}\033[0m (" . date('H:i:s') . ")\n";

                    $fileChanged = true;
                    break 2; // Exit the loop to immediately execute reload
                }
            }
        }
    }

    if ($fileChanged) {
        $lastMtime = time();

        // Cache PID master agar tidak perlu pgrep berulang kali
        static $masterPid = null;

        if ($masterPid === null || !posix_kill($masterPid, 0)) { // Sinyal 0 mengecek apakah proses masih hidup
            $masterPid = trim(shell_exec("pgrep -f 'php servers/web-server.php' | head -n 1"));
        }

        if (!empty($masterPid) && is_numeric($masterPid)) {
            posix_kill((int)$masterPid, SIGUSR1);
            echo "\033[32m[OpenSwoole]\033[0m Signal SIGUSR1 terkirim ke Master PID ({$masterPid}). Worker di-reload!\n";
        } else {
            $masterPid = null; // Reset cache jika tidak ditemukan
            echo "\033[31m[Warning]\033[0m Server OpenSwoole (web-server.php) tidak ditemukan/belum berjalan.\n";
        }
    }
}