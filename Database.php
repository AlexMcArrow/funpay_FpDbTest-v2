<?php

namespace FpDbTest;

use Exception;
use mysqli;

/**
 * @warning Only for MySQL\MariaDB
 */
class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    /**
     * Specify string flag for second stage parsing
     * @var string
     */
    const SKIP_FLAG = '##DOSKIP##';

    /**
     * Avalible placeholder flags
     * @var array
     */
    const AVALIBLE_TYPES = ['#', 'a', 'f', 'd'];

    var bool $DEBUG = false;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function _set_debug()
    {
        $this->DEBUG = true;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        // Inumerator counter for crawling in args
        $count = -1;
        // If DEBUG check SQL
        preg_replace_callback('/(\?([\#|\w]?))/', function ($match_data) use ($query) {
            if ($this->DEBUG && strlen($match_data[2]) > 0 && !in_array($match_data[2], Database::AVALIBLE_TYPES)) {
                throw new Exception('SQL [' . $query . '] contain not avalible placeholder [' . $match_data[2] . ']');
            }
        }, $query);
        // First stage parsing for variables (# d f a)
        $query = preg_replace_callback('/(\?([\#|\w]?))/', function ($match_data) use ($args, &$count) {
            $count++;
            return $this->_args_typing($match_data[2], $args[$count]);
        }, $query);
        // Second stage parsing
        $query = preg_replace_callback('/(\{(.*)\})/', function ($match_data) {
            // If in match found SKIP_FLAG, then cut - else return inner content
            if (strpos($match_data[0], Database::SKIP_FLAG) !== false) {
                return '';
            }
            return $match_data[2];
        }, $query);

        return $query;
    }

    /**
     * Args normaliazing to format
     */
    private function _args_typing(string $format, mixed $arg): mixed
    {
        switch ($format) {
            case '#':
                if (is_array($arg)) {
                    return "`" . implode("`, `", $arg) . "`";
                } else {
                    return "`" . strval($arg) . "`";
                }
                break;
            case 'a':
                $ret = [];
                if ($this->array_is_list($arg)) {
                    foreach ($arg as $key => $value) {
                        $ret[] = $this->_args_normolize_value($value);
                    }
                } else {
                    foreach ($arg as $key => $value) {
                        $ret[] = "`" . $key . "` = " . $this->_args_normolize_value($value);
                    }
                }
                return implode(", ", $ret);
                break;
            case 'f':
                return $this->_args_normolize_value($arg);
                break;
            case 'd':
            default:
                return $this->_args_normolize_value($arg);
                break;
        }
    }

    /**
     * Normalizing value by value-type
     */
    private function _args_normolize_value(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->_args_typing('a', $value);
        } elseif (is_bool(($value))) {
            return intval($value);
        } elseif (is_double(($value))) {
            return floatval($value);
        } elseif (is_numeric(($value))) {
            return intval($value);
        } elseif (is_null($value)) {
            return 'NULL';
        } else {
            return "'" . strval($value) . "'";
        }
    }

    /**
     * Check is array is numeric(true) or associated(false)
     */
    private function array_is_list(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    public function skip()
    {
        return Database::SKIP_FLAG;
    }
}
