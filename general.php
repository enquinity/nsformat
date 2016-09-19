<?php
namespace Nsformat;

require_once __DIR__ . '/base.php';

class LocaleInfo {
    public $decimalSeparator = '.';
    public $thousandsSeparator = ',';
    public $defaultPrecision = 2;

    public static function createForLocale($locale) {
        $info = new self();
        switch (strtolower($locale)) {
            case 'pl-pl':
                $info->decimalSeparator = ',';
                $info->thousandsSeparator = ' ';
                break;
            case 'en-us':
            case 'en-gb':
                break;
            default:
                throw new \Exception("Unknown localce code $locale");
        }
        return $info;
    }
}

class StdFormatter extends BaseFormatter {
    /**
     * @var LocaleInfo
     */
    protected $locale;

    public function __construct($localeCode = 'en-gb') {
        $this->locale = LocaleInfo::createForLocale($localeCode);
    }

    public function setLocale(LocaleInfo $localeInfo) {
        $this->locale = $localeInfo;
    }

    protected function formatValue($value, $formatter, $precision, $formatterArgs) {
        if (null === $formatter) $formatter = 'auto';
        if ('%' == $formatter[0]) {
            return sprintf($formatter, $value);
        }
        static $stdSpecifiers = array(
            'b' => 1, 'c' => 1, 'd' => 1, 'e' => 1, 'E' => 1, 'f' => 1, 'F' => 1,
            'g' => 1, 'G' => 1, 'o' => 1, 's' => 1, 'u' => 1, 'x' => 1, 'X' => 1,
        );
        if (!empty($stdSpecifiers[$formatter]) && empty($formatterArgs) && !is_array($value) && !is_object($value)) {
            if ($precision !== null && $precision != '') {
                return sprintf('%.' . $precision . $formatter, $value);
            } else {
                return sprintf('%' . $formatter, $value);
            }
        }
        switch ($formatter) {
            case 's':
            case 'data':
                if (is_array($value) || is_object($value)) {
                    if (!empty($formatterArgs) && isset($formatterArgs[0])) {
                        switch ($formatterArgs[0]) {
                            case 'json':
                                $value = json_encode($value);
                                break;
                            case 'php':
                                $value = var_export($value, 1);
                                break;
                            case 'dump':
                                ob_start();
                                var_dump($value);
                                $value = ob_get_clean();
                                break;
                            case 'join':
                                $delimiter = ',';
                                if (isset($formatterArgs[1]) && $formatterArgs[1] != '') $delimiter = $formatterArgs[1];
                                $value = implode($delimiter, $value);
                                break;
                            case 'print':
                                $value = print_r($value, 1);
                                break;
                            default:
                                $value = print_r($value, 1);
                        }
                    } else {
                        $value = print_r($value, 1);
                    }
                }
                $value = (string)$value;
                if (!empty($formatterArgs) && isset($formatterArgs[0])) {
                    switch ($formatterArgs[0]) {
                        case 'uc':
                            $value = strtoupper($value); break;
                        case 'ucf':
                            $value = ucfirst($value); break;
                        case 'lc':
                            $value = strtolower($value); break;
                        case 'lcf':
                            $value = lcfirst($value); break;
                        case 'caps':
                            $value = ucwords($value); break;
                    }
                }
                return $value;

            case 'a':
            case 'auto':
                if (is_null($value)) return 'NULL';
                if (is_numeric($value)) {
                    return $this->formatValue($value, false === strpos((string)$value, '.') ? 'int' : 'num', $precision, null);
                }
                if (is_bool($value)) return $value ? 'true' : 'false';
                if (is_string($value)) return $value;
                if ($value instanceof \Exception) return $this->formatValue($value, 'excpt', null, null);
                return print_r($value, 1);

            case 'n':
            case 'num':
                $decimals = $precision !== null ? $precision : $this->locale->defaultPrecision;
                return number_format($value, $decimals, $this->locale->decimalSeparator, $this->locale->thousandsSeparator);
            case 'i':
            case 'int':
                return number_format($value, 0, $this->locale->decimalSeparator, $this->locale->thousandsSeparator);
            case 'h':
                $decimals = $precision !== null ? $precision : $this->locale->defaultPrecision;
                $value = sprintf("%.{$decimals}f", $value);
                if ($this->locale->decimalSeparator != '.') $value = str_replace('.', $this->locale->decimalSeparator, $value);
                return $value;
            case 'y':
            case 'bool':
                if ($value) {
                    if (!empty($formatterArgs) && isset($formatterArgs[0])) return $formatterArgs[0];
                    return 'true';
                } else {
                    if (!empty($formatterArgs) && isset($formatterArgs[1])) return $formatterArgs[1];
                    return 'false';
                }
                break;

            case 'p':
            case 'excpt':
                return (string)$value;

            default:
                throw new \Exception("Unknown formatter $formatter");
        }
        //return parent::formatValue($value, $formatter, $precision, $formatterArgs);
    }
}