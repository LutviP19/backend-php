<?php

/**
 * Inject php native functions.
 * @author Lutvi <lutvip19@gmail.com>
 */


// --- Base64URL Encoding/Decoding Functions ---
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function array_keys_exists(array $keys, array $array): bool
{
    $diff = array_diff_key(array_flip($keys), $array);
    return count($diff) === 0;
}

function recursive_unset(&$array, $unwanted_key = '')
{
    // Check if the unwanted key exists at the current level and unset it
    if (array_key_exists($unwanted_key, $array)) {
        unset($array[$unwanted_key]);
    }

    // Iterate through the current array's values
    foreach ($array as &$value) {
        // If a value is an array, call the function recursively
        if (is_array($value)) {
            recursive_unset($value, $unwanted_key);
        }
    }
}

function mergeObjectsRecursively($obj1, $obj2)
{
    $merged = clone $obj1;
    foreach ($obj2 as $key => $value) {
        if (is_object($value) && isset($merged->$key) && is_object($merged->$key)) {
            $merged->$key = mergeObjectsRecursively($merged->$key, $value);
        } elseif (is_array($value) && isset($merged->$key) && is_array($merged->$key)) {
            $merged->$key = array_merge_recursive($merged->$key, $value);
        } else {
            $merged->$key = $value;
        }
    }
    return $merged;
}

function numberFormatIndo($num, $decimal = 0)
{
    return number_format($num, $decimal, ",", ".");
}

function format_with_rounding($number, $precision = 0, $mode = PHP_ROUND_HALF_UP, $decimal_separator = ',', $thousands_separator = '.')
{
    // Truncate the number to the desired number of decimal places without rounding
    $truncated_number = round($number, $precision, $mode);

    // Format the truncated number using number_format for display
    return number_format($truncated_number, $decimals, $decimal_separator, $thousands_separator);
}

function format_without_rounding($number, $decimals = 2, $decimal_separator = ',', $thousands_separator = '.')
{
    // Truncate the number to the desired number of decimal places without rounding
    $truncated_number = bcdiv((string) $number, 1, $decimals);

    // Format the truncated number using number_format for display
    return number_format($truncated_number, $decimals, $decimal_separator, $thousands_separator);
}

function addPadIfShort(string $inputString, int $minLength = 50, $stringPad = ' '): string
{
    if (mb_strlen($inputString) < $minLength) {
        return str_pad($inputString, $minLength, $stringPad, STR_PAD_RIGHT);
    }
    return $inputString;
}

function formatMapAddress($addressMap, $sort = false)
{
    $pattern = '/[a-z0-9]+\+[a-z0-9]+/i';
    $address = explode("||", (string) $addressMap);
    // $output = $addressMap;

    // Check if the format is found within the input string
    if (isset($address[1]) && preg_match($pattern, $address[1], $matches)) {
        if (isset($matches[0])) {
            $output = trim(str_replace($matches[0], "", $address[1]), ",");
        } else {
            $output = $address[1];
        }

        if ($sort) {
            return trim(explode(",", $output)[0]);
        }

        return $output;
    }

    if (isset($address[1])) {
        if ($sort) {
            return trim(explode(",", $address[0])[0]);
        }

        return $address[1];
    }

    return $addressMap;
}

function setArrivedTime($dateTimeStr, $minutesToAdd = 30, $formated = 'H:i A')
{
    $dateTime = new DateTime($dateTimeStr);
    $dateTime->modify("+{$minutesToAdd} minutes");

    return $dateTime->format($formated);
}

function convertMinutesToHoursAndMinutes($totalMinutes, $simpleText = true)
{
    if (!is_numeric($totalMinutes) || $totalMinutes < 0) {
        // return "Invalid input. Please provide a non-negative number of minutes.";
        return sprintf("%d Min", 0);
    }

    $hours = floor($totalMinutes / 60); // Get the whole number of hours
    $minutes = $totalMinutes % 60;    // Get the remaining minutes

    if (!$simpleText) {
        $plurals = $hours > 1 ? "Hours" : "Hour";
        if ($hours > 0) {
            return sprintf("%d %d and %d Minutes", $hours, $plurals, $minutes);
        }
        return sprintf("%d Minutes", $minutes);
    }

    if ($hours > 0) {
        return sprintf("%d H, %d Min", $hours, $minutes);
    }
    return sprintf("%d Min", $minutes);
}

