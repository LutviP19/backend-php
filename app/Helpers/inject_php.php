<?php

/**
 * Inject php native functions.
 * @author Lutvi <lutvip19@gmail.com>
 */

function array_keys_exists(array $keys, array $array): bool
{
    $diff = array_diff_key(array_flip($keys), $array);
    return count($diff) === 0;
}

function recursive_unset(&$array, $unwanted_key) 
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

function mergeObjectsRecursively($obj1, $obj2) {
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

if (! function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            // if (substr($name, 0, 5) == 'HTTP_') {
            if (str_starts_with((string) $name, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function numberFormatIndo($num, $decimal = 0)
{
    return number_format($num, $decimal, ",", ".");
}

function format_with_rounding($number, $precision = 0, $mode = PHP_ROUND_HALF_UP, $decimal_separator = ',', $thousands_separator = '.') {
    // Truncate the number to the desired number of decimal places without rounding
    $truncated_number = round($number, $precision, $mode);

    // Format the truncated number using number_format for display
    return number_format($truncated_number, $decimals, $decimal_separator, $thousands_separator);
}

function format_without_rounding($number, $decimals = 2, $decimal_separator = ',', $thousands_separator = '.') {
    // Truncate the number to the desired number of decimal places without rounding
    $truncated_number = bcdiv($number, 1, $decimals);

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

function formatMapAddress($addressMap, $sort = false) {
    $pattern = '/[a-z0-9]+\+[a-z0-9]+/i';
    $address = explode("||", (string) $addressMap);
    // $output = $addressMap;

    // Check if the format is found within the input string
    if (isset($address[1]) && preg_match($pattern, $address[1], $matches)) {
        if(isset($matches[0])) 
            $output = trim(str_replace($matches[0], "", $address[1]), ",");
        else
            $output = $address[1];

        if($sort)
        return trim(explode(",", $output)[0]);

        return $output;
    }

    if (isset($address[1])) {
        if($sort)
        return trim(explode(",", $address[0])[0]);

        return $address[1];
    }

    return $addressMap;
}

function setArrivedTime($dateTimeStr, $minutesToAdd = 30, $formated = 'H:i A') {
    $dateTime = new DateTime($dateTimeStr); 
    
    $dateTime->modify("+{$minutesToAdd} minutes");
    
    return $dateTime->format($formated);
}

function convertMinutesToHoursAndMinutes($totalMinutes, $simpleText = true) {
    if (!is_numeric($totalMinutes) || $totalMinutes < 0) {
        // return "Invalid input. Please provide a non-negative number of minutes.";
        return sprintf("%d Min", 0);
    }

    $hours = floor($totalMinutes / 60); // Get the whole number of hours
    $minutes = $totalMinutes % 60;    // Get the remaining minutes

    if(!$simpleText) {
        $plurals = $hours > 1 ? "Hours" : "Hour";
        if($hours > 0)
            return sprintf("%d %d and %d Minutes", $hours, $plurals, $minutes);
        
        return sprintf("%d Minutes", $minutes);
    }

    if($hours > 0)
        return sprintf("%d H, %d Min", $hours, $minutes);
    
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
function camelCaseToUnderscore($inputString) {
    // Add an underscore before each uppercase letter, unless it's at the beginning of the string
    $snakeCaseString = preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $inputString);
    // Convert the entire string to lowercase
    $snakeCaseString = strtolower($snakeCaseString);
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
function underscoreToCamelCase($inputString, $prefix = '') {
    if($prefix !== '')
        $inputString = $prefx . '_' . $inputString;

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

function str_replace_multi(array $replace, string $subject) {
    return str_replace(array_keys($replace), array_values($replace), $subject); 
}

function is_float_string($value) {
    // return is_numeric($value) && strpos($value, '.') !== false;
    return is_numeric($value) && str_contains($value, '.');
}

function is_decimal($n) {
    // Note that floor returns a float 
    return is_numeric($n) && floor($n) != $n;
}

/**
 * Divide an array into two arrays. One with keys and the other with values.
 *
 * @param  array  $array
 * @return array
 */
function divideArray($array) {
    return [array_keys($array), array_values($array)];
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param  iterable  $array
 * @param  string  $prepend
 * @return array
 */
function dotArray($array, $prepend = '') {
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
        $item = $item instanceof Collection ? $item->all() : $item;

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

        if($mode === 'ltrim')
            return preg_replace('~^[\s\x{FEFF}\x{200B}\x{200E}'.$ltrimDefaultCharacters.']+~u', '', $value) ?? ltrim($value);
        elseif($mode === 'rtrim')
            return preg_replace('~[\s\x{FEFF}\x{200B}\x{200E}'.$rtrimDefaultCharacters.']+$~u', '', $value) ?? rtrim($value);
        else
            return preg_replace('~^[\s\x{FEFF}\x{200B}\x{200E}'.$trimDefaultCharacters.']+|[\s\x{FEFF}\x{200B}\x{200E}'.$trimDefaultCharacters.']+$~u', '', $value) ?? trim($value);
    }

    if($mode === 'ltrim')
        return ltrim($value, $charlist);
    elseif($mode === 'rtrim')
        return rtrim($value, $charlist);
    else
        return trim($value, $charlist);
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

function bp_session_start()
{
    session_start();
    if (isset($_SESSION['destroyed'])) {
        if ($_SESSION['destroyed'] < time() - 300) {
            // // Should not happen usually. This could be attack or due to unstable network.
            // // Remove all authentication status of this users session.
            // remove_all_authentication_flag_from_active_sessions($_SESSION['userid']);
            // throw(new DestroyedSessionAccessException);
        }
        if (isset($_SESSION['new_session_id'])) {
            // Not fully expired yet. Could be lost cookie by unstable network.
            // Try again to set proper session ID cookie.
            // NOTE: Do not try to set session ID again if you would like to remove
            // authentication flag.
            session_commit();
            session_id($_SESSION['new_session_id']);

            // use_strict_mode is mandatory for security reasons.
            // ini_set('session.use_strict_mode', 1);
            // New session ID should exist
            session_start();
            return;
        }
    }

    $sessionStrictMode = ini_get('session.use_strict_mode');
    // \App\Core\Support\Log::debug($sessionStrictMode, 'Helpers.inject_php.bp_session_start.$sessionStrictMode');
}

function bp_session_regenerate_id($oldSessionId)
{
    $new_session_id = session_create_id();

    // backup session variables
    $keepSession = $_SESSION ;

    // add info for users with bad connection not receiving the new session id
    $_SESSION['new_session_id'] = $new_session_id;
    // Set destroy timestamp
    $_SESSION['destroyed'] = time();

    // Write and close current session;
    session_commit() ;

    // Start session with new session ID
    ini_set('session.use_strict_mode', 0);
    session_id($new_session_id);

    $sessionStrictMode = ini_get('session.use_strict_mode');
    // \App\Core\Support\Log::debug($sessionStrictMode, 'Helpers.inject_php.bp_session_regenerate_id.before-$sessionStrictMode');
    $sessionName = session_name();
    $cookie = session_get_cookie_params();
    $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
    $setcookie = ['Set-Cookie' => "{$sessionName}={$new_session_id}; Max-Age={$sessionExp}; Path={$cookie['path']};"];

    // use_strict_mode is mandatory for security reasons.
    ini_set('session.use_strict_mode', 1);

    session_start();
    $_SESSION = $keepSession ;

    // Delete Old session file
    $sessionSavePath = session_save_path();
    $fileSessionOld = $sessionSavePath.'/sess_'.$oldSessionId;
    $sessionStrictMode = ini_get('session.use_strict_mode');
    // \App\Core\Support\Log::debug($sessionSavePath, 'Helpers.inject_php.bp_session_regenerate_id.$sessionSavePath');
    // \App\Core\Support\Log::debug($fileSessionOld, 'Helpers.inject_php.bp_session_regenerate_id.$fileSessionOld');
    // \App\Core\Support\Log::debug($sessionStrictMode, 'Helpers.inject_php.bp_session_regenerate_id.$sessionStrictMode');
    if (\file_exists($fileSessionOld)) {
        $status = unlink($fileSessionOld);
        // \App\Core\Support\Log::debug($status, 'Helpers.inject_php.bp_session_regenerate_id.unlink-$fileSessionOld');
    }

    return $setcookie;
}

// function regenerateSession($reload = false)
// {
//     // This token is used by forms to prevent cross site forgery attempts
//     if (!isset($_SESSION['nonce']) || $reload) {
//         $_SESSION['nonce'] = bin2hex(openssl_random_pseudo_bytes(32));
//     }

//     if (!isset($_SESSION['IPaddress']) || $reload) {
//         $_SESSION['IPaddress'] = clientIP();
//     }

//     if (!isset($_SESSION['userAgent']) || $reload) {
//         $_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
//     }

//     //$_SESSION['user_id'] = $this->user->getId();

//     // Set current session to expire in 1 minute
//     $_SESSION['OBSOLETE'] = true;
//     $_SESSION['EXPIRES'] = time() + 60;

//     // Create new session without destroying the old one
//     session_regenerate_id(false);

//     // Grab current session ID and close both sessions to allow other scripts to use them
//     $newSession = session_id();
//     session_write_close();

//     // Set session ID to the new one, and start it back up again
//     session_id($newSession);
//     session_start();

//     // Don't want this one to expire
//     unset($_SESSION['OBSOLETE']);
//     unset($_SESSION['EXPIRES']);
// }

// function checkSession()
// {
//     try {
//         if ($_SESSION['OBSOLETE'] && ($_SESSION['EXPIRES'] < time())) {
//             throw new Exception('Attempt to use expired session.');
//         }

//         // if(!is_numeric($_SESSION['user_id']))
//         //     throw new Exception('No session started.');

//         if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR']) {
//             throw new Exception('IP Address mixmatch (possible session hijacking attempt).');
//         }

//         if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
//             throw new Exception('Useragent mixmatch (possible session hijacking attempt).');
//         }

//         // if(!$this->loadUser($_SESSION['user_id']))
//         //     throw new Exception('Attempted to log in user that does not exist with ID: ' . $_SESSION['user_id']);

//         if (!$_SESSION['OBSOLETE'] && mt_rand(1, 100) == 1) {
//             $this->regenerateSession();
//         }

//         return true;

//     } catch (Exception $e) {
//         return false;
//     }
// }

// // Backend PHP custom session start function support timestamp management
// function custom_session_start()
// {
//     session_start();
//     // Do not allow to use too old session ID
//     if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - 180) {
//         session_destroy();
//         session_start();
//     }
// }

// // Backend PHP custom session regenerate id function
// function custom_session_regenerate_id($prefix = 'bp-')
// {
//     // Call session_create_id() while session is active to
//     // make sure collision free.
//     if (session_status() != PHP_SESSION_ACTIVE) {
//         session_start();
//     }
//     // WARNING: Never use confidential strings for prefix!
//     $newid = session_create_id($prefix);
//     // Set deleted timestamp. Session data must not be deleted immediately for reasons.
//     $_SESSION['deleted_time'] = time();
//     // Finish session
//     session_commit();
//     // Make sure to accept user defined session ID
//     // NOTE: You must enable use_strict_mode for normal operations.
//     ini_set('session.use_strict_mode', 0);
//     // Set new custom session ID
//     session_id($newid);

//     $sessionName = session_name();
//     $cookie = session_get_cookie_params();
//     setcookie(
//         $sessionName,
//         $newid,
//         (env('SESSION_LIFETIME', 120) * 60),
//         $cookie['path'],
//         $cookie['domain'],
//         $cookie['secure'],
//         $cookie['httponly']
//     );

//     // use_strict_mode is mandatory for security reasons.
//     ini_set('session.use_strict_mode', 1);
//     // Start with custom session ID
//     session_start();

//     return $newid;
// }
