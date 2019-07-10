<?php

require __DIR__ . '/../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// Replace these with your token settings
// Create a project at https://console.developers.google.com/
$clientId     = '191964231097-m0886rm007magvag1bfu1gq23u9pcntv.apps.googleusercontent.com';
$clientSecret = '7l2CfB1yBJrgqNRrM0y1cvGL';

// Change this if you are not using the built-in PHP server
$redirectUri  = 'https://it.pf.dk/fagprojekt/examples';

// Start the session
session_start();

// Initialize the provider
$provider = new Google(compact('clientId', 'clientSecret', 'redirectUri'));

// No HTML for demo, prevents any attempt at XSS
header('Content-Type', 'text/plain');

return $provider;
