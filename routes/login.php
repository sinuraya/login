<?php

use App\User;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Tuupola\Base62;
use Exception\NotFoundException;
use Exception\ForbiddenException;
use PHPMailer\PHPMailer\PHPMailer;
// use PhpAmqpLib\Connection\AMQPStreamConnection;
// use PhpAmqpLib\Message\AMQPMessage;

$app->post(getenv("MIDDLE_URL") . "/login", function ($request, $response, $arguments) {

    $now = new DateTime();
    $future = new DateTime("now +2 day");
    $server = $request->getServerParams();
    $username = $server["PHP_AUTH_USER"];
    $password = $server["PHP_AUTH_PW"];

    if (false === $user = $this->spot->mapper("App\User")->where(["username" => $username, "status >" => 0])->first()) {
        throw new ForbiddenException("Your credential information is invalid.", 403);
    }

    if (!$user->login($username, $password)) {
        throw new ForbiddenException("Your credential information is invalid.", 403);
    }

    $arrPermissions =  $this->authManager->getPermissionsByUser($user->id);

    $scopes = array();

    /* get all permission name */
    foreach ($arrPermissions as $permission) {
        $scopes[] = $permission->name;
    }


    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti,
        "sub" => $username,
        "isAdmin" => false,
        "apptype" => "talent",
        "uid" => $user->uid,
        "scope" => $scopes
    ];

    $secret = getenv("JWT_SECRET");
    $token = JWT::encode($payload, $secret, "HS256");
    $data["status"] = "ok";
    $data["token"] = $token;

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("MIDDLE_URL") . "/register", function ($request, $response, $arguments) {

    $now = new DateTime();
    $future = new DateTime("now +2 day");

    $body = $request->getParsedBody();
    
    $arrUser = array();
    $arrUser["username"] = $body["username"];
    
    $arrUser["password_hash"] = password_hash($body["password"], PASSWORD_DEFAULT);
    $arrUser["email"] = $body["username"];
    $arrUser["picture"] = "";
    
    $referred_by = "";
    
    if (!empty($body["refferal"])) {
        $referred_by = $body["referral"];
    }
    
    $user = new User($arrUser);

    $userId = $this->spot->mapper("App\User")->insert($user);

    if (!$userId) {
        throw new ForbiddenException("Your credential information is invalid. User is already registered", 403);
    }

    // //Create a new PHPMailer instance
    // $mail = new PHPMailer;
    // //Tell PHPMailer to use SMTP
    // $mail->isSMTP();
    // //Enable SMTP debugging
    // // 0 = off (for production use)
    // // 1 = client messages
    // // 2 = client and server messages
    // $mail->SMTPDebug = 0;
    // //Set the hostname of the mail server
    // $mail->Host = getenv("MAIL_SMTP");
    // // use
    // // $mail->Host = gethostbyname('smtp.gmail.com');
    // // if your network does not support SMTP over IPv6
    // //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    // $mail->Port = getenv("MAIL_SMTP_PORT");
    // //Set the encryption system to use - ssl (deprecated) or tls
    // $mail->SMTPSecure = getenv("MAIL_SMTP_TYPE");
    // //Whether to use SMTP authentication
    // $mail->SMTPAuth = true;
    // //Username to use for SMTP authentication - use full email address for gmail
    // $mail->Username = getenv("MAIL_USER");
    // //Password to use for SMTP authentication
    // $mail->Password = getenv("MAIL_PASSWORD");
    // //Set who the message is to be sent from
    // $mail->setFrom(getenv("MAIL_USER"), getenv("MAIL_USER"));
    // //Set an alternative reply-to address
    // $mail->addReplyTo(getenv("MAIL_USER"), getenv("MAIL_USER"));
    // //Set who the message is to be sent to
    // $mail->addAddress($user->email, $user->username);
    // //Set the subject line
    // $mail->isHTML(true);
    // $mail->Subject = 'Eventalent Registration Status';
    // $mail->Body = "Please click link below to activate your account <br/> \n<a href=\"".getenv("CONFIRM_URL").$user->auth_key
    //         . "\">".getenv("CONFIRM_URL").$user->auth_key."</a>";
    // //Read an HTML message body from an external file, convert referenced images to embedded,
    // //convert HTML into a basic plain-text alternative body
    // // $mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
    // // //Replace the plain text body with one created manually
    // // $mail->AltBody = 'This is a plain-text message body';
    // // //Attach an image file
    // // $mail->addAttachment('images/phpmailer_mini.png');

    // //send the message, check for errors
    // if (!$mail->send()) {
    //     echo "Mailer Error: " . $mail->ErrorInfo;
    // } else {
    //     echo "Message sent!";
    //     //Section 2: IMAP
    //     //Uncomment these to save your message in the 'Sent Mail' folder.
    //     #if (save_mail($mail)) {
    //     #    echo "Message saved!";
    //     #}
    // }
    //set roles here
    // uncomment this to set unactivated user role
    //$defaultRole = $this->authManager->getRole('non-active.user');

    $defaultRole = $this->authManager->getRole('default.user');

    if ($defaultRole === null) {
        throw new NotFoundException("Something wrong with roles. Please contact administrator!");
    }

    $this->authManager->assign($defaultRole, $userId);

    $arrPermissions =  $this->authManager->getPermissionsByUser($user->id);

    $scopes = array();

    /* get all permission name */
    foreach ($arrPermissions as $permission) {
        $scopes[] = $permission->name;
    }


    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti,
        "sub" => $user->username,
        "isAdmin" => false,
        "apptype" => "talent",
        "uid" => $user->uid,
        "scope" => $scopes
    ];

    $secret = getenv("JWT_SECRET");
    $token = JWT::encode($payload, $secret, "HS256");
    $data["status"] = "ok";
    $data["token"] = $token;

    //=============================================================

    // $exchange_name = getenv("AMQP_EXCHANGE_NAME");

    // $connection = new AMQPStreamConnection(getenv("AMQP_HOST"), getenv("AMQP_PORT"), getenv("AMQP_USER"), getenv("AMQP_PWD"));

    // $channel = $connection->channel();

    // $channel->exchange_declare($exchange_name, 'fanout', false, false, false);

    // $msg = new AMQPMessage($token);

    // $channel->basic_publish($msg, $exchange_name);

    // $this->logger->addInfo(" [x] Sent $token \n");

    // $channel->close();
    // $connection->close();



    //=============================================================

    // call create user in talent management service
    // $client = new Client();
    // $client->request('POST', getenv("TMS_ENDPOINT"), ['headers' =>
    //     [
    //         'Content-Type' => 'application/json',
    //         'Authorization' => 'Bearer ' . $token
    //     ],
    //     'json' => [
    //         'referred_by' => $referred_by
    //     ]

    // ]);
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("MIDDLE_URL") . "/forgot", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();
        
    $email = $body["email"];

    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "username" => $email
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    

    $user->password_reset_token = (new Base62)->encode(random_bytes(32));
    $this->spot->mapper("App\User")->save($user);            

    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {$this->logger->addInfo("debug level $level; message: $str");};
    //Set the hostname of the mail server
    $mail->Host = getenv("MAIL_SMTP");
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6
    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    $mail->Port = getenv("MAIL_SMTP_PORT");
    //Set the encryption system to use - ssl (deprecated) or tls
    $mail->SMTPSecure = getenv("MAIL_SMTP_TYPE");
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = getenv("MAIL_USER");
    //Password to use for SMTP authentication
    $mail->Password = getenv("MAIL_PASSWORD");
    //Set who the message is to be sent from
    $mail->setFrom(getenv("MAIL_USER"), getenv("MAIL_USER"));
    //Set an alternative reply-to address
    $mail->addReplyTo(getenv("MAIL_USER"), getenv("MAIL_USER"));
    //Set who the message is to be sent to
    $mail->addAddress($user->email, $user->username);
    //Set the subject line
    $mail->isHTML(true);
    $mail->Subject = 'Change your Eventalent password.';
    $mail->Body = "Please click link below to change your password <br/> \n<a href=\"".getenv("RECOVER_URL").$user->password_reset_token
            . "\">".getenv("RECOVER_URL").$user->password_reset_token."</a>";
    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    // $mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
    // //Replace the plain text body with one created manually
    // $mail->AltBody = 'This is a plain-text message body';
    // //Attach an image file
    // $mail->addAttachment('images/phpmailer_mini.png');

    //send the message, check for errors
    if (!$mail->send()) {
        $this->logger->addInfo("Failed to send mail. E-mail address: $email\n");

        $data["status"] = "error";
        $data["message"] = "Something wrong about sending e-mail, please contact administrator!";
                       
        return $response->withStatus(500)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    
        // echo "Mailer Error: " . $mail->ErrorInfo;
    } else {
        $this->logger->addInfo("Mail has been sent. E-mail address: $email\n");

        $data["status"] = "ok";
        $data["message"] = "An instruction to change your password has been sent to your e-mail account.";
                       
        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // echo "Message sent!";
        //Section 2: IMAP
        //Uncomment these to save your message in the 'Sent Mail' folder.
        #if (save_mail($mail)) {
        #    echo "Message saved!";
        #}
    }
});

