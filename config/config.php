<?php

return [
    'id' => 'et-client',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host='.getenv("DB_HOST").';dbname='.getenv("DB_NAME"),
            'username' => getenv("DB_USER"),
            'password' => getenv("DB_PASSWORD"),
            'charset' => 'utf8',
        ],

        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
    ],
];    
