<?php
declare(strict_types=1);

use \Slim\App;
use \Slim\Middleware\Session;

//미들웨어셋팅은 따로 분리하지 않았음.
return function (App $app) {
    $app->setBasePath('/API');
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware();
    $app->add(
        new Session([
            'name' => 'PHPSESSID', //기본 값을 사용
            'autorefresh' => true,
            'lifetime' => '3 hour',
            'ini_settings' => [
                'session.save_path' => __DIR__.'/../../data/session',
                'session.use_trans_sid' => 0,
                'session.cache_expire' => 180,
                'session.gc_probability' => 1,
                'session.gc_divisor' => 100,
            ]
        ])
    );
    $app->OPTIONS('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        /*
         설정된 URL CORS 설정.
        $test = $request->getUri();
        var_dump($test->getHost());
        var_dump($test->getScheme());
        */
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            //->withHeader('Access-Control-Allow-Origin', 'https://admin-tools.barrybarries.kr')
            //->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
            //->withHeader('Access-Control-Allow-Origin', 'https://cybertronchain.com')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('P3P', 'CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');
        /*
            barry GB에서 날아오는 세션을 공유 함.
            API 외부 접속 시 인증을 위해 JWT 인증이 구현 되기 전까지는,... 세션을 통해 인증을 한다.
            TO-DO
            JWT로 인증 되면, GB 에서는 로그인 하면 auth 에서 JWT 값을 발급 받는다.

        */
    });


    /**
     * Add Error Handling Middleware
     *
     * @param bool $displayErrorDetails -> Should be set to false in production
     * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
     * @param bool $logErrorDetails -> Display error details in error log
     * which can be replaced by a callable of your choice.

     * Note: This middleware should be added last. It will not handle any exceptions/errors
     * for middleware added after it.
     */
    $errorMiddleware = $app->addErrorMiddleware(false, false, false);


};