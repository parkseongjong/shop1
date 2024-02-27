<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

use \barry\admin\Order as barryAdminOrder;
use \barry\admin\Media as barryAdminMedia;
use \barry\admin\Log as barryAdminLog;
use \barry\admin\Goods as barryAdminGoods;
use \barry\admin\Coupon as barryAdminCoupon;
use \barry\admin\DanalPayup as barryDanalPayup;
use \barry\admin\Banner as barryBanner;
use \barry\admin\Cron as barryCron;

return function (App $app) {
//admin
    $app->group('/admin-inner/admin', function (RouteCollectorProxy $group) {
        //corn
        $group->POST('/cron/goods/coin/price', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin cron goods not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('admin cron goods access view');

            $parsedBody['serverParams'] = $request->getServerParams();

            $barryCron = new barryCron($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryCronReturn = $barryCron -> goodsEtp3PriceUpdate();

            if($barryCronReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryCronReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryCronReturn['code'],'otherMsg'=>$barryCronReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryCronReturn['data']
            );

            $response->getBody()->write($status->success($returnArray).PHP_EOL);
            $this->get('logger')->info('admin cron goods success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //cyberTron main count
        $group->GET('/goods/check/count/all-category', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $getQueryParams = $request->getQueryParams();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin cyberTron main count not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('admin cyberTron main count access view');

            $barryGoods = new barryAdminGoods($getQueryParams,$this->get('logger'));
            $status = new Json();

            $barryGoodsReturn = $barryGoods -> goodsPublishCount();

            if($barryGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryGoodsReturn['data']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin cyberTron main count success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods
        $group->GET('/goods/{table}/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $getQueryParams = $request->getQueryParams();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin goods not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin goods check
            $getQueryParams['table'] = $args['table'];
            $getQueryParams['id'] = (int) $args['id'];

            $this->get('logger')->info('admin goods access view');

            $barryGoods = new barryAdminGoods($getQueryParams,$this->get('logger'));
            $status = new Json();

            $barryGoodsReturn = $barryGoods -> getSingleItem();

            if($barryGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryGoodsReturn['code'],'otherMsg'=>$barryGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryGoodsReturn['data']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin goods success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods publish list
        $group->GET('/goods/{publishStatus}/{table}/{page:[0-9]+}/{numRows:[0-9]+}/{orderKey}/{orderDir}[/{searchKeyword}]', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $getQueryParams = $request->getQueryParams();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin goods publish list not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin goods publish type check
            $getQueryParams['table'] = $args['table'];
            $getQueryParams['publishStatus'] = $args['publishStatus'];
            $getQueryParams['page'] = (int) $args['page'];
            $getQueryParams['numRows'] = (int) $args['numRows'];
            $getQueryParams['orderKey'] = $args['orderKey'];
            $getQueryParams['orderDir'] = $args['orderDir'];
            //서치 키워드가 없을땐 NULL을 보내지않고 false를 보낸다.
            $getQueryParams['searchKeyword'] = (!isset($args['searchKeyword']))?false:$args['searchKeyword'];

            $this->get('logger')->info('admin goods publish list  access view');

            $barryGoods = new barryAdminGoods($getQueryParams,$this->get('logger'));
            $status = new Json();

            $barryGoodsReturn = $barryGoods -> getMultiItem();

            if($barryGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryGoodsReturn['code'],'otherMsg'=>$barryGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryGoodsReturn['data']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin goods publish list  success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //publish or reject
        $group->PUT('/goods/publish/{table}/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin goods publish not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin goods publish type check
            $parsedBody['table'] = $args['table'];
            $parsedBody['id'] = (int) $args['id'];

            $this->get('logger')->info('admin goods publish access view');

            $barryGoods = new barryAdminGoods($parsedBody,$this->get('logger'));
            $status = new Json();

            if(isset($parsedBody['type']) == 'reject'){
                $barryGoodsReturn = $barryGoods -> reject();
            }
            else{
                $barryGoodsReturn = $barryGoods -> publishItem();
            }

            if($barryGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryGoodsReturn['code'],'otherMsg'=>$barryGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryGoodsReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin goods publish success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods unpublish
        $group->DELETE('/goods/publish/{table}/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin goods publish not allowed access(2)');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin goods publish type check
            $parsedBody['table'] = $args['table'];
            $parsedBody['id'] = (int) $args['id'];

            $this->get('logger')->info('admin goods publish access view');

            $barryGoods = new barryAdminGoods($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryGoodsReturn = $barryGoods -> unpublishItem();

            if($barryGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryGoodsReturn['code'],'otherMsg'=>$barryGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryGoodsReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin goods publish success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //banner
        $group->GET('/banner/list/{page:[0-9]+}/{numRows:[0-9]+}/{orderKey}/{orderDir}[/{searchKeyword}]', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $getQueryParams = $request->getQueryParams();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin banner not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin banner type check
            $getQueryParams['page'] = (int) $args['page'];
            $getQueryParams['numRows'] = (int) $args['numRows'];
            $getQueryParams['orderKey'] = $args['orderKey'];
            $getQueryParams['orderDir'] = $args['orderDir'];
            //서치 키워드가 없을땐 NULL을 보내지 false를 보낸다.
            $getQueryParams['searchKeyword'] = (!isset($args['searchKeyword']))?false:$args['searchKeyword'];

            $this->get('logger')->info('admin banner access view');

            $barryBanner = new barryBanner($getQueryParams,$this->get('logger'));
            $status = new Json();
            $barryBannerReturn = $barryBanner -> bannerList();

            if($barryBannerReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryBannerReturn['data']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin banner list success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //banner disable
        $group->DELETE('/banner/disable', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            $barryBanner = new barryBanner($parsedBody, $this->get('logger'));
            $status = new Json();

            $barryBannerReturn = $barryBanner->bannerDisabled();

            if($barryBannerReturn['code'] == 200){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');

            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
        });

        //bannerEnable
        $group->PUT('/banner/enable', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            $barryBanner = new barryBanner($parsedBody, $this->get('logger'));
            $status = new Json();

            $barryBannerReturn = $barryBanner->bannerEnabled();

            if($barryBannerReturn['code'] == 200){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
        });

        //banner upload
        $group->POST('/banner/upload', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            //files
            $parsedBody['files'] = $request->getUploadedFiles();
            //기본 url도 리턴, 만약 API 서버 위치가 달라진다면, 이 부분 수정 고려 해봐야 함.
            $parsedBody['uriObj'] = $request->getUri();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin banner not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('admin banner upload access view');
            $barryBanner = new barryBanner($parsedBody, $this->get('logger'));
            $status = new Json();

            $barryBannerReturn = $barryBanner->bannerUpload();

            if($barryBannerReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->getBody()->write($status->success());
            $this->get('logger')->info('admin banner upload success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //banner Modify
        $group->POST('/banner/modify', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            //files
            $parsedBody['files'] = $request->getUploadedFiles();
            //기본 url도 리턴, 만약 API 서버 위치가 달라진다면, 이 부분 수정 고려 해봐야 함.
            $parsedBody['uriObj'] = $request->getUri();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin banner not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('admin banner modify access view');
            $barryBanner = new barryBanner($parsedBody, $this->get('logger'));
            $status = new Json();

            $barryBannerReturn = $barryBanner->bannerModify();

            if($barryBannerReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->getBody()->write($status->success());
            $this->get('logger')->info('admin banner modify success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //banner detail single get
        $group->GET('/banner/detail/{bannerId:[0-9]+}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $getQueryParams = $request->getQueryParams();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin banner not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin banner type check
            $getQueryParams['bannerId'] = (int) $args['bannerId'];

            $this->get('logger')->info('admin banner detail access view');

            $barryBanner = new barryBanner($getQueryParams,$this->get('logger'));
            $status = new Json();
            $barryBannerReturn = $barryBanner -> bannerDetail();

            if($barryBannerReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryBannerReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryBannerReturn['code'],'otherMsg'=>$barryBannerReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $barryBannerReturn['data']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin banner list success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //order
        $group->POST('/order/{type}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuth($parsedBody)){
                $this->get('logger')->error('admin order not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin order type check

            //유입되는 타입을 가변변수로 함수 호출 합니다.
            $inputType = $args['type'];
            if($inputType == 'orderEtokenStatusList') {
                $this->get('logger')->info('admin order access view');

                $barryOrder = new barryAdminOrder($parsedBody,$this->get('logger'));
                $status = new Json();

                $barryOrderReturn = $barryOrder -> $inputType();

                if($barryOrderReturn['code'] == 200){
                    //정상인 경우 처리 없음
                }
                else if($barryOrderReturn['code'] == 9999){
                    $response->getBody()->write($status->success(array('otherCode'=>$barryOrderReturn['code'],'otherMsg'=>$barryOrderReturn['msg'])));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }
                else{
                    $response->getBody()->write($status->fail());
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }

                $returnArray = array(
                    'data' => $barryOrderReturn['data']
                );

                $response->getBody()->write($status->success($returnArray));
                $this->get('logger')->info('admin order list success');
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $this->get('logger')->info('admin order list fail!');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

        });

        //item order cancel
        $group->DELETE('/order/item/cancel', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin item order cancel not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $getQueryParams = $request->getQueryParams();

            $this->get('logger')->info('admin item order cancel access view');

            $barryOrder = new barryAdminOrder($getQueryParams,$this->get('logger'));
            $status = new Json();

            $barryOrderReturn = $barryOrder -> setCancelStatus();

            $errorTargetCodeArray = array(406,403);

            if($barryOrderReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if(in_array($barryOrderReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('otherCode'=>$barryOrderReturn['code'],'otherMsg'=>$barryOrderReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'orderCode' => $barryOrderReturn['orderCode'],
                'orderMsg' => $barryOrderReturn['orderMsg']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('admin item order cancel success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //media
        $group->POST('/media/{type}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            if(!$auth->ckeyAuth($parsedBody)){
                $this->get('logger')->error('admin media not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            //admin media type check

            //유입되는 타입을 가변변수로 함수 호출 합니다.
            $inputType = $args['type'];
            if($inputType == 'boardList' || $inputType == 'linkHitList') {
                $this->get('logger')->info('admin media list access view');

                $barryMedia = new barryAdminMedia($parsedBody,$this->get('logger'));
                $status = new Json();

                $barryMediaReturn = $barryMedia -> $inputType();

                if($barryMediaReturn['code'] == 200){
                    //정상인 경우 처리 없음
                }
                else if($barryMediaReturn['code'] == 9999){
                    $response->getBody()->write($status->success(array('otherCode'=>$barryMediaReturn['code'],'otherMsg'=>$barryMediaReturn['msg'])));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }
                else{
                    $response->getBody()->write($status->fail());
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }

                $returnArray = array(
                    'data' => $barryMediaReturn['data']
                );

                $response->getBody()->write($status->success($returnArray));
                $this->get('logger')->info('admin media list success');
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $this->get('logger')->info('admin media list fail!');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

        });

        //log
        $group->POST('/log/{type}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('admin log not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            //admin media type check
            if($args['type'] == 'list' || $args['type'] == 'legacyList'){
                $this->get('logger')->info('admin log list access view');

                $barryLog = new barryAdminLog($parsedBody,$this->get('logger'));
                $status = new Json();

                if($args['type'] == 'list') {
                    $barryLogReturn = $barryLog-> list();
            }
                else{
                    $barryLogReturn = $barryLog-> legacyList();
                }

                if($barryLogReturn['code'] == 200){
                    //정상인 경우 처리 없음
                }
                else if($barryLogReturn['code'] == 9999){
                    $response->getBody()->write($status->success(array('otherCode'=>$barryLogReturn['code'],'otherMsg'=>$barryLogReturn['msg'])));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }
                else{
                    $response->getBody()->write($status->fail());
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }

                $returnArray = array(
                    'data' => $barryLogReturn['data']
                );

                $response->getBody()->write($status->success($returnArray));
                $this->get('logger')->info('admin log list success');
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $this->get('logger')->info('admin log list fail!');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

        });

        //coupon
        $group->POST('/coupon/{type}', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuth($parsedBody)){
                $this->get('logger')->error('admin coupon not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            //admin media type check
            if($args['type'] == 'paymentList'){
                $this->get('logger')->info('admin coupon list access view');

                $barryLog = new barryAdminCoupon($parsedBody,$this->get('logger'));
                $status = new Json();

                if($args['type'] == 'paymentList') {
                    $barryCouponReturn = $barryLog-> couponPaymentList();
                }

                if($barryCouponReturn['code'] == 200){
                    //정상인 경우 처리 없음
                }
                else if($barryCouponReturn['code'] == 9999){
                    $response->getBody()->write($status->success(array('otherCode'=>$barryCouponReturn['code'],'otherMsg'=>$barryCouponReturn['msg'])));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }
                else{
                    $response->getBody()->write($status->fail());
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }

                $returnArray = array(
                    'data' => $barryCouponReturn['data']
                );

                $response->getBody()->write($status->success($returnArray));
                $this->get('logger')->info('admin coupon list success');
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $this->get('logger')->info('admin coupon list fail!');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

        });

        //danal payup
        $group->POST('/payup/payment/card/cancel', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            $parsedBody = $request->getParsedBody();

            if(!$auth->ckeyAuth($parsedBody)){
                $this->get('logger')->error('admin payup not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('admin payup access view');

            $barryPayup = new barryDanalPayup($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryPayupReturn = $barryPayup-> cardCancel();

            if($barryPayupReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryPayupReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryPayupReturn['code'],'otherMsg'=>$barryPayupReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
//
//        $returnArray = array(
//            'data' => $barryPayupReturn['data']
//        );

//        $response->getBody()->write($status->success($returnArray));
            $response->getBody()->write($status->success());
            $this->get('logger')->info('admin payup success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods Modify
        $group->POST('/goods/temp-modify', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            $this->get('logger')->alert(print_r($parsedBody,true));
            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('temp Modify not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('temp1 Modify access view');

            $barryAdminGoods = new barryAdminGoods($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryAdminGoodsReturn = $barryAdminGoods-> tempModify();

            if($barryAdminGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryAdminGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryAdminGoodsReturn['code'],'otherMsg'=>$barryAdminGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->getBody()->write($status->success());
            $this->get('logger')->info('temp Modify success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods Modify2
        $group->POST('/goods/temp-modify2', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            $this->get('logger')->alert(print_r($parsedBody,true));
            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('temp Modify not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('temp2 Modify access view');

            $barryAdminGoods = new barryAdminGoods($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryAdminGoodsReturn = $barryAdminGoods-> tempModify2();

            if($barryAdminGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryAdminGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryAdminGoodsReturn['code'],'otherMsg'=>$barryAdminGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->getBody()->write($status->success());
            $this->get('logger')->info('temp Modify success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //goods Modify3
        $group->POST('/goods/temp-modify3', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            $parsedBody = $request->getParsedBody();
            $parsedBody['files'] = $request->getUploadedFiles();

            $this->get('logger')->alert(print_r($parsedBody,true));
            if(!$auth->ckeyAuthHeader($request->getHeaderLine('Authorization'))){
                $this->get('logger')->error('temp Modify not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $this->get('logger')->info('temp3 Modify access view');

            $barryAdminGoods = new barryAdminGoods($parsedBody,$this->get('logger'));
            $status = new Json();

            $barryAdminGoodsReturn = $barryAdminGoods->tempModify3();

            $this->get('logger')->info($barryAdminGoodsReturn);
            if($barryAdminGoodsReturn['code'] == 200){
                //정상인 경우 처리 없음
            }
            else if($barryAdminGoodsReturn['code'] == 9999){
                $response->getBody()->write($status->success(array('otherCode'=>$barryAdminGoodsReturn['code'],'otherMsg'=>$barryAdminGoodsReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->getBody()->write($status->success());
            $this->get('logger')->info('temp3 Modify success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

    });
};
?>