<?php

class Stubs {

    // Terminal Color Constants
    const CLR_SUCCESS = "\033[0;32m"; // Green
    const CLR_ERROR   = "\033[0;31m"; // Red
    const CLR_INFO    = "\033[0;34m"; // Blue
    const CLR_BOLD    = "\033[1m";    // Thick
    const CLR_RESET   = "\033[0m";    // Color Reset

    /**
     * Dynamic function to generate files from stubs
     */
    public static function generate(string $newName, string $stubPath, string $targetDir)
    {
        // 1. Validate source files
        if (!file_exists($stubPath)) {
            return self::CLR_ERROR . "❌ Error: Stub file not found in $stubPath" . self::CLR_RESET . "\n";
        }

        // 2. Create a folder if it doesn't exist yet
        $targetDirParts = explode('/', $targetDir);
        $formattedDirParts = array_map('ucfirst', $targetDirParts);
        $targetDir = implode('/', $formattedDirParts);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 3. Read the contents of the stub file
        $content = file_get_contents($stubPath);

        // --- A. LOGIKA NAMESPACE ---
        if (!str_contains($targetDir, 'stubs')) {
            //1. Clean up the path: remove the "./" at the front and the "/" at the end
            $cleanPath = ltrim($targetDir, './'); 
            $cleanPath = rtrim($cleanPath, '/');
            
            // 2. Normalize folder separators to be consistent (Windows/Linux)
            $normalizedPath = str_replace('\\', '/', $cleanPath);
            
            // 3. Break the folder into parts
            $folderParts = explode('/', $normalizedPath);
            
            // 4. AUTO-CORRECTION: Force each section to be PascalCase
            // Contoh: "app/core/models" -> ["App", "Core", "Models"]
            $formattedParts = array_map(function($part) {
                // List of folders usually abbreviated (optional)
                // $specialCases = ['api' => 'API', 'url' => 'URL', 'id' => 'ID'];
                $lowerPart = strtolower($part);
                
                // if (isset($specialCases[$lowerPart])) {
                //     return $specialCases[$lowerPart];
                // }
                
                return ucfirst($lowerPart);
            }, array_filter($folderParts));
            
            $newNamespace = implode('\\', $formattedParts);
            
            // 5. Update stub file contents with regex
            $content = preg_replace('/namespace\s+[^;]+;/', "namespace {$newNamespace};", $content);
        } else {
            // Default Namespace untuk folder stubs
            $newNamespace = str_contains($newName, 'Model') ? 'App\\Models' : 'App\\Controllers';
        }

        // --- B. DYNAMIC CLASS NAME LOGIC ---
        $content = preg_replace('/class\s+\w+/', "class {$newName}", $content);

        // --- C. SAVE FILES ---
        $destination = rtrim($targetDir, '/') . "/{$newName}.php";

        if (file_exists($destination)) {
            return self::CLR_ERROR . "⚠️  Error: File {$newName}.php it's already exists in {$targetDir}!" . self::CLR_RESET . "\n";
        }

        if (file_put_contents($destination, $content)) {
            $output = self::CLR_SUCCESS . self::CLR_BOLD . "✅ Success: " . self::CLR_RESET;
            $output .= self::CLR_SUCCESS . "'{$newName}' created successfully." . self::CLR_RESET . "\n";
            $output .= self::CLR_INFO . "📌 Namespace: " . self::CLR_RESET . "{$newNamespace}\n";
            $output .= self::CLR_INFO . "📍 Location: " . self::CLR_RESET . "{$destination}\n";
            return $output;
        }

        return self::CLR_ERROR . "❌ Error: Failed to write file to disk." . self::CLR_RESET . "\n";
    }

