<?php

use App\User;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Tuupola\Base62;
use Exception\NotFoundException;
use Exception\ForbiddenException;

$app->post(getenv("MIDDLE_URL") . "/banned", function ($request, $response, $arguments) {
    
    /* Check if the user is admin or not */
    if (false === $this->token->decoded->isAdmin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["admin.ban"])) {
        throw new ForbiddenException("Token not allowed to ban users.", 403);
    }


    $body = $request->getParsedBody();

    $uid = $body["uid"];
            
    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "uid" => $uid
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    
            
    $user->status = 1;
    
    
    $this->spot->mapper("App\User")->save($user);            
    $userId = $user->id;
    $bannedRole = $this->authManager->getRole('non-active.user');

    if ($bannedRole === null) {
        throw new NotFoundException("Something wrong with roles. Please contact administrator!");
    }
    $this->authManager->revokeAll($userId);
    $this->authManager->assign($bannedRole, $userId);

    $data["status"] = "ok";
    $data["message"] = "Talent Status is Updated ";
    
    $client = new Client();
    $res = $client->request('POST', getenv("PUBLISH_ENDPOINT"), ['headers' =>
        [
            'Content-Type' => 'application/json',
            'Authorization' => $request->getHeader('HTTP_AUTHORIZATION')
        ],
        'json' => [
            'is_published' => false,
            'uid' => $uid
        ]

    ]);
    // print_r($res->getBody()->getContents());
    // die();
    $data["tms"] = json_decode($res->getBody()->getContents());

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        
});

$app->post(getenv("MIDDLE_URL") . "/soft", function ($request, $response, $arguments) {
    
    /* Check if the user is admin or not */
    if (false === $this->token->decoded->isAdmin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["admin.ban"])) {
        throw new ForbiddenException("Token not allowed to ban users.", 403);
    }


    $body = $request->getParsedBody();

    $uid = $body["uid"];
            
    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "uid" => $uid
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    
            
    $user->status = 2;
    
    // print_r($user->uid);
    // die();
    $this->spot->mapper("App\User")->save($user);            
    $userId = $user->id;
    $bannedRole = $this->authManager->getRole('banned.user');

    if ($bannedRole === null) {
        throw new NotFoundException("Something wrong with roles. Please contact administrator!");
    }
    $this->authManager->revokeAll($userId);
    $this->authManager->assign($bannedRole, $userId);

    $data["status"] = "ok";
    $data["message"] = "Talent Status is Updated ";
    
    $client = new Client();
    $res = $client->request('POST', getenv("PUBLISH_ENDPOINT"), ['headers' =>
        [
            'Content-Type' => 'application/json',
            'Authorization' => $request->getHeader('HTTP_AUTHORIZATION')
        ],
        'json' => [
            'is_published' => false,
            'uid' => $uid
        ]

    ]);
    // print_r($res->getBody()->getContents());
    // die();
    $data["tms"] = json_decode($res->getBody()->getContents());

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        
});

$app->post(getenv("MIDDLE_URL") . "/unban", function ($request, $response, $arguments) {
    
    /* Check if the user is admin or not */
    if (false === $this->token->decoded->isAdmin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["admin.ban"])) {
        throw new ForbiddenException("Token not allowed to ban users.", 403);
    }


    $body = $request->getParsedBody();

    $uid = $body["uid"];
            
    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "uid" => $uid
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    
            
    $user->status = 6;
    
    // print_r($user->uid);
    // die();
    $this->spot->mapper("App\User")->save($user);
    $userId = $user->id;
    $defaultRole = $this->authManager->getRole('default.user');

    if ($defaultRole === null) {
        throw new NotFoundException("Something wrong with roles. Please contact administrator!");
    }
    $this->authManager->revokeAll($userId);
    $this->authManager->assign($defaultRole, $userId);            

    $data["status"] = "ok";
    $data["message"] = "Talent Status is Updated ";
    
    $client = new Client();
    $res = $client->request('POST', getenv("PUBLISH_ENDPOINT"), ['headers' =>
        [
            'Content-Type' => 'application/json',
            'Authorization' => $request->getHeader('HTTP_AUTHORIZATION')
        ],
        'json' => [
            'is_published' => true,
            'uid' => $uid
        ]

    ]);
    // print_r($res->getBody()->getContents());
    // die();
    $data["tms"] = json_decode($res->getBody()->getContents());

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        
});