$app->post(getenv("MIDDLE_URL") . "/reset", function ($request, $response, $arguments) {
        
    $body = $request->getParsedBody();
            
    $token = $body["token"];
    $password = $body["password"];
            
    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "password_reset_token" => $token
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    
            
    $user->password_hash = password_hash($body["password"], PASSWORD_DEFAULT);
    $user->password_reset_token = "";
            
    $this->spot->mapper("App\User")->save($user);            

    $data["status"] = "ok";
    $data["message"] = "Your password has been changed successfully ";
                   
    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        
});
        
$app->post(getenv("MIDDLE_URL") . "/activate", function ($request, $response, $arguments) {
    
    $body = $request->getParsedBody();
            
    $token = $body["token"];
            
    /* Load existing user using provided e-mail */
    if (false === $user = $this->spot->mapper("App\User")->first([
            "auth_key" => $token
    ])) {
        throw new NotFoundException("User not found.", 404);
    };    
            
    $user->status = 6;
            
    $this->spot->mapper("App\User")->save($user);            

    $defaultRole = $this->authManager->getRole('default.user');
    
    if ($defaultRole === null) {
        throw new NotFoundException("Something wrong with roles. Please contact administrator!");
    }
    
    $this->authManager->assign($defaultRole, $user->id);
    

    $data["status"] = "ok";
    $data["message"] = "Your account has been activated";
                
    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    
});

