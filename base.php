<?php
namespace Nsformat;

class CompiledTag {
    public $argIndex;
    public $argSubIndex;
    public $width;
    public $precision;
    public $format;
    public $formatArgs;
    public $emptyValue;
}

class CompiledFormat {
    /**
     * Skompilowany string zgodny z formatem sprintf.
     * @var string
     */
    public $string;

    /**
     * @var CompiledTag[]
     */
    public $tags = [];
}

class BaseFormatter {
    protected $rex = '/(?<!\{)\{(?!\{)(.+?)(?::~(.+?))?(?::(-?\d+)?(?:.(\d+))?([^\s}]+)(?:\s+(.+?))?)?(?<!\})\}(?!\})/';
    protected $rexIdx = 1;
    protected $rexEmptyVal = 2;
    protected $rexWidth = 3;
    protected $rexPrecision = 4;
    protected $rexFormat = 5;
    protected $rexFormatArgs = 6;

    public function format($format/*, $args...*/) {
        $args = func_get_args();
        array_shift($args);
        return $this->formatArr($format, $args);
    }

    /**
     * @param mixed $format Format komunikatu w formie stringa lub w postaci skompilowanej
     * @param array $args
     * @return mixed|string
     */
    public function formatArr($format, array $args = []) {
        if ($format instanceof CompiledFormat) {
            $formatedArgs = [];
            foreach ($format->tags as $tag) {
                $value = $args[$tag->argIndex];
                if (null !== $tag->argSubIndex) $value = $value[$tag->argSubIndex];

                if (null !== $tag->emptyValue && empty($value)) {
                    $value = $tag->emptyValue;
                } else {
                    $value = $this->formatValue($value, $tag->format, $tag->precision, $tag->formatArgs);
                }
                $formatedArgs[] = $value;
            }
            return vsprintf($format->string, $formatedArgs);
        }

        $formatted = preg_replace_callback($this->rex, function ($m) use ($args) {
            $value = null;
            $argIdx = null;
            $argSubIdx = null;
            $this->parseIndex($m[$this->rexIdx], $value, $argIdx, $argSubIdx);
            if ($argIdx !== null) $value = $args[$argIdx];
            if ($argSubIdx !== null) $value = $value[$argSubIdx];

            if (empty($value) && isset($m[$this->rexEmptyVal]) && $m[$this->rexEmptyVal] != '') {
                $value = $m[$this->rexEmptyVal];
            } else {
                $precision = isset($m[$this->rexPrecision]) && $m[$this->rexPrecision] != '' ? $m[$this->rexPrecision] : null;
                $formatter = isset($m[$this->rexFormat]) && $m[$this->rexFormat] != '' ? $m[$this->rexFormat] : null;
                $formatArgs = null;
                if (isset($m[$this->rexFormatArgs]) && $m[$this->rexFormatArgs] != '') {
                    $formatArgs = array_map('trim', explode(';', $m[$this->rexFormatArgs]));
                }
                $value = $this->formatValue($value, $formatter, $precision, $formatArgs);
            }
            if (isset($m[$this->rexWidth]) && $m[$this->rexWidth] != '') {
                if ('-' === $m[$this->rexWidth][0]) {
                    $value = str_pad($value, substr($m[$this->rexWidth], 1), ' ', STR_PAD_RIGHT);
                } else {
                    $value = str_pad($value, $m[$this->rexWidth], ' ', STR_PAD_LEFT);
                }
            }
            return $value;
        }, $format);
        $formatted = str_replace(['}}', '{{'], ['}', '{'], $formatted);

        return $formatted;
    }

    public function compile($format) {
        $compiled = new CompiledFormat();
        $compiled->string = preg_replace_callback($this->rex, function ($m) use ($compiled) {
            $idxVal = null;
            $argIdx = null;
            $argSubIdx = null;
            $this->parseIndex($m[$this->rexIdx], $idxVal, $argIdx, $argSubIdx);

            $emptyVal = isset($m[$this->rexEmptyVal]) && $m[$this->rexEmptyVal] != '' ? $m[$this->rexEmptyVal] : '';
            $width = isset($m[$this->rexWidth]) && $m[$this->rexWidth] != '' ? $m[$this->rexWidth] : '';
            $precision = isset($m[$this->rexPrecision]) && $m[$this->rexPrecision] != '' ? $m[$this->rexPrecision] : null;
            $formatter = isset($m[$this->rexFormat]) && $m[$this->rexFormat] != '' ? $m[$this->rexFormat] : null;
            $formatArgs = null;
            if (isset($m[$this->rexFormatArgs]) && $m[$this->rexFormatArgs] != '') {
                $formatArgs = array_map('trim', explode(';', $m[$this->rexFormatArgs]));
            }

            if ($idxVal !== null && null === $argIdx) {
                // wartość inline od razu wstawiamy
                $value = $this->formatValue($idxVal, $formatter, $precision, $formatArgs);
                if ($width !== null) {
                    if ('-' === $width[0]) {
                        $value = str_pad($value, substr($width, 1), ' ', STR_PAD_RIGHT);
                    } else {
                        $value = str_pad($value, $width, ' ', STR_PAD_LEFT);
                    }
                }
                return $value;
            } else {
                $tag = new CompiledTag();
                $compiled->tags[] = $tag;
                $tag->argIndex = $argIdx;
                $tag->argSubIndex = $argSubIdx;
                $tag->emptyValue = str_replace(['}}', '{{'], ['}', '{'], $emptyVal);
                $tag->precision = $precision;
                $tag->format = $formatter;
                $tag->formatArgs = $formatArgs;
                $tag->width = $width;
                return chr(17) . $width . 's';
            }
        }, $format);
        $compiled->string = str_replace(chr(17), '%', str_replace('%', '%%', $compiled->string));
        $compiled->string = str_replace(['}}', '{{'], ['}', '{'], $compiled->string);
        return $compiled;
    }

    protected function formatValue($value, $formatter, $precision, $formatterArgs) {
        return $value;
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
}