/**
 * Implemented to replace FILTER_SANITIZE_STRING behaviour deprecated in php8.1
 *
 * @param mixed $value
 * @return string
 */
function polyfill_filter_var_string($value)
{
    $str = preg_replace('/\x00|<[^>]*>?/', '', (string) $value);
    return (string)str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
}

/**
 * camelCaseToUnderscore function
 *
 * @param  [string] $inputString
 *
 * @return string
 *
 * example:
 * $camelCaseString = "thisIsACamelCaseString";
 * echo camelCaseToUnderscore($camelCaseString); // Output: this_is_a_camel_case_string
 */
function camelCaseToUnderscore($inputString)
{
    // Add an underscore before each uppercase letter, unless it's at the beginning of the string
    $snakeCaseString = preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $inputString);
    // Convert the entire string to lowercase
    $snakeCaseString = strtolower((string) $snakeCaseString);
    return $snakeCaseString;
}

/**
 * underscoreToCamelCase function
 *
 * @param  [string] $inputString
 *
 * @return string
 *
 * example:
 * $underscoreString = "this_is_a_snake_case_string";
 * echo underscoreToCamelCase($underscoreString); // Output: thisIsASnakeCaseString
 */
function underscoreToCamelCase($inputString, $prefix = '')
{
    if ($prefix !== '') {
        $inputString = $prefx . '_' . $inputString;
    }

    // Replace underscores with spaces
    $inputString = str_replace('_', ' ', $inputString);
    // Capitalize the first letter of each word
    $inputString = ucwords($inputString);
    // Remove spaces to form camelCase
    $inputString = str_replace(' ', '', $inputString);
    // Optionally, convert the first letter to lowercase for lower camel case
    $inputString = lcfirst($inputString);

    return $inputString;
}

function format_array_key($string)
{
    // Lowercase all letters
    $string = strtolower($string);
    // Replace spaces, non-alphanumeric characters, and \u00a0 with underscores
    $string = preg_replace('/[\s\x{00a0}-]+/u', '_', $string);
    // Remove any other characters that are not letters, numbers, or underscores
    $string = preg_replace('/[^a-z0-9_]/', '', $string);
    // Clean double underscores or at the ends of strings
    return trim(preg_replace('/_+/', '_', $string), '_');
}

function str_replace_multi(array $replace, string $subject)
{
    return str_replace(array_keys($replace), array_values($replace), $subject);
}

function is_float_string($value)
{
    // return is_numeric($value) && strpos($value, '.') !== false;
    return is_numeric($value) && str_contains($value, '.');
}

function is_decimal($n)
{
    // Note that floor returns a float
    return is_numeric($n) && floor($n) != $n;
}

/**
 * Divide an array into two arrays. One with keys and the other with values.
 *
 * @param  array  $array
 * @return array
 */
