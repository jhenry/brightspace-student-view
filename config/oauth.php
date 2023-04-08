<?php
// Access environment through the config helper
// This will avoid issues when using Laravel's config caching
// https://laravel.com/docs/8.x/configuration#configuration-caching
return [
  'appId'             => env('OAUTH_APP_ID', ''),
  'appSecret'         => env('OAUTH_APP_SECRET', ''),
  'redirectUri'       => env('OAUTH_REDIRECT_URI', ''),
  'scopes'            => env('OAUTH_SCOPES', ''),
  'scopeSeparator'            => env('OAUTH_SCOPE_Separator', ''),
  'authorizeEndpoint' => env('OAUTH_AUTHORIZE_ENDPOINT', 'https://auth.brightspace.com/oauth2/auth'),
  'tokenEndpoint'     => env('OAUTH_TOKEN_ENDPOINT', 'https://auth.brightspace.com/core/connect/token'),
];
