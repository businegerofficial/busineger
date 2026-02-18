<?php

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// init configuration
$clientID = '577134171328-ha3bnmmc4kcoriuilil1dgulmh374msk.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/AIprompt/welcome.php';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
