<?php

namespace Nsformat {
    class StdFormatterInstance {
        private static $instance = null;

        /**
         * @return StdFormatter
         */
        public static function instance() {
            if (null === self::$instance) {
                if (!class_exists(\Nsformat\StdFormatter::class)) {
                    require_once __DIR__ . '/general.php';
                }
                self::$instance = new StdFormatter();
            }
            return self::$instance;
        }
    }
}

namespace {

    function nsformat($format/*, $args... */) {
        $args = func_get_args();
        array_shift($args);
        return \Nsformat\StdFormatterInstance::instance()->formatArr($format, $args);
    }

    function nsformatCompile($format) {
        return \Nsformat\StdFormatterInstance::instance()->compile($format);
    }

    function nsformatSetLocaleCode($localeCode) {
        \Nsformat\StdFormatterInstance::instance()->setLocale(\Nsformat\LocaleInfo::createForLocale($localeCode));
    }
}