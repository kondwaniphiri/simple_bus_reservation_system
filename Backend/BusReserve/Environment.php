<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
class Environment {
    private static $values = null;
    public static function readFile($path) {
        if (!is_file($path)) return [];
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) as $index => $line) {
            if ($index === 0) $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $match)) throw new RuntimeException('Invalid environment setting on line '.($index + 1));
            $value = trim($match[2]);
            if ($value !== '' && $value[0] === '"') {
                $decoded = json_decode($value, true);
                if (!is_string($decoded)) throw new RuntimeException('Invalid quoted environment value on line '.($index + 1));
                $value = $decoded;
            } elseif ($value !== '' && $value[0] === "'") {
                if (substr($value, -1) !== "'") throw new RuntimeException('Unclosed environment value on line '.($index + 1));
                $value = substr($value, 1, -1);
            }
            $values[$match[1]] = $value;
        }
        return $values;
    }
    public static function value($key, $default = '') {
        $system = getenv($key);
        if ($system !== false) return $system;
        if (self::$values === null) self::$values = self::readFile(dirname(__DIR__, 2).'/.env');
        return self::$values[$key] ?? $default;
    }
    public static function integer($key, $default, $minimum, $maximum) {
        $value = filter_var(self::value($key, (string)$default), FILTER_VALIDATE_INT);
        if ($value === false || $value < $minimum || $value > $maximum) throw new RuntimeException('Invalid numeric environment setting: '.$key);
        return $value;
    }
}
