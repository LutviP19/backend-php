<?php

/**
 * Inject php native functions.
 *
 */

function array_keys_exists(array $keys, array $array): bool
{
    $diff = array_diff_key(array_flip($keys), $array);
    return count($diff) === 0;
}
