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
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
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
    $address = explode("||", $addressMap);
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
    $str = preg_replace('/\x00|<[^>]*>?/', '', $value);
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
    $snakeCaseString = preg_replace('/(?<!^)[A-Z]/', '_$0', $inputString);
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
    return is_numeric($value) && strpos($value, '.') !== false;
}

function is_decimal($n) {
    // Note that floor returns a float 
    return is_numeric($n) && floor($n) != $n;
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
