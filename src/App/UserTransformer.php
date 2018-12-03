<?php

namespace App;

use App\User;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{
    private $middleUrl = "";

    public function setMiddleUrl($url) {
        $this->middleUrl = $url;
    }

    public function transform(User $user)
    {
        return [
            "uid" => (string) $user->uid,
            "username" => (string) $user->username,
            "auth_key" => (string) $user->auth_key,
            "password_hash" => (string) $user->password_hash,
            "password_reset_token" => (string) $user->password_reset_token,
            "email" => (string) $user->email,
            "picture" => (string) $user->picture,
            "status" => (integer) $user->status,
            "created_at"   => $user->created_at,
            "links" => [
                "self" => $this->middleUrl . "/users/{$user->id}"
            ]
        ];
    }
}
