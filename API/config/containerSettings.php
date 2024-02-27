<?php
declare(strict_types=1);

use \DI\Container;

use \Monolog\Logger;
use \MySQLHandler\MySQLHandler;
use \barry\db\DriverApi as barryDb;
use \barry\common\Auth;

use \SlimSession as slimSession;

return function (Container $c) {
    $c->set('logger', function () {
        date_default_timezone_set('Asia/Seoul');
        $logger = new \Monolog\Logger('barry');
        //$logger->pushHandler(new \Monolog\Handler\StreamHandler('./logs/app.log', Logger::DEBUG));
        //Logger::DEBUG,Logger::ERROR
        $db = barryDb::singletonMethod();
        if(BARRY_ENV == 'DEV'){
            $mySQLHandler = new MySQLHandler($db->getPDO(), "barry_log", array(), \Monolog\Logger::DEBUG);
        }
        else{
            $mySQLHandler = new MySQLHandler($db->getPDO(), "barry_log", array(), \Monolog\Logger::ERROR);
        }

        $logger->pushHandler($mySQLHandler);
        unset($db,$mySQLHandler);
        $logger->info('mono Log 로드 완료');
        return $logger;
    });

    $c->set('session', function () {
        return new slimSession\Helper();
    });

    $c->set('sessionAuth', function () {
        $auth = new auth();
        if(!$auth->sessionAuth()){
            return false;
        }
        else{
            return $auth->getSessionId();
        }
        //return new auth();
    });

    $c->set('jwtAuth', function () {
        return false;
    });

};