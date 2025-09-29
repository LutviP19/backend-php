<?php

namespace App\Core\Validation;

class Filter
{
    /**
     * Rules delimiter.
     *
     * @var string
     */
    public static $rules_delimiter = '|';

    /**
     * Rules-parameters delimiter.
     *
     * @var string
     */
    public static $rules_parameters_delimiter = ',';

     /**
     * Rules parameters array delimiter.
     *
     * @var string
     */
    public static $rules_parameters_arrays_delimiter = ';';

    /**
     * Customer filters.
     *
     * @var array
     */
    protected static $filter_methods = [];


    /**
     * Sanitize the input data.
     *
     * @param array $input
     * @param array $fields
     * @param bool $utf8_encode
     *
     * @return array
     */
    public function sanitize(array $input, array $fields = [], bool $utf8_encode = true)
    {
        if (empty($fields)) {
            $fields = array_keys($input);
        }

        $return = [];

        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                continue;
            }

            $value = $input[$field];
            if (is_array($value)) {
                $value = $this->sanitize($value, [], $utf8_encode);
            }
            if (is_string($value)) {
                if (strpos($value, "\r") !== false) {
                    $value = trim($value);
                }

                if (function_exists('iconv') && function_exists('mb_detect_encoding') && $utf8_encode) {
                    $current_encoding = mb_detect_encoding($value);

                    if ($current_encoding !== 'UTF-8' && $current_encoding !== 'UTF-16') {
                        $value = iconv($current_encoding, 'UTF-8', $value);
                    }
                }

                $value = polyfill_filter_var_string($value);
            }

