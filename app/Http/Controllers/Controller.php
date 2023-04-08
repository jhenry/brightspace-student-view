<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function loadViewData()
    {
        $viewData = [];

        // Check for flash errors
        if (session('error')) {
            $viewData['error'] = session('error');
            $viewData['errorDetail'] = session('errorDetail');
        }

        // Check for logged on user
        if (session('userName')) {
            $viewData['userName'] = session('userName');
            $viewData['userIdentifier'] = session('userIdentifier');
            //$viewData['tokenExpires'] = session('tokenExpires');
        }

        return $viewData;
    }

    public function getOauthClient() {
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('oauth.appId'),
            'clientSecret'            => config('oauth.appSecret'),
            'redirectUri'             => config('oauth.redirectUri'),
            'urlAuthorize'            => config('oauth.authorizeEndpoint'),
            'urlAccessToken'          => config('oauth.tokenEndpoint'),
            'urlResourceOwnerDetails' => '',
            'scopes'                  => config('oauth.scopes'),
            'scopeSeparator'          => config('oauth.scopeSeparator')
        ]);

        return $oauthClient;
    }


    public function whoAmI($oauthClient, $accessToken) {
        $basePath = config('services.lms.base') . config('services.lms.api') . '/' . config('services.lms.lp');
        $request = $oauthClient->getAuthenticatedRequest(
            'GET',
            $basePath . '/users/whoami',
            $accessToken
        );

        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->send($request);

        $user = $response->getBody();

        $userArray = json_decode($user, true);

        return $userArray;
    }

}
