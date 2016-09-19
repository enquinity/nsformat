<?php
namespace Nsformat;

require_once __DIR__ . '/base.php';

abstract class SqlFormatter extends BaseFormatter {

    protected abstract function quoteString($string);
    protected abstract function quoteIdentifier($identifier);
    protected abstract function quoteColumnAlias($alias);

    protected function formatValue($value, $formatter, $precision, $formatterArgs) {
        if (empty($formatter)) $formatter = 'n';
        switch ($formatter) {
            case 'n':
                $as = '';
                $p = strpos($value, ' AS ');
                if (false !== $p) {
                    $as = ' AS ' . $this->quoteColumnAlias(trim(substr($value, $p + 4)));
                    $value = substr($value, 0, $p);
                }
                $fmt = '';
                $p = strpos($value, '.');
                if (false !== $p) {
                    $fmt = $this->quoteIdentifier(trim(substr($value, 0, $p))) . '.';
                    $value = substr($value, $p + 1);
                }
                $p = strpos($value, ' ');
                if (false !== $p) {
                    $fmt .= $this->quoteIdentifier(trim(substr($value, 0, $p))) . ' ' . $this->quoteIdentifier(trim(substr($value, $p + 1)));
                } else {
                    $fmt .= $this->quoteIdentifier($value);
                }
                $fmt .= $as;
                return $fmt;
            case 'a': // FALL THROUGH
            case 'ca':
                return $this->quoteColumnAlias($value);
            case 'd':
                if (null === $value) return 'NULL';
                return (int)$value;
            case 'f':
                if (null === $value) return 'NULL';
                if (null !== $precision) return number_format($value, $precision);
                return (float)$value;
            case 's':
                if (null === $value) return 'NULL';
                return $this->quoteString($value);
            case 'ld':
                if (empty($value)) return 'NULL';
                $formatted = '';
                foreach ($value as $elem) {
                    if ($formatted !== '') $formatted .= ',';
                    $formatted .= (int)$elem;
                }
                return $formatted;
            case 'lf':
                if (empty($value)) return 'NULL';
                $formatted = '';
                foreach ($value as $elem) {
                    if ($formatted !== '') $formatted .= ',';
                    $formatted .= (float)$elem;
                }
                return $formatted;
            case 'ls':
                if (empty($value)) return 'NULL';
                $formatted = '';
                foreach ($value as $elem) {
                    if ($formatted !== '') $formatted .= ',';
                    $formatted .= $this->quoteString($elem);
                }
                return $formatted;
            default:
                throw new \Exception("Sql format unknown formatter $formatter");
        }
    }
}