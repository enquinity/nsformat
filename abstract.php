<?php
namespace Nsformat;

class CompiledTag {
    public $index;
    public $compiledIndex;
    public $width;
    public $maxWidth = 0;
    public $align; // STR_PAD_LEFT, STR_PAD_RIGHT lub STR_PAD_BOTH
    public $padChar = ' ';
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

abstract class AbstractFormatter {
    //protected $rex = '/(?<!\{)\{(?!\{)(.+?)(?::~(.+?))?(?::([-+*]?\d+|[-+*]\D\d+)?(?:-(\d+))?(?:.(\d+))?([^\s}]+)(?:\s+(.+?))?)?(?<!\})\}(?!\})/';
    protected $rex = '/\{(.+?)(?::~(.+?))?(?::([-+*]?\d+|[-+*]\D\d+)?(?:-(\d+))?(?:.(\d+))?([^\s}]+)(?:\s+(.+?))?)?\}/';
    protected $rexIdx = 1;
    protected $rexEmptyVal = 2;
    protected $rexWidth = 3;
    protected $rexMaxWidth = 4;
    protected $rexPrecision = 5;
    protected $rexFormat = 6;
    protected $rexFormatArgs = 7;

    /**
     * @param mixed $format Format komunikatu w formie stringa lub w postaci skompilowanej
     * @param mixed $data Dane (parametry formatowania)
     * @return mixed|string
     */
    public function formatData($format, $data) {
        if ($format instanceof CompiledFormat) {
            $formattedArgs = [];
            foreach ($format->tags as $tag) {
                $value = $this->getDataValue($tag->index, $data, $tag->compiledIndex);
                if (null !== $tag->emptyValue && empty($value)) {
                    $value = $tag->emptyValue;
                } else {
                    $value = $this->formatValue($value, $tag->format, $tag->precision, $tag->formatArgs);
                }
                if ($tag->width > 0 || $tag->maxWidth > 0) {
                    $vl = strlen($value);
                    if ($tag->width > 0 && $vl < $tag->width) {
                        $value = str_pad($value, $tag->width, null !== $tag->padChar ? $tag->padChar : ' ', $tag->align);
                    } elseif ($tag->maxWidth > 0 && $vl > $tag->maxWidth) {
                        $value = substr($value, 0, $tag->maxWidth);
                    }
                }
                $formattedArgs[] = $value;
            }
            return vsprintf($format->string, $formattedArgs);
        }

        $format = str_replace(['{{', '}}'], [chr(17), chr(18)], $format);
        $formatted = preg_replace_callback($this->rex, function ($m) use ($data) {
            $index = $m[$this->rexIdx];
            $value = $this->getDataValue($index, $data);

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
            if (isset($m[$this->rexWidth]) && $m[$this->rexWidth] != '' && $m[$this->rexWidth] !== '0') {
                $padChar = null;
                $padAlign = null;
                $padWidth = null;
                $this->parseWidth($m[$this->rexWidth], $padWidth, $padChar, $padAlign);
                if ($padWidth > 0) {
                    $value = str_pad($value, $padWidth, $padChar, $padAlign);
                }
            }
            if (isset($m[$this->rexMaxWidth]) && $m[$this->rexMaxWidth] != '') {
                if (strlen($value) > $m[$this->rexMaxWidth]) {
                    $value = substr($value, 0, $m[$this->rexMaxWidth]);
                }
            }
            return $value;
        }, $format);
        $formatted = str_replace([chr(17), chr(18)], ['{', '}'], $formatted);

        return $formatted;
    }

    protected function parseWidth($widthStr, &$width, &$padChar, &$padAlign) {
        $padChar = ' ';
        $padAlign = STR_PAD_LEFT;
        $width = $widthStr;

        if ('-' === $width[0] || '+' === $width[0] || '*' === $width[0]) {
            $padAlign = ('-' === $width[0]) ? STR_PAD_RIGHT : ('+' === $width[0] ? STR_PAD_LEFT : STR_PAD_BOTH);
            if (isset($width[2]) && ($width[1] <= '0' || $width[1] > '9')) {
                $padChar = $width[1];
                $width = substr($width, 2);
            } else {
                $width = substr($width, 1);
            }
        } elseif ('0' === $width[0] && isset($width[1])) {
            $padChar = '0';
            $width = substr($width, 1);
        }
    }