function divideArray($array)
{
    return [array_keys($array), array_values($array)];
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param  iterable  $array
 * @param  string  $prepend
 * @return array
 */
function dotArray($array, $prepend = '')
{
    $results = [];

    foreach ($array as $key => $value) {
        if (is_array($value) && ! empty($value)) {
            $results = array_merge($results, dotArray($value, $prepend.$key.'.'));
        } else {
            $results[$prepend.$key] = $value;
        }
    }

    return $results;
}

/**
 * Convert a flatten "dot" notation array into an expanded array.
 *
 * @param  iterable  $array
 * @return array
 */
function undotArray($array)
{
    $results = [];

    foreach ($array as $key => $value) {
        setDotArray($results, $key, $value);
    }

    return $results;
}

/**
 * Set an array item to a given value using "dot" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * @param  array  $array
 * @param  string|int|null  $key
 * @param  mixed  $value
 * @return array
 */
function setDotArray(&$array, $key, $value)
{
    if (is_null($key)) {
        return $array = $value;
    }

    $keys = explode('.', $key);

    foreach ($keys as $i => $key) {
        if (count($keys) === 1) {
            break;
        }

        unset($keys[$i]);

        // If the key doesn't exist at this depth, we will just create an empty array
        // to hold the next value, allowing us to create the arrays to hold final
        // values at the correct depth. Then we'll keep digging into the array.
        if (! isset($array[$key]) || ! is_array($array[$key])) {
            $array[$key] = [];
        }

        $array = &$array[$key];
    }

    $array[array_shift($keys)] = $value;

    return $array;
}

/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param  iterable  $array
 * @param  int  $depth
 * @return array
 */
function flattenArray($array, $depth = INF)
{
    $result = [];

    foreach ($array as $item) {
        $item = $item instanceof \Collection ? $item->all() : $item;

        if (! is_array($item)) {
            $result[] = $item;
        } else {
            $values = $depth === 1
                ? array_values($item)
                : flattenArray($item, $depth - 1);

            foreach ($values as $value) {
                $result[] = $value;
            }
        }
    }

    return $result;
}

/**
 * Wrap the string with the given strings.
 *
 * @param  string  $value
 * @param  string  $before
 * @param  string|null  $after
 * @return string
 */
function wrapStr($value, $before, $after = null)
{
    return $before.$value.($after ??= $before);
}

/**
 * Limit the number of characters in a string.
 *
 * @param  string  $value
 * @param  int  $limit
 * @param  string  $end
 * @param  bool  $preserveWords
 * @return string
 */
function limitStr($value, $limit = 100, $end = '...', $preserveWords = false)
{
    if (mb_strwidth($value, 'UTF-8') <= $limit) {
        return $value;
    }

    if (! $preserveWords) {
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }

    $value = trim((string) preg_replace('/[\n\r]+/', ' ', strip_tags($value)));

    $trimmed = rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8'));

    if (mb_substr($value, $limit, 1, 'UTF-8') === ' ') {
        return $trimmed.$end;
    }

    return preg_replace("/(.*)\s.*/", '$1', $trimmed).$end;
}

/**
 * Limit the number of words in a string.
 *
 * @param  string  $value
 * @param  int  $words
 * @param  string  $end
 * @return string
 */
function limitWordsStr($value, $words = 100, $end = '...')
{
    preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

    if (! isset($matches[0]) || mb_strlen($value) === mb_strlen($matches[0])) {
        return $value;
    }

    return rtrim($matches[0]).$end;
}


/**
 * Remove all whitespace from both ends of a string.
 *
 * @param  string  $value
 * @param  string  $mode : both | ltrim | rtrim
 * @param  string|null  $charlist
 * @return string
 */
function trimStr($value, $mode = 'both', $charlist = null)
{
    if ($charlist === null) {
        $trimDefaultCharacters = " \n\r\t\v\0";

        if ($mode === 'ltrim') {
            return preg_replace('~^[\s\x{FEFF}\x{200B}\x{200E}'.$ltrimDefaultCharacters.']+~u', '', $value) ?? ltrim($value);
        } elseif ($mode === 'rtrim') {
            return preg_replace('~[\s\x{FEFF}\x{200B}\x{200E}'.$rtrimDefaultCharacters.']+$~u', '', $value) ?? rtrim($value);
        } else {
            return preg_replace('~^[\s\x{FEFF}\x{200B}\x{200E}'.$trimDefaultCharacters.']+|[\s\x{FEFF}\x{200B}\x{200E}'.$trimDefaultCharacters.']+$~u', '', $value) ?? trim($value);
        }
    }

    if ($mode === 'ltrim') {
        return ltrim($value, $charlist);
    } elseif ($mode === 'rtrim') {
        return rtrim($value, $charlist);
    } else {
        return trim($value, $charlist);
    }
}

/**
 * Remove all "extra" blank space from the given string.
 *
 * @param  string  $value
 * @return string
 */
function squishStr($value)
{
    return preg_replace('~(\s|\x{3164}|\x{1160})+~u', ' ', trimStr($value));
}

// function tofloat($num) {
//     $dotPos = strrpos($num, '.');
//     $commaPos = strrpos($num, ',');
//     $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
//         ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
//     if (!$sep) {
//         return floatval(preg_replace("/[^0-9]/", "", $num));
//     }

//     return floatval(
//         preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
//         preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
//     );
// }

// function floattostr( $val )
// {
//     preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o );
//     return $o[1].sprintf('%d',$o[2]).($o[3]!='.'?$o[3]:'');
// }

function checkSession()
{
    try {

        // if(!is_numeric($_SESSION['user_id']))
        //     throw new Exception('No session started.');

        if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR']) {
            throw new Exception('IP Address mixmatch (possible session hijacking attempt).');
        }

        if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
            throw new Exception('Useragent mixmatch (possible session hijacking attempt).');
        }

        // if(!$this->loadUser($_SESSION['user_id']))
        //     throw new Exception('Attempted to log in user that does not exist with ID: ' . $_SESSION['user_id']);

        return true;

    } catch (Exception $e) {
        return false;
    }
}

