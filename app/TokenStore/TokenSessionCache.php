<?php

namespace App\TokenStore;

use Illuminate\Support\Facades\Log;

class TokenSessionCache
{
    public function storeTokens($accessToken, $user)
    {

        session([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'tokenExpires' => $accessToken->getExpires(),
            'userName' => $user["UniqueName"],
            'userIdentifier' => $user["Identifier"]
        ]);
    }

    public function clearTokens()
    {
        session()->forget('accessToken');
        session()->forget('refreshToken');
        session()->forget('tokenExpires');
        session()->forget('userName');
        session()->forget('userIdentifier');
    }

    public function getAccessToken()
    {
        // Check if tokens exist
        if (
            empty(session('accessToken')) ||
            empty(session('refreshToken')) ||
            empty(session('tokenExpires'))
        ) {
            return '';
        }

        // Check if token is expired or within 5 mins of expiry
        $now = time() + 300;
        if (session('tokenExpires') <= $now) {
            // Token is expired (or very close to it) so let's refresh
            $this->refreshAccessToken();
        }

        // Token is still valid, just return it
        return session('accessToken');
    }

    public function refreshAccessToken()
    {
        // Initialize the OAuth client
        $oauthClient = $this->getOauthClient();

        try {
            $newToken = $oauthClient->getAccessToken('refresh_token', [
                'refresh_token' => session('refreshToken')
            ]);

            // Store the new values
            $this->updateTokens($newToken);

            return $newToken->getToken();
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            $error = $e->getResponseBody();
            Log::debug('Caught exception during refresh: ' . $error['error'] . ' - ' . $error['error_description']);

            return '';
        }
    }

    public function updateTokens($accessToken)
    {
        session([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'tokenExpires' => $accessToken->getExpires()
        ]);
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
}