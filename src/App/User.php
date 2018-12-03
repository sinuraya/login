<?php

namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;
use Tuupola\Base62;
use Psr\Log\LogLevel;

class User extends \Spot\Entity
{
    protected static $table = "user";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "uid" => ["type" => "string", "length" => 16, "unique" => true],
            "username" => ["type" => "string", "length" => 255, "unique" => true, "required" => true],
            "auth_key" => ["type" => "string", "length" => 255, "required" => true],
            "password_hash" => ["type" => "string", "length" => 255, "required" => true],
            "password_reset_token" => ["type" => "string", "length" => 255],
            "email" => ["type" => "string", "length" => 255, "unique" => true, "required" => true],
            "picture" => ["type" => "string", "length" => 255],
            "status" => ["type" => "smallint", "length" => 6, "required" => true],
            "created_at"   => ["type" => "datetime", "value" => new \DateTime(), "required" => true],
            "updated_at"   => ["type" => "datetime", "value" => new \DateTime(), "required" => true]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->uid = (new Base62)->encode(random_bytes(9));
        });

        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->auth_key = (new Base62)->encode(random_bytes(32));
        });
        // 0 = deleted
        // 1 = non active
        // 2 = soft banned
        // 6 = normal
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->status = 6;
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->updated_at = new \DateTime();
        });
    }
    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function etag()
    {
        return md5($this->username . $this->timestamp());
    }

    public function login($username, $password)
    {
        $verified = false;

        if ($this->username == $username) {
            $verified = password_verify($password, $this->password_hash);
        }
        return $verified;
    }

    public function clear()
    {
        $this->data([
            "uid" => null,
            "username" => null,
            "auth_key" => null,
            "password_hash" => null,
            "password_reset_token" => null,
            "email" => null,
            "status" => 0,
            "picture" => null
            

        ]);
    }
}