function bp_session_start()
{
    // Pastikan session belum aktif sebelum mengubah konfigurasi ini
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', 1);
        @session_start();
    }

    if (isset($_SESSION['destroyed'])) {
        $ttl = (int)env('SESSION_REGENERATE', 300);

        // Jika session lama sudah melewati batas toleransi TTL, regenerasi ID baru
        if (!empty($_SESSION['destroyed']) && $_SESSION['destroyed'] < time() - $ttl) {
            $oldSessionId = session_id();
            
            // Lakukan rotasi ID session
            $headers = bp_session_regenerate_id($oldSessionId);
            
            // Pastikan fungsi setHeaders ada sebelum dipanggil (khusus OpenSwoole)
            if (function_exists('setHeaders')) {
                setHeaders($headers);
            }
        }
    }
}

function bp_session_regenerate_id($oldSessionId = null)
{
    $oldSessionId = $oldSessionId ?: session_id();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // 1. Ambil data session saat ini sebelum dicommit/ditutup
    $keepSession = $_SESSION;

    // 2. Buat ID baru menggunakan fungsi internal PHP yang aman
    $new_session_id = session_create_id();

    // 3. Tandai session lama sebagai hancur dan berikan info ID baru untuk toleransi koneksi tidak stabil
    $_SESSION['new_session_id'] = $new_session_id;
    $_SESSION['destroyed'] = time();
    
    // Simpan penanda hancur ini ke session ID lama terlebih dahulu
    session_commit();

    // 4. Mulai session baru dengan ID baru yang telah dibuat
    ini_set('session.use_strict_mode', 0);
    session_id($new_session_id);
    ini_set('session.use_strict_mode', 1);

    @session_start();
    
    // Salin kembali semua data dari session lama ke session ID baru
    $_SESSION = $keepSession;
    
    // Hapus penanda hancur di session baru agar tidak terjadi perulangan tanpa akhir (infinite loop)
    unset($_SESSION['destroyed'], $_SESSION['new_session_id']);
    
    // Tulis data ke session baru dan kunci/tutup sementara
    session_commit();

    // 5. Bersihkan data dari session ID lama di storage (Files / Redis)
    $saveHandler = ini_get('session.save_handler');

    if ($saveHandler === 'files') {
        $sessionSavePath = session_save_path() ?: sys_get_temp_dir();
        $fileSessionOld = rtrim($sessionSavePath, '/\\') . '/sess_' . $oldSessionId;

        if (\file_exists($fileSessionOld)) {
            @unlink($fileSessionOld);
        }
    } elseif ($saveHandler === 'redis' && function_exists('delDataFromRedis')) {
        // Hapus data session lama dari redis
        delDataFromRedis($oldSessionId, 'PHPREDIS_SESSION', '0', true);
    }

    // 6. Siapkan Header Cookie Baru yang Aman (Sesuai parameter bawaan PHP/aplikasi)
    $sessionName = session_name();
    $cookie = session_get_cookie_params();
    $sessionExp = (int)env('SESSION_LIFETIME', 120) * 60;

    // Mulai susun string cookie
    $cookieString = "{$sessionName}={$new_session_id}; Max-Age={$sessionExp}; Path={$cookie['path']};";
    
    // 1. JANGAN gunakan Domain jika menembak ke localhost/127.0.0.1 agar cookie tidak ditolak client
    if (!empty($cookie['domain']) && !in_array($cookie['domain'], ['localhost', '127.0.0.1'])) {
        $cookieString .= " Domain={$cookie['domain']};";
    }
    
    // 2. KRUSIAL: Hanya gunakan Secure jika URL menggunakan HTTPS!
    // Jika di localhost (http://localhost:8008), pastikan 'Secure;' TIDAK MASUK.
    if (!empty($cookie['secure']) && env('APP_ENV') !== 'local') {
        $cookieString .= " Secure;";
    }
    
    $cookieString .= " HttpOnly; SameSite=Lax;";

    return ['Set-Cookie' => $cookieString];
}