    /**
     * Specifically for generating View files (HTML/PHP)
     * Without Namespace and Class logic
     */
    public static function generateView(string $newName, string $stubPath, string $targetDir)
    {
        // 1. Validate source files
        if (!file_exists($stubPath)) {
            return self::CLR_ERROR . "❌ Error: View stub file not found in $stubPath" . self::CLR_RESET . "\n";
        }

        // 2. Create a folder if it doesn't exist yet
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        // 3. Read the contents of the stub file
        $content = file_get_contents($stubPath);

        // --- A. DYNAMIC PLACEHOLDER LOGIC (Optional) ---
        // Replace {{title}} or {{name}} in the HTML if present
        $content = str_replace(['{{title}}', '{{name}}'], $newName, $content);

        // --- B. SAVE FILES ---
        // Make sure the file name is lowercase for standard View
        $fileName = strtolower($newName);
        $destination = rtrim($targetDir, '/') . "/{$fileName}.php";

        if (file_exists($destination)) {
            return self::CLR_ERROR . "⚠️  Error: View {$destination} already exists!" . self::CLR_RESET . "\n";
        }

        if (file_put_contents($destination, $content)) {
            $output = self::CLR_SUCCESS . self::CLR_BOLD . "🎨 Success: " . self::CLR_RESET;
            $output .= self::CLR_SUCCESS . "View '{$fileName}' created successfully." . self::CLR_RESET . "\n";
            $output .= self::CLR_INFO . "📍 Location: " . self::CLR_RESET . "{$destination}\n";
            return $output;
        }

        return self::CLR_ERROR . "❌ Error: Failed to write view file." . self::CLR_RESET . "\n";
    }

     /**
     * Function to generate a new model from stub
     * @param string $newClassName New class name (eg: 'DashboardModel')
     * @param string $stubPath Source file path
     * @param string $targetDir Storage destination directory
     */
    public static function generateModelFromStub(string $newClassName, string $stubPath, string $targetDir)
    {
        // 1. Validate source files
        if (!file_exists($stubPath)) {
            return "Error: Stub file not found in $stubPath";
        }

        // 2. Read the contents of the stub file
        $content = file_get_contents($stubPath);

        // -- A. Fix Namespace By Folder --    
        // Check that targetDir does NOT contain the word "stubs"
        if (!str_contains($targetDir, 'stubs')) {

            // Converting App/Models to App\Models (Standard PSR-4)
            // ucfirst each section of the folder to keep the namespace tidy (Example: app/models -> App\Models)
            $folderParts = explode('/', $targetDir);
            $formattedParts = array_map('ucfirst', $folderParts);
            $newNamespace = implode('\\', $formattedParts);

            // Converting App/Models to App\Models
            $newNamespace = str_replace('/', '\\', $targetDir);

            // If the input folder starts with 'App', we assume it is the main namespace
            $content = preg_replace('/namespace\s+[^;]+;/', "namespace {$newNamespace};", $content);
        } else {
            $newNamespace = str_replace('/', '\\', 'App/Models');
            // echo "ℹ️  Menjaga namespace asli (Folder stub terdeteksi).\n";
        }

        // -- B. Rename Class --
        $content = str_replace('class MyModel', "class {$newClassName}", $content);

        // 6. Save Files
        $destination = "{$targetDir}/{$newClassName}.php";

        if (file_exists($destination)) {
            die("⚠️ Error: File {$newClassName}.php it's already exists in that folder!\n");
        }

        if (file_put_contents($destination, $content)) {
            echo "✅ Success: Model '{$newClassName}' created successfully.\n";
            // if (!str_contains($targetDir, 'stubs')) {
                echo "📌 Namespace: {$newNamespace}\n";
            // }
            echo "📍 Location: {$destination}\n";
        } else {
            echo "❌ Error: Failed to write file.\n";
        }
    }

    /**
     * Converts input to PascalCase + Suffix
     * Example: "user_setting" + "Controller" -> "UserSettingController"
     */
    public static function formatClassName(string $input, string $suffix): string 
    {
        // 1. Clear the suffix if the user has already typed it (case-insensitive)
        // Using regex so that "usercontroller" remains "User" before adding the official suffix
        $cleanName = preg_replace("/$suffix$/i", '', trim($input));
        
        // 2. Change snake_case/kebab-case to PascalCase
        // "product_detail" -> "Product Detail" -> "ProductDetail"
        $pascal = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $cleanName)));
        
        return $pascal . $suffix;
    }

    /**
     * Converts input to snake_case for View
     * Example: "UserDetail" -> "user_detail"
     */
    public static function formatViewName(string $input): string 
    {
        // Separate capital letters with underscores (for Pascal to Snake handles)
        $input = preg_replace('/([a-z])([A-Z])/', '$1_$2', trim($input));
        
        // Make everything smaller and change the space/dash to an underscore
        $snake = strtolower(str_replace([' ', '-'], '_', $input));
        
        // Clean double underscores if any
        return preg_replace('/__+/', '_', $snake);
    }
}
