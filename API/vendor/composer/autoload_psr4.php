<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'barry\\payment\\' => array($baseDir . '/controllers/payment'),
    'barry\\other\\' => array($baseDir . '/controllers/other'),
    'barry\\order\\' => array($baseDir . '/controllers/order'),
    'barry\\memo\\' => array($baseDir . '/controllers/memo'),
    'barry\\goods\\' => array($baseDir . '/controllers/goods'),
    'barry\\encrypt\\' => array($baseDir . '/../plugin/barryEncrypt'),
    'barry\\db\\' => array($baseDir . '/../plugin/barryDbDriver'),
    'barry\\coupon\\' => array($baseDir . '/controllers/coupon'),
    'barry\\common\\' => array($baseDir . '/controllers/common'),
    'barry\\client\\' => array($baseDir . '/controllers/client'),
    'barry\\banner\\' => array($baseDir . '/controllers/banner'),
    'barry\\admin\\' => array($baseDir . '/controllers/admin'),
    'Webmozart\\Assert\\' => array($vendorDir . '/webmozart/assert/src'),
    'Symfony\\Polyfill\\Php80\\' => array($vendorDir . '/symfony/polyfill-php80'),
    'Symfony\\Polyfill\\Ctype\\' => array($vendorDir . '/symfony/polyfill-ctype'),
    'Symfony\\Component\\Yaml\\' => array($vendorDir . '/symfony/yaml'),
    'Symfony\\Component\\Finder\\' => array($vendorDir . '/symfony/finder'),
    'Slim\\Psr7\\' => array($vendorDir . '/slim/psr7/src'),
    'Slim\\' => array($vendorDir . '/slim/slim/Slim'),
    'Psr\\Log\\' => array($vendorDir . '/psr/log/Psr/Log'),
    'Psr\\Http\\Server\\' => array($vendorDir . '/psr/http-server-handler/src', $vendorDir . '/psr/http-server-middleware/src'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-factory/src', $vendorDir . '/psr/http-message/src'),
    'Psr\\Container\\' => array($vendorDir . '/psr/container/src'),
    'PhpDocReader\\' => array($vendorDir . '/php-di/phpdoc-reader/src/PhpDocReader'),
    'Opis\\Closure\\' => array($vendorDir . '/opis/closure/src'),
    'OpenApi\\' => array($vendorDir . '/zircote/swagger-php/src'),
    'MySQLHandler\\' => array($vendorDir . '/wazaari/monolog-mysql/src/MySQLHandler'),
    'Monolog\\' => array($vendorDir . '/monolog/monolog/src/Monolog'),
    'League\\Plates\\' => array($vendorDir . '/league/plates/src'),
    'Lcobucci\\JWT\\' => array($vendorDir . '/lcobucci/jwt/src'),
    'Invoker\\' => array($vendorDir . '/php-di/invoker/src'),
    'Fig\\Http\\Message\\' => array($vendorDir . '/fig/http-message-util/src'),
    'FastRoute\\' => array($vendorDir . '/nikic/fast-route/src'),
    'Doctrine\\Common\\Lexer\\' => array($vendorDir . '/doctrine/lexer/lib/Doctrine/Common/Lexer'),
    'Doctrine\\Common\\Annotations\\' => array($vendorDir . '/doctrine/annotations/lib/Doctrine/Common/Annotations'),
    'DI\\Bridge\\Slim\\' => array($vendorDir . '/php-di/slim-bridge/src'),
    'DI\\' => array($vendorDir . '/php-di/php-di/src'),
    '' => array($vendorDir . '/bryanjhv/slim-session/src'),
);
