<?php

declare(strict_types=1);


class Stubs
{
    // Terminal Color Constants
    public const CLR_SUCCESS = "\033[0;32m"; // Green
    public const CLR_ERROR   = "\033[0;31m"; // Red
    public const CLR_INFO    = "\033[0;34m"; // Blue
    public const CLR_BOLD    = "\033[1m";    // Thick
    public const CLR_RESET   = "\033[0m";    // Color Reset

    /**
     * Centralized helper to create a directory if it doesn't exist yet
     */
    public static function ensureDirectoryExists(string $dirPath, int $permissions = 0755): void
    {
        if (!is_dir($dirPath)) {
            mkdir($dirPath, $permissions, true);
        }
    }

    /**
     * Dynamic function to generate PHP files from stubs (Controllers, Models, Services, etc.)
     */
    public static function generate(string $newName, string $stubPath, string $targetDir): string
    {
        // 1. Validate source files
        if (!file_exists($stubPath)) {
            return self::CLR_ERROR . "❌ Error: Stub file not found in {$stubPath}" . self::CLR_RESET . PHP_EOL;
        }

        // 2. Normalize path separator
        $normalizedDir = str_replace('\\', '/', $targetDir);
        $cleanPath = trim($normalizedDir, './');

        // 3. Format Target Directory & Ensure Directory Exists
        $dirParts = array_filter(explode('/', $cleanPath));
        $formattedDirParts = array_map(fn ($part) => strtolower($part) === 'app' ? 'app' : ucfirst($part), $dirParts);
        $finalTargetDir = implode('/', $formattedDirParts);

        self::ensureDirectoryExists($finalTargetDir);

        // 4. Read stub content
        $content = file_get_contents($stubPath);

        // 5. Generate PSR-4 Namespace dynamically
        if (!str_contains($finalTargetDir, 'stubs')) {
            $namespaceParts = array_map(fn ($part) => ucfirst($part), $dirParts);
            $newNamespace = implode('\\', $namespaceParts);

            // Replace namespace in stub content
            $content = preg_replace('/namespace\s+[^;]+;/', "namespace {$newNamespace};", $content);
        } else {
            $newNamespace = str_contains($newName, 'Model') ? 'App\\Models' : 'App\\Controllers';
        }

        // 6. Dynamic Class Name Logic
        $content = preg_replace('/class\s+\w+/', "class {$newName}", $content);

        // 7. Save File
        $destination = rtrim($finalTargetDir, '/') . "/{$newName}.php";

        if (file_exists($destination)) {
            return self::CLR_ERROR . "⚠️  Error: File '{$newName}.php' already exists in {$finalTargetDir}!" . self::CLR_RESET . PHP_EOL;
        }

        if (file_put_contents($destination, $content) !== false) {
            $output  = self::CLR_SUCCESS . self::CLR_BOLD . "✅ Success: " . self::CLR_RESET;
            $output .= self::CLR_SUCCESS . "'{$newName}' created successfully." . self::CLR_RESET . PHP_EOL;
            $output .= self::CLR_INFO . "📌 Namespace: " . self::CLR_RESET . "{$newNamespace}" . PHP_EOL;
            $output .= self::CLR_INFO . "📍 Location: " . self::CLR_RESET . "{$destination}" . PHP_EOL;
            return $output;
        }

        return self::CLR_ERROR . "❌ Error: Failed to write file to disk." . self::CLR_RESET . PHP_EOL;
    }

    /**
     * Specifically for generating View files (HTML/PHP)
     */
    public static function generateView(string $newName, string $stubPath, string $targetDir): string
    {
        if (!file_exists($stubPath)) {
            return self::CLR_ERROR . "❌ Error: View stub file not found in {$stubPath}" . self::CLR_RESET . PHP_EOL;
        }

        $normalizedDir = str_replace('\\', '/', $targetDir);
        self::ensureDirectoryExists($normalizedDir, 0775);

        $content = file_get_contents($stubPath);
        $content = str_replace(['{{title}}', '{{name}}'], $newName, $content);

        $fileName = strtolower($newName);
        $destination = rtrim($normalizedDir, '/') . "/{$fileName}.php";

        if (file_exists($destination)) {
            return self::CLR_ERROR . "⚠️  Error: View '{$destination}' already exists!" . self::CLR_RESET . PHP_EOL;
        }

        if (file_put_contents($destination, $content) !== false) {
            $output  = self::CLR_SUCCESS . self::CLR_BOLD . "🎨 Success: " . self::CLR_RESET;
            $output .= self::CLR_SUCCESS . "View '{$fileName}' created successfully." . self::CLR_RESET . PHP_EOL;
            $output .= self::CLR_INFO . "📍 Location: " . self::CLR_RESET . "{$destination}" . PHP_EOL;
            return $output;
        }

        return self::CLR_ERROR . "❌ Error: Failed to write view file." . self::CLR_RESET . PHP_EOL;
    }

    /**
     * Converts input to PascalCase + Suffix
     * Example: "admin_settings" + "Model" -> "AdminSettingsModel"
     */
    public static function formatClassName(string $input, string $suffix = ''): string
    {
        $cleanName = preg_replace("/{$suffix}$/i", '', trim($input));
        $pascal = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $cleanName)));

        return $pascal . $suffix;
    }

    /**
     * Converts input to snake_case for View
     * Example: "UserDetail" -> "user_detail"
     */
    public static function formatViewName(string $input): string
    {
        $input = preg_replace('/([a-z])([A-Z])/', '$1_$2', trim($input));
        $snake = strtolower(str_replace([' ', '-'], '_', $input));

        return (string) preg_replace('/__+/', '_', $snake);
    }
}
