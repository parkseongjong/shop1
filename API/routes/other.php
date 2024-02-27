<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;
use \barry\payment\Password as barryPassword;
use \barry\other\PersonalInformation as barryPersonalInformation;

return function (App $app) {
    /**
     * @OA\Get(
     *     path="/API",
     *     summary="root path 보호",
     *     description="root path 보호용 입니다.",
     *     @OA\Response(
     *          response=301,
     *          description="/API/list로 리다이렉션 합니다."
     *      )
     * )
     */
    $app->get('/', function (Request $request, Response $response, array $args) {
        $this->get('logger')->info('index 정상 노출');
        return $response
            ->withHeader('Location', '/API/list')
            ->withStatus(301);
    });
    /**
     * @OA\Get(
     *     path="/API/list",
     *     @OA\Response(
     *          response=200,
     *          description="API 작동 여부 확인."
     *      ),
     *
     * )
     */
    $app->get('/list', function (Request $request, Response $response, array $args) {
        $response->getBody()->write("BARRY API SERVER");
        $this->get('logger')->info('list 정상 노출');
        return $response;
    });

    //test
    $app->get('/test', function (Request $request, Response $response, array $args) {
        $parsedBody = $request->getParsedBody();
        $barryPassword = new barryPassword($parsedBody,1000,$this->get('logger'));
        $barryPassword -> test();
        return $response;
    });

    $app->POST('/personal-information', function (Request $request, Response $response, array $args) {
        
        $parsedBody = $request->getParsedBody();
        //member ID 대신 토큰으로...
        $memberId = $parsedBody['token'];
        $barryPersonalInformation = new barryPersonalInformation($parsedBody,$memberId,$this);
        $barryPersonalInformationReturn = $barryPersonalInformation->agree();

        $status = new Json();

        $errorTargetCodeArray = array(406,403);

        if($barryPersonalInformationReturn['code'] == 200){

        }
        else if(in_array($barryPersonalInformationReturn['code'],$errorTargetCodeArray)){
            $response->getBody()->write($status->fail(array('piCode'=>$barryPersonalInformationReturn['code'],'piMsg'=>$barryPersonalInformationReturn['msg'])));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
        else{
            $response->getBody()->write($status->fail());
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }


        $returnArray = array(
            'piMsg' => $barryPersonalInformationReturn['msg'],
        );

        $response->getBody()->write($status->success($returnArray));
        $this->get('logger')->info('personal Information success');

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
    });
};
