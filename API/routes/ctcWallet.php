<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

//use \barry\payment\Password as barryPassword;
use \barry\payment\PasswordAutoRealWalletAndCashBack as barryPassword;

return function (App $app) {

//CTC WALLET
    $app->group('/ctcwallet', function (RouteCollectorProxy $group) {
        //CTC WALLET PAYMENT
        /**
         * @OA\POST(
         *     path="/ctcwallet/payment/password/check",
         *     @OA\Parameter(
         *         name="plainPassword",
         *         in="query",
         *         description="지갑 결제 비밀번호(암호화 필요)",
         *         required=true,
         *         @OA\Schema(
         *             type="string",
         *             format="password"
         *         )
         *     ),
         *     @OA\Response(
         *          response=200,
         *          description="정상 구매 처리",
         *          @OA\MediaType(
         *             mediaType="application/json",
         *          ),
         *      ),
         *
         * )
         */
        $group->POST('/payment/password/{type}', function (Request $request, Response $response, array $args) {
            $auth = new auth();
            //인증을 위해 로그인한 userid(주문자 ID 값)은 session 에서 받는다.
            if (!$auth->sessionAuth()) {
                $this->get('logger')->error('payment password not allowed access');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $memberId = $auth->getSessionId(); //주문자 ID 값 (핸드폰 번호)
            }
            //payment password check
            if ($args['type'] == 'check') {
                $this->get('logger')->info('payment password access view');

                $parsedBody = $request->getParsedBody();
                //virtual wallet -> real wallet transport and cashback function version Class:PasswordAutoRealWalletAndCashBack
                //$barryPassword = new barryPassword($parsedBody,$memberId,$this->get('logger'));
                $barryPassword = new barryPassword($parsedBody, $memberId, $this->get('logger'));

                $status = new Json();
                //password 처리중 나타나는 오류는 어떤 오류인지 리턴 안하고 404 에러만 던진다. (단 패스워드 비교,결제 요청 정보, 성공 정보 제외)
                //200번 코드만 정상 처리, SQL 오류시 익셉션 코드로 0으로 리턴 되어서 오류로 간주
                //리턴 true도 0이랑 같다고 비교되어 오류로 판정 됨.
                $passwordReturn = $barryPassword->process();
                if ($passwordReturn['code'] == 200) {
                    //정상인 경우 처리 안함.
                } else if ($passwordReturn['code'] == 10 || $passwordReturn['code'] == 20 || $passwordReturn['code'] == 177 || $passwordReturn['code'] == 144 || $passwordReturn['code'] == 155 || $passwordReturn['code'] == 166 || $passwordReturn['code'] == 255 || $passwordReturn['code'] == 244 || $passwordReturn['code'] == 233 || $passwordReturn['code'] == 266) {
                    $response->getBody()->write($status->success(array('paymentCode' => $passwordReturn['code'], 'paymentMsg' => $passwordReturn['msg'])));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                } else {
                    $response->getBody()->write($status->fail());
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
                }
                $this->get('logger')->info('payment password process complete');

                $returnArray = array(
                    'data' => $passwordReturn['data']
                );

                $response->getBody()->write($status->success($returnArray));
                $this->get('logger')->info('payment password check and payment success');
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $this->get('logger')->info('payment password check and payment fail!');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
            }

        });
    });
};
?>