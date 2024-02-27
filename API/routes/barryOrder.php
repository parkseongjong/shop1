<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

use \barry\order\Invoice as barryOrderInvoice;
use \barry\order\Status as barryOrderStatus;
use \barry\order\Upload as barryOrderUpload;
use \barry\payment\CreditCard as barryPaymentCreditCard;
use \barry\common\Token as barryToken;

return function (App $app) {

//BARRY ORDER
    $app->group('/barry/order', function (RouteCollectorProxy $group) {

        /*
         *
         * goods(item) order upload
         *
         */
        $group->POST('/item/user/upload', function(Request $request, Response $response, array $args) {
            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('user goods(item) order upload not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();

            $barryOrderUpload = new barryOrderUpload($parsedBody,$memberId,$this);
            $status = new Json();
            $barryOrderUploadReturn = $barryOrderUpload->userItemUpload();

            $errorTargetCodeArray = array(406,403);

            if($barryOrderUploadReturn['code'] == 200){

            }
            else if(in_array($barryOrderUploadReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('orderCode'=>$barryOrderUploadReturn['code'],'orderMsg'=>$barryOrderUploadReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'orderMsg' => $barryOrderUploadReturn['orderMsg'],
                'orderId' => $barryOrderUploadReturn['orderId']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('user goods(item) order upload success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //invoice 체크는 status에서 모두 확인 합니다. 추후 별도로 해야 한다면 그때 다시 API 별개 항목으로 오픈,
        /*
        $group->POST('/invoice/{orderId}/{corp}/{number:[0-9]+}', function (Request $request, Response $response, array $args) {

            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('orderInvoice not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $this->get('logger')->info('orderInvoice access view');

            $getQueryParams = $request->getQueryParams();
            $getQueryParams['orderId'] = $args['orderId'];
            $getQueryParams['orderDeliveryInvoice'] = $args['number'];
            $getQueryParams['orderDeliveryCorp'] = $args['corp'];
            $barryOrderInvoice = new barryOrderInvoice($getQueryParams,$memberId,$this->get('logger'));
            $status = new Json();

            //invoice 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $barryOrderInvoiceReturn = $barryOrderInvoice->sweettrackerSearch();
            if($barryOrderInvoiceReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if($barryOrderInvoiceReturn['code'] == 403 || $barryOrderInvoiceReturn['code'] == 406){
                $response->getBody()->write($status->success(array('orderCode'=>$barryOrderInvoiceReturn['code'],'orderMsg'=>$barryOrderInvoiceReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            $this->get('logger')->info('orderInvoice process complete');

            $returnArray = array(
                'orderCode' => $barryOrderInvoiceReturn['orderCode'],
                'orderMsg' => $barryOrderInvoiceReturn['orderMsg']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('orderInvoice success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });
        */

        $group->GET('/invoice/list', function (Request $request, Response $response, array $args) {

            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('orderInvoice not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $this->get('logger')->info('orderInvoice access view');

            $getQueryParams = $request->getQueryParams();

            $barryOrderInvoice = new barryOrderInvoice($getQueryParams,$memberId,$this->get('logger'));
            $status = new Json();

            $barryOrderInvoiceReturn = $barryOrderInvoice->getDeliveryList();
            if($barryOrderInvoiceReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            $this->get('logger')->info('orderInvoice process complete');

            $returnArray = array(
                'data' => $barryOrderInvoiceReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('orderInvoice success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        $group->PUT('/status/{action}', function (Request $request, Response $response, array $args) {

            //인증을 위해 로그인한 userid은 session 에서 받는다.  (mb_id 핸드폰 번호)

            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('order not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $this->get('logger')->info('order access view');

            $parsedBody = $request->getParsedBody();
            $parsedBody['action'] = $args['action'];
            //임시 처리
            $barryOrderStatus = new barryOrderStatus($parsedBody,$memberId,$this->get('logger'));
            $status = new Json();
            //OrderStatus 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $orderStatusReturn = $barryOrderStatus->process();
            $errorTargetCodeArray = array(104,406,403);

            if($orderStatusReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if(in_array($orderStatusReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('orderCode'=>$orderStatusReturn['code'],'orderMsg'=>$orderStatusReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            $this->get('logger')->info('orderStatus process complete');

            $returnArray = array(
                'orderCode' => $orderStatusReturn['orderCode'],
                'orderMsg' => $orderStatusReturn['orderMsg']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('order success');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');

        });

        /*
         *
         * 상품 카드 결제
         *
         */
        $group->POST('/item/payment/credit-card', function(Request $request, Response $response, array $args) {

            if(!$this->get('sessionAuth')){
                $this->get('logger')->error('creditCard paymentnot allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $memberId = $this->get('sessionAuth');
            }

            $parsedBody = $request->getParsedBody();

            $barryPaymentCreditCard = new barryPaymentCreditCard($parsedBody,$memberId,$this->get('logger'));
            $status = new Json();
            $barryPaymentCreditCardReturn = $barryPaymentCreditCard->process();

            $errorTargetCodeArray = array(406,403,6002,5006,1157,1301,3100,3101,3128,3192,3110,3115,3133,3143,3223,3102,3112,3119,3217,3143);
//            $barryPaymentCreditCardReturn['code'] = 3115;
            if($barryPaymentCreditCardReturn['code'] == 200){

            }
            else if(in_array($barryPaymentCreditCardReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->fail(array('paymenetCode'=>$barryPaymentCreditCardReturn['code'],'paymentMsg'=>$barryPaymentCreditCardReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'orderMsg' => $barryPaymentCreditCardReturn['orderMsg'],
                'orderId' => $barryPaymentCreditCardReturn['orderId']
            );

            $response->getBody()->write($status->success($returnArray));
            $this->get('logger')->info('creditCard payment success');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });
    });

};
?>