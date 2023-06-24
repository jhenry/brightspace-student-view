<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

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

    public function buildRequest($requestPath, $accessToken, $method = 'GET', array $options = [])
    {
        $path = $this->basePath . $requestPath;
        $request = $this->oauthClient->getAuthenticatedRequest(
            $method,
            $path,
            $accessToken,
            $options
        );

        return $request;
    }
    public function sendRequest($request)
    {
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->send($request, ['http_errors' => false]);
        return $response;
    }

    public function doRequest($path, $accessToken, $method='GET', $body = NULL)
    {
        $options = array();
        if (!empty($body)) {
            $options['body'] = json_encode($body);
        }
        $request = $this->buildRequest($path, $accessToken, $method, $options);
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
