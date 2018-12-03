<?php

use App\User;
use App\UserTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use yii\log\Logger;

$app->get(getenv("MIDDLE_URL") . "/users", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    // if (false === $this->token->decoded->isAdmin) {
    //     throw new ForbiddenException("Token is not an admin token.", 403);
    // }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["talent.list", "talent.all"])) {
        throw new ForbiddenException("Token not allowed to list users.", 403);
    }

    $arrOrderBy = ['user' => 'username', 
                'email' => 'email',
                'created' => 'created_at',
                'updated' => 'updated_at'];
    $arrDirection = [0 => 'DESC', 1 => 'ASC'];

    /*
        parameter:
        - p : Page
        - ps : Page Size, default 10 records
        - ob : Order By, default order by created_at field
        - d : Order Direction, default DESC
    */
    $orderBy = $arrOrderBy[$request->getQueryParam("ob", "created")];
    $direction = $arrDirection[$request->getQueryParam("d", 1)];
    $pageSize = $request->getQueryParam("ps", 10);

    $users = $this->spot->mapper("App\User")
    ->all()
    ->order([$orderBy => $direction]);

    $recordCount = $users->count();

    $pageCount = ceil($recordCount / $pageSize);

    $blnPagination = false;

    $page = $request->getQueryParam("p");
    // if ($page <= 0 ) { 
    //     $page = 1;
    // } else if ($page >= $pageCount)  {
    //     $page = $pageCount;
    // } 
    if (is_numeric($page)) {
        $blnPagination = true;
    }

    $page = ($page <= 0) ? 1 : (($page >= $pageCount) ? $pageCount : $page);
    $offset = (($page - 1) <= 0 ? 0 : ($page - 1)) * $pageSize;



    if ($blnPagination) {
        /* Use ETag and date from User with most recent update. */
        $first = $this->spot->mapper("App\User")
            ->all()
            ->order([$orderBy => $direction])
            ->offset($offset)
            ->limit($pageSize)
            ->first();
    } else {
        /* Use ETag and date from User with most recent update. */
        $first = $this->spot->mapper("App\User")
            ->all()
            ->order([$orderBy => $direction])
            ->first();
    }

    /* Add Last-Modified and ETag headers to response when atleast on user exists. */
    if ($first) {
        // throw new ForbiddenException("ETag : " . $first->timestamp(), 403);
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
    }


    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    $meta = ['curentPage' => $page,
            'prevPage' => (($page - 1) <= 0) ? 1 : ($page - 1),
            'nextPage' => (($page + 1) >= $pageCount) ? $pageCount : ($page + 1),
            'pageSize' => $pageSize,
            'pageCount' => $pageCount,
            'orderBy' => $orderBy . ' ' . $direction ];

    // $this->logger->addInfo("Jumlah record: ". $recordCount);
    
    if ($blnPagination) {
        $users =  $users->offset($offset)->limit($pageSize);
    }

        
    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $userTransformer = new UserTransformer();
    $userTransformer->setMiddleUrl(getenv("MIDDLE_URL"));
    $resource = new Collection($users, $userTransformer);
    $data = $fractal->createData($resource)->toArray();
    if ($blnPagination) {
        $data['meta'] = $meta;
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("MIDDLE_URL") . "/users", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    if (false === $this->token->decoded->isAdmin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["talent.add"])) {
        throw new ForbiddenException("Token not allowed to add users.", 403);
    }


    $body = $request->getParsedBody();
    $body["password_hash"] = password_hash($body["password_hash"], PASSWORD_DEFAULT);

    $user = new User($body);
    $this->spot->mapper("App\User")->save($user);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $userTransformer = new UserTransformer();
    $userTransformer->setMiddleUrl(getenv("MIDDLE_URL"));
    $resource = new Item($user, $userTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New user created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->withHeader("Location", $data["data"]["links"]["self"])
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("MIDDLE_URL") . "/users/{id}", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    // if (false === $this->token->decoded->isAdmin) {
    //     throw new ForbiddenException("Token is not an admin token.", 403);
    // }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["talent.read"])) {
        throw new ForbiddenException("Token not allowed to view users.", 403);
    }

    /* Load existing user using provided id */
    if (false === $user = $this->spot->mapper("App\User")->first([
        "uid" => $arguments["id"]
    ])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    // if ($this->cache->isNotModified($request, $response)) {
    //     return $response->withStatus(304);
    // }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $userTransformer = new UserTransformer();
    $userTransformer->setMiddleUrl(getenv("MIDDLE_URL"));
    $resource = new Item($user, $userTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("MIDDLE_URL") . "/users/{id}", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    if (false === $this->token->decoded->admin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["user.update"])) {
        throw new ForbiddenException("Token not allowed to update users.", 403);
    }

    /* Load existing user using provided id */
    if (false === $user = $this->spot->mapper("App\User")->first([
        "id" => $arguments["id"]
    ])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the user respond with 412 Precondition Failed. */
    // if (false === $this->cache->hasCurrentState($request, $user->etag(), $user->timestamp())) {
    //     throw new PreconditionFailedException("User has been modified.", 412);
    // }

    $body = $request->getParsedBody();
    $user->data($body);
    $this->spot->mapper("App\User")->save($user);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $userTransformer = new UserTransformer();
    $userTransformer->setMiddleUrl(getenv("MIDDLE_URL"));
    $resource = new Item($user, $userTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "User updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("MIDDLE_URL") . "/users/{id}", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    if (false === $this->token->decoded->admin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["talent.update"])) {
        throw new ForbiddenException("Token not allowed to update users.", 403);
    }

    /* Load existing user using provided id */
    if (false === $user = $this->spot->mapper("App\User")->first([
        "id" => $arguments["id"]
    ])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the user respond with 412 Precondition Failed. */
    // if (false === $this->cache->hasCurrentState($request, $user->etag(), $user->timestamp())) {
    //     throw new PreconditionFailedException("User has been modified.", 412);
    // }

    $body = $request->getParsedBody();
    $body["password_hash"] = password_hash($body["password_hash"], PASSWORD_DEFAULT);

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the user object first. */
    $user->clear();

    $user->data($body);
    $this->spot->mapper("App\User")->save($user);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $userTransformer = new UserTransformer();
    $userTransformer->setMiddleUrl(getenv("MIDDLE_URL"));
    $resource = new Item($user, $userTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "User updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("MIDDLE_URL") . "/users/{id}", function ($request, $response, $arguments) {

    /* Check if the user is admin or not */
    if (false === $this->token->decoded->admin) {
        throw new ForbiddenException("Token is not an admin token.", 403);
    }

    /* Check if token has needed scope */
    if (false === $this->token->hasScope(["talent.delete"])) {
        throw new ForbiddenException("Token not allowed to delete users.", 403);
    }

    /* Load existing user using provided uid */
    if (false === $user = $this->spot->mapper("App\User")->first([
        "id" => $arguments["id"]
    ])) {
        throw new NotFoundException("User not found.", 404);
    };

    $this->spot->mapper("App\User")->delete($user);

    $data["status"] = "ok";
    $data["message"] = "User deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
