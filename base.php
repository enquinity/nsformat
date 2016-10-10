<?php
namespace Nsformat;

require_once __DIR__ . '/abstract.php';

class BaseFormatter extends AbstractFormatter {

    public function format($format/*, $args...*/) {
        $args = func_get_args();
        array_shift($args);
        return $this->formatData($format, $args);
    }

    /**
     * @param mixed $format Format komunikatu w formie stringa lub w postaci skompilowanej
     * @param array $args
     * @return mixed|string
     */
    public function formatArr($format, array $args = []) {
        return $this->formatData($format, $args);
    }

    protected function parseIndex($index, &$value, &$argIndex, &$argSubIndex) {
        if ($index === null || $index === '') {
            $value = '';
            return 1;
        }
        if ('@' === $index[0]) {
            if ($index[1] >= '0' && $index[1] <= '9') {
                $argIndex = (int)substr($index, 1);
            } elseif ('.' == $index[1]) {
                $argIndex = 0;
                $argSubIndex = substr($index, 2);
            } else {
                $argIndex = 0;
                $argSubIndex = substr($index, 1);
            }
            return 2;
        }
        if ('=' === $index[0]) {
            $value = substr($index, 1);
            return 1;
        }
        if ($index[0] >= '0' && $index[0] <= '9') {
            $p = strpos($index, '.');
            if (false === $p) {
                $argIndex = (int)$index;
            } else {
                $argIndex = (int)substr($index, 0, $p);
                $argSubIndex = substr($index, $p + 1);
            }
            return 2;
        }
        if ('.' === $index[0]) {
            $argIndex = 0;
            $argSubIndex = substr($index, 1);
            return 2;
        }
        $value = $index;
        return 1;
    }

    protected function getDataValue($index, $data, $compiledIndex = null) {
        if ($compiledIndex !== null) {
            if (is_array($compiledIndex)) {
                return $data[$compiledIndex[0]][$compiledIndex[1]];
            } else {
                return $data[$compiledIndex];
            }
        }
        $ixVal = null;
        $ixArgIndex = null;
        $ixArgSubIndex = null;
        $this->parseIndex($index, $ixVal, $ixArgIndex, $ixArgSubIndex);
        if ($ixArgIndex !== null) {
            $value = $data[$ixArgIndex];
            if ($ixArgSubIndex !== null) $value = $value[$ixArgSubIndex];
            return $value;
        }
        return $ixVal;
    }

    protected function getInlineValueFromIndex($index) {
        if (empty($index)) return null;
        if ('=' === $index[0]) return substr($index, 1);
        if ('@' === $index[0] || '.' === $index[0] || ($index[0] >= '0' && $index[0] <= '9')) return null;
        return $index;
    }

    protected function compileIndex($index) {
        $ixVal = null;
        $ixArgIndex = null;
        $ixArgSubIndex = null;
        $this->parseIndex($index, $ixVal, $ixArgIndex, $ixArgSubIndex);
        if ($ixArgIndex !== null) {
            if ($ixArgSubIndex !== null) {
                return [$ixArgIndex, $ixArgSubIndex];
            } else {
                return $ixArgIndex;
            }
        }
        return null;
    }
}