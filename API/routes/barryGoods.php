<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Json;

use \barry\goods\Detail as barryGoodsDetail;
use \barry\goods\Upload as barryGoodsUpload;
use \barry\goods\Option as barryGoodsUploadOption;
use \barry\common\Token as barryToken;


return function (App $app) {

//BARRY GOODS
    $app->group('/barry/goods', function (RouteCollectorProxy $group) {
        $group->POST('/status/{action}', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('goods not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();
            $parsedBody['action'] = $args['action'];

            $barryGoodsDetail = new barryGoodsDetail($parsedBody,$memberId,$this->get('logger'));
            $status = new Json();
            $barryGoodsDetailReturn = $barryGoodsDetail->goodsDetailProcess();

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsDetailReturn['code'] == 200){

            }
            else if(in_array($barryGoodsDetailReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('goodsCode'=>$barryGoodsDetailReturn['code'],'goodsMsg'=>$barryGoodsDetailReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }


            $returnArray = array(
                'goodsCode' => $barryGoodsDetailReturn['goodsCode'],
                'goodsMsg' => $barryGoodsDetailReturn['goodsMsg']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('goods success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM UPLOAD
         * (GOODS와 ITEM 단어 사용을 결정 해야함.... ITEM이 GOODS의 하위라고 생각하고 혼용 사용?
         *
         */
        $group->POST('/item/upload', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('item upload not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();
            $parsedBody['files'] = $request->getUploadedFiles();
            $barryGoodsUpload = new barryGoodsUpload($parsedBody,$memberId,$this);
            $barryGoodsUploadReturn = $barryGoodsUpload->itemUpload();
            $status = new Json();

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsUploadReturn['code'] == 200){

            }
            else if(in_array($barryGoodsUploadReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('uploadCode'=>$barryGoodsUploadReturn['code'],'uploadMsg'=>$barryGoodsUploadReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }


            $returnArray = array(
                'uploadMsg' => $barryGoodsUploadReturn['uploadMsg'],
                'html' => $barryGoodsUploadReturn['html']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item upload success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM MODIFY
         *
         */
        $group->POST('/item/upload/modifications', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('item upload not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();
            $parsedBody['files'] = $request->getUploadedFiles();
            $barryGoodsUpload = new barryGoodsUpload($parsedBody,$memberId,$this);

            $barryGoodsUploadReturn = $barryGoodsUpload->itemModify();
            $status = new Json();

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsUploadReturn['code'] == 200){

            }
            else if(in_array($barryGoodsUploadReturn['code'],$barryGoodsUploadReturn)){
                $response->getBody()->write($status->fail(array('uploadCode'=>$barryGoodsUploadReturn['code'],'uploadMsg'=>$barryGoodsUploadReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }


            $returnArray = array(
                'uploadMsg' => $barryGoodsUploadReturn['uploadMsg']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item upload success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM UPLOAD TOKEN SET
         *
         */
        $group->POST('/item/upload/token', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('item upload not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();
            $token = barryToken::singletonMethod();

            $barryGoodsUploadTokenReturn = $token->setUploadToken($parsedBody['bo_table'],$this);
            $status = new Json();

            if($barryGoodsUploadTokenReturn['code'] == 200){

            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'token' => $barryGoodsUploadTokenReturn['token']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item upload success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM UPLOAD SELLER SELECT OPTION
         *
         */
        $group->POST('/item/upload/option/select', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('item upload option select not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();
            $barryGoodsUploadOption = new barryGoodsUploadOption($parsedBody,$memberId,$this->get('logger'));

            $barryGoodsUploadOptionReturn = $barryGoodsUploadOption->getSellerUploadSelectOptionForm();
            $status = new Json();

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsUploadOptionReturn['code'] == 200){

            }
            else if(in_array($barryGoodsUploadOptionReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('optionCode'=>$barryGoodsUploadOptionReturn['code'],'optionMsg'=>$barryGoodsUploadOptionReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'html' => $barryGoodsUploadOptionReturn['html']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item upload option select success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM MODIFY SELLER SELECT OPTION
         *
         */
        $group->GET('/item/upload/option/select/{tableId:[a-zA-Z0-9]+}/{itemId:[0-9]+}', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('item upload option select not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $getQueryParams = $request->getQueryParams();
            $getQueryParams['itemId'] = $args['itemId'];
            $getQueryParams['tableId'] = $args['tableId'];

            $barryGoodsUploadOption = new barryGoodsUploadOption($getQueryParams,$memberId,$this->get('logger'));

            $barryGoodsUploadOptionReturn = $barryGoodsUploadOption->getSellerModifySelectOptionForm();
            $status = new Json();

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsUploadOptionReturn['code'] == 200){

            }
            else if(in_array($barryGoodsUploadOptionReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('optionCode'=>$barryGoodsUploadOptionReturn['code'],'optionMsg'=>$barryGoodsUploadOptionReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'html' => $barryGoodsUploadOptionReturn['html']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item upload option select success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        /*
         *
         * GOODS ITEM USER VIEW OPTION LIST
         *
         */
        $group->GET('/item/option/select/{tableId:[a-zA-Z0-9]+}/{itemId:[0-9]+}', function(Request $request, Response $response, array $args) {
            //인증이 필요한 부분이 아님..

            //single or ALL 인지 파라메터 하나 더 받자.
            $getQueryParams = $request->getQueryParams();
            $getQueryParams['itemId'] = $args['itemId'];
            $getQueryParams['tableId'] = $args['tableId'];
            //$getQueryParams['type']

            $barryGoodsOption = new barryGoodsUploadOption($getQueryParams,false,$this->get('logger'));

            $status = new Json();

            if($getQueryParams['type'] == 'single'){
                $barryGoodsOptionReturn = $barryGoodsOption->getUserSelectOptionSingleList();
            }
            else if($getQueryParams['type'] == 'all'){
                $barryGoodsOptionReturn = $barryGoodsOption->getUserSelectOptionAllList();
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $errorTargetCodeArray = array(406,403);

            if($barryGoodsOptionReturn['code'] == 200){

            }
            else if(in_array($barryGoodsOptionReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('optionCode'=>$barryGoodsOptionReturn['code'],'optionMsg'=>$barryGoodsOptionReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'html' => $barryGoodsOptionReturn['html']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('item option select success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });
    });
};
?>