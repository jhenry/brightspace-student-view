<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\TokenStore\TokenCache;
use App\TokenStore\TokenCacheCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
  public function welcome()
  {
    $viewData = $this->loadViewData();
    $tokenCache = new TokenCacheCache();

    Log::debug("cached token epiry: " . $tokenCache->prettyExpiry(cache('tokenExpires')));

    $accessToken = $tokenCache->getAccessToken();
    // Initialize the OAuth client
    $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => config('oauth.appId'),
        'clientSecret'            => config('oauth.appSecret'),
        'redirectUri'             => config('oauth.redirectUri'),
        'urlAuthorize'            => config('oauth.authorizeEndpoint'),
        'urlAccessToken'          => config('oauth.tokenEndpoint'),
        'urlResourceOwnerDetails' => '',
        'scopes'                  => config('oauth.scopes'),
        'scopeSeparator'                  => config('oauth.scopeSeparator')
      ]);

    $basePath = config('services.lms.base') . config('services.lms.api') . '/' . config('services.lms.lp') ;
        $request = $oauthClient->getAuthenticatedRequest(
            'GET',
            $basePath . '/users/whoami',
            $accessToken
        );

        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->send($request);

        $user = $response->getBody();
        $viewData['whoami'] = $user;

    return view('welcome', $viewData);
  }
}