            $return[$field] = $value;
        }

        return $return;
    }

    /**
     * Filter the input data according to the specified filter set.
     *
     * @param mixed  $input
     * @param array  $filterset
     * @return mixed
     * @throws Exception
     */
    public function filter(array $input, array $filterset)
    {
        foreach ($filterset as $field => $filters) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $filters = $this->parse_rules($filters);

            foreach ($filters as $filter) {
                $parsed_rule = $this->parse_rule($filter);

                if (is_array($input[$field])) {
                    $input_array = &$input[$field];
                } else {
                    $input_array = array(&$input[$field]);
                }

                foreach ($input_array as &$value) {
                    $value = $this->call_filter($parsed_rule['rule'], $value, $parsed_rule['param']);
                }

                unset($input_array, $value);
            }
        }

        return $input;
    }

    /**
     * Helper to convert filter rule name to filter rule method name.
     *
     * @param string $rule
     * @return string
     */
    private static function filter_to_method(string $rule)
    {
        return sprintf('filter_%s', $rule);
    }

    /**
     * Calls a filter.
     *
     * @param string $rule
     * @param mixed $value
     * @param array $rule_params
     * @return mixed
     * @throws Exception
     */
    private function call_filter(string $rule, $value, array $rule_params = [])
    {
        $method = self::filter_to_method($rule);

        // use native filters
        if (is_callable(array($this, $method))) {
            return $this->$method($value, $rule_params);
        }

        // use custom filters
        if (isset(self::$filter_methods[$rule])) {
            return call_user_func(self::$filter_methods[$rule], $value, $rule_params);
        }

        // use php functions as filters
        if (function_exists($rule)) {
            return call_user_func($rule, $value, ...$rule_params);
        }

        throw new Exception(sprintf("'%s' filter does not exist.", $rule));
    }

    /**
     * Parses filters and validators rules group.
     *
     * @param string|array $rules
     * @return array
     */
    private function parse_rules($rules)
    {
        // v2
        if (is_array($rules)) {
            $rules_names = [];
            foreach ($rules as $key => $value) {
                $rules_names[] = is_numeric($key) ? $value : $key;
            }

            return array_map(function ($value, $key) use ($rules) {
                if ($value === $key) {
                    return [ $key ];
                }

                return [$key, $value];
            }, $rules, $rules_names);
        }

        return explode(self::$rules_delimiter, $rules);
    }

    /**
     * Parses filters and validators individual rules.
     *
     * @param string|array $rule
     * @return array
     */
    private function parse_rule($rule)
    {
        // v2
        if (is_array($rule)) {
            return [
                'rule' => $rule[0],
                'param' => $this->parse_rule_params($rule[1] ?? [])
            ];
        }

        $result = [
            'rule' => $rule,
            'param' => []
        ];

        if (strpos($rule, self::$rules_parameters_delimiter) !== false) {
            list($rule, $param) = explode(self::$rules_parameters_delimiter, $rule);

            $result['rule'] = $rule;
            $result['param'] = $this->parse_rule_params($param);
        }

        return $result;
    }

    /**
     * Parse rule parameters.
     *
     * @param string|array $param
     * @return array|string|null
     */
    private function parse_rule_params($param)
    {
        if (is_array($param)) {
            return $param;
        }

        if (strpos($param, self::$rules_parameters_arrays_delimiter) !== false) {
            return explode(self::$rules_parameters_arrays_delimiter, $param);
        }

        return [ $param ];
    }

    // ** ------------------------- Custom Filters --------------------------------------- ** //

    /**
     * Remove all known punctuation from a string.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_rmpunctuation($value, array $params = [])
    {
        return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
    }

    /**
     * Sanitize the string by urlencoding characters.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_urlencode($value, array $params = [])
    {
        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_htmlencode($value, array $params = [])
    {
        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Sanitize the string by removing illegal characters from emails.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_sanitize_email($value, array $params = [])
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize the string by removing illegal characters from numbers.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_sanitize_numbers($value, array $params = [])
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize the string by removing illegal characters from float numbers.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_sanitize_floats($value, array $params = [])
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize the string by removing any script tags.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_sanitize_string($value, array $params = [])
    {
        return self::polyfill_filter_var_string($value);
    }

    /**
     * Implemented to replace FILTER_SANITIZE_STRING behaviour deprecated in php8.1
     *
     * @param mixed $value
     * @return string
     */
    public static function polyfill_filter_var_string($value)
    {
        $str = preg_replace('/\x00|<[^>]*>?/', '', $value);
        return (string) str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
    }

    /**
     * Converts ['1', 1, 'true', true, 'yes', 'on'] to true, anything else is false ('on' is useful for form checkboxes).
     *
     * @param mixed $value
     * @param array $params
     *
     * @return bool
     */
    public function filter_boolean($value, array $params = [])
    {
        if (in_array($value, self::$trues, true)) {
            return true;
        }

        return false;
    }

    /**
     * Filter out all HTML tags except the defined basic tags.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_basic_tags($value, array $params = [])
    {
        return strip_tags($value, self::$basic_tags);
    }

    /**
     * Convert the provided numeric value to a whole number.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_whole_number($value, array $params = [])
    {
        return intval($value);
    }

    /**
     * Convert MS Word special characters to web safe characters. ([“ ”] => ", [‘ ’] => ', [–] => -, […] => ...)
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_ms_word_characters($value, array $params = [])
    {
        return str_replace(['“', '”', '‘', '’', '–', '…'], ['"', '"', "'", "'", '-', '...'], $value);
    }

    /**
     * Converts to lowercase.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_lower_case($value, array $params = [])
    {
        return mb_strtolower($value);
    }

    /**
     * Converts to uppercase.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_upper_case($value, array $params = [])
    {
        return mb_strtoupper($value);
    }

    /**
     * Converts value to url-web-slugs.
     *
     * @see https://stackoverflow.com/questions/40641973/php-to-convert-string-to-slug
     * @see http://cubiq.org/the-perfect-php-clean-url-generator
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_slug($value, array $params = [])
    {
        $delimiter = '-';
        return mb_strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $value))))), $delimiter));
    }

    /**
     * Remove spaces from the beginning and end of strings.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    public function filter_trim($value, array $params = [])
    {
        return trim($value);
    }

}
