<?php

namespace App\Services;

class BrightspaceService
{

    public $basePath;
    public $oauthClient;

    public function __construct()
    {
        $this->basePath = config('services.lms.base') . config('services.lms.api') . '/' . config('services.lms.lp');

        $this->oauthClient = $this->getOauthClient();
    }

    public function getOauthClient()
    {
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

    public function buildRequest($requestPath, $accessToken,  $method = 'GET')
    {
        $request = $this->oauthClient->getAuthenticatedRequest(
            $method,
            $this->basePath . $requestPath,
            $accessToken
        );

        return $request;
    }
    public function sendRequest($request)
    {
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->send($request);

        return $response;
    }

    public function doRequest($path, $accessToken, $method='GET')
    {
        $request = $this->buildRequest($path, $accessToken, $method);
        $response = $this->sendRequest($request);
        return $response;
    }

    public function whoAmI($accessToken)
    {
        $response = $this->doRequest('/users/whoami', $accessToken);
        $user = $response->getBody();
        $userArray = json_decode($user, true);
        return $userArray;
    }
}
