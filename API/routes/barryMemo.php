<?php
declare(strict_types=1);

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\App;
use \Slim\Routing\RouteCollectorProxy;

use \barry\common\Auth;
use \barry\common\Json;

use \barry\memo\Chat as barryMemoChat;

return function (App $app) {
//BARRY MEMO(chattalk)
    $app->GET('/barry/memo' , function(Request $request, Response $response, array $args) {
        $auth = new auth();

        if(!$auth->sessionAuth()){
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
        else{
            $memberId = $auth->getSessionId();
        }

        $getQueryParams = $request->getQueryParams();
        $barryMemoChat = new barryMemoChat($getQueryParams,$memberId,$this->get('logger'));
        $status = new Json();

        $errorTargetCodeArray = array(406,403);

        $barryMemoChatReturn = $barryMemoChat->chatProcess();
        if($barryMemoChatReturn['code'] == 200){

        }
        else if(in_array($barryMemoChatReturn['code'],$errorTargetCodeArray)){
            $response->getBody()->write($status->fail(array('result'=>$barryMemoChatReturn['code'],'memoInfo'=>$barryMemoChatReturn['msg'])));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
        else{
            $response->getBody()->write($status->fail());
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        $returnArray = array(
            'memoInfo' => $barryMemoChatReturn['memoInfo'],
            'result' => $barryMemoChatReturn['result']
        );
        $response->getBody()->write($status->success($returnArray));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');
    });
};
?>