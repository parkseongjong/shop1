<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

use \barry\coupon\Service as barryCoupon;


return function (App $app) {
//COUPON
    $app->group('/coupon', function (RouteCollectorProxy $group) {

        //COUPON PAYMEMNT
        $group->POST('/payment', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('coupon create auth not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }

            $this->get('logger')->info('coupon create auth access view');

            $parsedBody = $request->getParsedBody();

            $barryCoupon = new barryCoupon($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //payment 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $barryCouponReturn = $barryCoupon->payment();
            $errorTargetCodeArray = array(406,1157,1301,3100,3110,3115,3223,3102,3192);
            if($barryCouponReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if(in_array($barryCouponReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('couponCode'=>$barryCouponReturn['code'],'couponMsg'=>$barryCouponReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'couponMsg' => $barryCouponReturn['couponMsg'],
                'data' => $barryCouponReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //COUPON Single GET
        $group->GET('/{couponNumber:[a-zA-Z0-9]+}', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('coupon create auth not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }

            $this->get('logger')->info('coupon create auth access view');

            $parsedBody = $request->getParsedBody();
            $parsedBody['couponNumber'] = $args['couponNumber'];

            $barryCoupon = new barryCoupon($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //getSingleCoupon 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $barryCouponReturn = $barryCoupon->getSingleCoupon();

            if($barryCouponReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if($barryCouponReturn['code'] == 404){
                $response->getBody()->write($status->success(array('couponCode'=>$barryCouponReturn['code'],'couponMsg'=>$barryCouponReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryCouponReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //COUPON Multi GET
        $group->GET('', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('coupon multi get not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }

            $this->get('logger')->info('coupon multi get auth access view');

            $parsedBody = $request->getParsedBody();

            $barryCoupon = new barryCoupon($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //getMultiCoupon 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $barryCouponReturn = $barryCoupon->getMultiCoupon();

            if($barryCouponReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if($barryCouponReturn['code'] == 404){
                $response->getBody()->write($status->success(array('couponCode'=>$barryCouponReturn['code'],'couponMsg'=>$barryCouponReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryCouponReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //COUPON CREATE
        $group->POST('', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('coupon create auth not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }

            $this->get('logger')->info('coupon create auth access view');

            $parsedBody = $request->getParsedBody();

            $barryCoupon = new barryCoupon($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //create 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $barryCouponReturn = $barryCoupon->create();
            if($barryCouponReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if($barryCouponReturn['code'] == 406){
                $response->getBody()->write($status->success(array('couponCode'=>$barryCouponReturn['code'],'couponMsg'=>$barryCouponReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryCouponReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });
    });
};
?>