function bp_minimum_php_version(string $version = "8.4.0")
{
    if (version_compare(PHP_VERSION, $version, "<")) {
        $message = "ERROR: PHP $version or higher is required. Current: " . PHP_VERSION;

        if (PHP_SAPI === "cli") {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            http_response_code(500);
            print($message);
        }
        exit(1);
    }
}

function bp_curl_close($ch)
{
    if (version_compare(PHP_VERSION, "8.5.0", "<")) {
        curl_close($ch);
    }
}

/**
 * slug function
 *
 * @param  [string] $title
 * @param  string $separator
 * @param  string $language
 * @param  array  $dictionary
 *
 * @return void
 */
function slug($title, $separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
{
    // Convert all dashes/underscores into separator
    $flip = $separator === '-' ? '_' : '-';

    $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, (string) $title);

    // Replace dictionary words
    foreach ($dictionary as $key => $value) {
        $dictionary[$key] = $separator . $value . $separator;
    }

    $title = str_replace(array_keys($dictionary), array_values($dictionary), $title);

    // Remove all characters that are not the separator, letters, numbers, or whitespace
    $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', strtolower($title));

    // Replace all separator characters and whitespace by a single separator
    $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, (string) $title);

    return trim((string) $title, $separator);
}

function formatPhoneSnapshot($phoneNumber)
{
    $phone_number = str_replace('62', '0', trim((string) $phoneNumber));
    return substr($phone_number, 0, 4).'****'.substr($phone_number, 8, strlen($phone_number));
}

function base64ToWebP($base64_string, $output_file, $quality = 80)
{
    // Remove the "data:image/webp;base64," prefix if present
    $data = explode(',', (string) $base64_string);
    $decoded_data = base64_decode(end($data));

    // Create an image resource from the decoded data
    $image = imagecreatefromstring($decoded_data);

    if ($image === false) {
        return false; // Error creating image resource
    }

    // 3. Handle Transparency (Optional but Recommended for PNG/GIF)
    imagepalettetotruecolor($image);
    imagealphablending($image, true);
    imagesavealpha($image, true);

    // Save the image as a WebP file
    $success = imagewebp($image, $output_file, $quality);

    // Free up memory
    imagedestroy($image);

    return $success;
}

function base64ToImage($base64_string, $output_file)
{
    // Separate the metadata from the base64 string
    $parts = explode(',', (string) $base64_string);
    $imageData = base64_decode($parts[1]);

    // Save the decoded data to a file
    if (file_put_contents($output_file, $imageData)) {
        // return 'Image successfully saved to: ' . $output_file;
        return true;
    } else {
        // return 'Failed to save image.';
        return false;
    }
}

// Function to save any image to Webp
function webpImage($source, $quality = 80, $removeOld = false)
{
    $dir = pathinfo((string) $source, PATHINFO_DIRNAME);
    $name = pathinfo((string) $source, PATHINFO_FILENAME);
    $destination = $dir . DIRECTORY_SEPARATOR . $name . '.webp';
    $info = getimagesize($source);
    $isAlpha = false;
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($isAlpha = $info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($isAlpha = $info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return $source;
    }
    if ($isAlpha) {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
    imagewebp($image, $destination, $quality);

    if ($removeOld) {
        unlink($source);
    }

    return $destination;
}