$app->post(getenv("MIDDLE_URL") . "/refresh", function ($request, $response, $arguments) {

    $now = new DateTime();
    $future = new DateTime("now +2 day");

    /* Check if token has needed scope */
    if (empty($this->token->decoded)) {
        throw new ForbiddenException("Invalid old token!", 403);
    }

    $iat = $this->token->decoded->iat;
    $jti = $this->token->decoded->jti;
    $username = $this->token->decoded->sub;
    $uid = $this->token->decoded->uid;
    $scopes = $this->token->decoded->scope;

    $payload = [
        "iat" => $iat,
        "exp" => $future->getTimeStamp(),
        "jti" => $jti,
        "sub" => $username,
        "isAdmin" => false,
        "apptype" => "talent",
        "uid" => $uid,
        "scope" => $scopes
    ];

    $secret = getenv("JWT_SECRET");
    $token = JWT::encode($payload, $secret, "HS256");
    $data["status"] = "ok";
    $data["token"] = $token;

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


/* This is just for debugging, not usefull in real life. */
$app->get(getenv("MIDDLE_URL") . "/dump", function ($request, $response, $arguments) {
    print_r($this->token);
});

$app->post(getenv("MIDDLE_URL") . "/dump", function ($request, $response, $arguments) {
    print_r($this->token);
});
