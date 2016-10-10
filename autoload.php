<?php
spl_autoload_register(function($className) {
    if (0 !== strncmp('Nsformat\\', $className, 9)) return;
    if ('Nsformat\AbstractFormatter' === $className) {
        require_once __DIR__ . '/abstract.php';
        return;
    }
    if ('Nsformat\BaseFormatter' === $className) {
        require_once __DIR__ . '/base.php';
        return;
    }
    if ('Nsformat\StdFormatter' === $className) {
        require_once __DIR__ . '/std.php';
        return;
    }
    if ('Nsformat\SqlFormatter' === $className) {
        require_once __DIR__ . '/sql.php';
        return;
    }
});