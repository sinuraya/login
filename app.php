<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

date_default_timezone_set("UTC");

require __DIR__ . "/vendor/autoload.php";
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true,
    ]
]);

require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";
$yiiConfig = require(__DIR__ . '/config/config.php');
new yii\web\Application($yiiConfig); // Do NOT call run() here


$app->get("/", function ($request, $response, $arguments) {
    print "Here be dragons";
});

require __DIR__ . "/routes/login.php";
require __DIR__ . "/routes/user.php";
require __DIR__ . "/routes/status.php";

$app->run();
