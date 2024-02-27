<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

use \barry\payment\DanalPayup as barryPayup;

return function (App $app) {

//PAYUP (지금은 내부 결제만 허용 한다.)
    $app->group('/payup', function (RouteCollectorProxy $group) {
        //PAYUP PAYMENT AUTH
        $group->GET('/payment/auth', function (Request $request, Response $response, array $args) {
            //처리 안함...
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //PAYUP PAYMENT CREDIT CARD
        /**
         * @OA\POST(
         *     path="/payup/payment/credit-card",
         *     @OA\Parameter(
         *         name="cardNumber",
         *         in="query",
         *         description="카드 번호",
         *         required=true,
         *         @OA\Schema(
         *             type="string",
         *             format=""
         *         )
         *     ),
         *     @OA\Response(
         *          response=200,
         *          description="결제 요청 성공",
         *          @OA\MediaType(
         *             mediaType="application/json",
         *          ),
         *      ),
         *
         * )
         */
        $group->POST('/payment/credit-card', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('payment payup auth not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }

            //TEST
//        $memberId = '01096415095';
            $this->get('logger')->info('payment payup auth access view');

            $parsedBody = $request->getParsedBody();
//        $parsedBody = array(
//            'cardNumber' => '1111222233334444',
//            'expireMonth' => '11',
//            'expireYear' => '21',
//            'userName' => '오정택',
//            'userMobileNumber' => '01050958112',
//            'cardPw' => '10'
//        );
            $barryPayup= new barryPayup($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //creditCard 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $creditCardReturn = $barryPayup->creditCard();
            $errorTargetCodeArray = array(406,403);
            if($creditCardReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if(in_array($creditCardReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('paymentCode'=>$creditCardReturn['code'],'paymentMsg'=>$creditCardReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $creditCardReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //PAYUP PAYMENT VIRTUAL BANK
        $group->POST('/payment/virtual-bank', function (Request $request, Response $response, array $args) {
            $auth = new auth();

            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('payment payup auth not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId();
            }
            //TEST
            //$memberId = '01096415095';
            $this->get('logger')->info('payment payup auth access view');

            $parsedBody = $request->getParsedBody();
            /*
            $parsedBody = array(
                'orderNumber' => 'CP2021012111284296', //dev order number
                'orderType' => 'coupon',
                'userMobileNumber' => '01050958112',
                'cashReceiptUse' => '1',
                'cashReceiptType' => '0',
                'cashReceiptNo' => '01050958112',
                'bankCode' => 'BK03'
            );
            */

            $barryPayup= new barryPayup($parsedBody, $memberId, $this->get('logger'));
            $status = new Json();
            //VIRTUAL BANK 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $virtualBankReturn = $barryPayup->virtualBank();
            $errorTargetCodeArray = array(406,403);
            if($virtualBankReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else if(in_array($virtualBankReturn['code'],$errorTargetCodeArray)){
                $response->getBody()->write($status->success(array('paymentCode'=>$virtualBankReturn['code'],'paymentMsg'=>$virtualBankReturn['msg'])));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $virtualBankReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });

        //PAYUP PAYMENT VIRTUAL BANK TRANSACTION CHECK
        $group->GET('/payment/virtual-bank/return', function (Request $request, Response $response, array $args) {

            //http://barrybarries.kr/API/payup/payment/virtual-bank/return?RESPONSE_CODE=0000&STATUS_CODE=2001&TRANSACTION_ID=20210125152422016443&MERCHANT_ID=oneheartsmart&RESPONSE_MSG=hi~&CASH_AUTH_NO=0000&ORDER_NUMBER=CP2021012515242237

            $parsedBody = $request->getQueryParams();
            $barryPayup= new barryPayup($parsedBody, false, $this->get('logger'));

            $status = new Json();
            //creditCard 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다.
            //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
            //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
            $virtualBankReturn = $barryPayup->virtualBankReturn();
            if($virtualBankReturn['code'] == 200){
                //정상인 경우 처리 안함.
            }
            else{
                $response->getBody()->write($status->fail());
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

            $returnArray = array(
                'data' => $virtualBankReturn['data'],
            );

            $response->getBody()->write($status->success($returnArray));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        });
    });
};
?>