    public function compile($format) {
        $compiled = new CompiledFormat();
        $format = str_replace(['{{', '}}'], [chr(17), chr(18)], $format);
        $compiled->string = preg_replace_callback($this->rex, function ($m) use ($compiled) {
            $index = $m[$this->rexIdx];

            $emptyVal = isset($m[$this->rexEmptyVal]) && $m[$this->rexEmptyVal] != '' ? $m[$this->rexEmptyVal] : '';
            $width = isset($m[$this->rexWidth]) && $m[$this->rexWidth] != '' ? $m[$this->rexWidth] : '';
            $maxWidth = isset($m[$this->rexMaxWidth]) && $m[$this->rexMaxWidth] != '' ? $m[$this->rexMaxWidth] : 0;
            $precision = isset($m[$this->rexPrecision]) && $m[$this->rexPrecision] != '' ? $m[$this->rexPrecision] : null;
            $formatter = isset($m[$this->rexFormat]) && $m[$this->rexFormat] != '' ? $m[$this->rexFormat] : null;
            $formatArgs = null;
            if (isset($m[$this->rexFormatArgs]) && $m[$this->rexFormatArgs] != '') {
                $formatArgs = array_map('trim', explode(';', $m[$this->rexFormatArgs]));
            }

            $padChar = ' ';
            $padAlign = STR_PAD_LEFT;
            $padWidth = 0;
            if ($width != '' && $width !== '0') {
                $this->parseWidth($m[$this->rexWidth], $padWidth, $padChar, $padAlign);
            }

            $inlineValue = $this->getInlineValueFromIndex($index);
            if (null !== $inlineValue) {
                // wartość inline od razu wstawiamy
                if (empty($inlineValue) && !empty($emptyVal)) {
                    $inlineValue = $emptyVal;
                }
                $value = $this->formatValue($inlineValue, $formatter, $precision, $formatArgs);

                if ($padWidth > 0) {
                    $value = str_pad($value, $padWidth, $padChar, $padAlign);
                }
                if ($maxWidth > 0) {
                    if (strlen($value) > $m[$this->rexMaxWidth]) {
                        $value = substr($value, 0, $m[$this->rexMaxWidth]);
                    }
                }

                return $value;
            } else {
                $tag = new CompiledTag();
                $compiled->tags[] = $tag;
                $tag->index = $index;
                $tag->compiledIndex = $this->compileIndex($index);
                $tag->emptyValue = str_replace(['}}', '{{'], ['}', '{'], $emptyVal);
                $tag->precision = $precision;
                $tag->format = $formatter;
                $tag->formatArgs = $formatArgs;
                $tag->maxWidth = $maxWidth;

                $printfWidth = '';
                if (($padChar === ' ' || $padChar === '0' || empty($padChar)) && $padAlign !== STR_PAD_BOTH) {
                    if (STR_PAD_RIGHT === $padAlign) $printfWidth .= '-';
                    if ('0' === $padChar) $printfWidth .= '0';
                    if (!empty($padWidth)) $printfWidth .= $padWidth;
                } else {
                    $tag->width = $padWidth;
                    $tag->padChar = $padChar;
                    $tag->align = $padAlign;
                }
                return chr(19) . $printfWidth . 's';
            }
        }, $format);
        $compiled->string = str_replace(['%', chr(17), chr(18), chr(19)], ['%%', '{', '}', '%'], $compiled->string);
        return $compiled;
    }

    protected abstract function getDataValue($index, $data, $compiledIndex = null);

    protected function formatValue($value, $formatter, $precision, $formatterArgs) {
        return $value;
    }

    protected function getInlineValueFromIndex($index) {
        // to override
        return null;
    }

    protected function compileIndex($index) {
        // to override
        return null;
    }
}