<?php

namespace App\TokenStore;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenCacheCache
{
    public function storeTokens($accessToken, $user = null)
    {
        Log::debug("Storing new token with expiry: " . $this->prettyExpiry($accessToken->getExpires()));

        Cache::put('accessToken', $accessToken->getToken());
        Cache::put('refreshToken', $accessToken->getRefreshToken());
        Cache::put('tokenExpires', $accessToken->getExpires());

        Log::debug("Cached token expiry is now: " . $this->prettyExpiry(cache('tokenExpires')));
    }

    public function clearTokens()
    {
        cache()->forget('accessToken');
        cache()->forget('refreshToken');
        cache()->forget('tokenExpires');
        cache()->forget('userName');
        cache()->forget('userIdentifier');
    }

    public function getAccessToken()
    {
        // Check if tokens exist
        if (
            empty(cache('accessToken')) ||
            empty(cache('refreshToken')) ||
            empty(cache('tokenExpires'))
        ) {
            Log::warning("Token cache is empty.");
            return '';
        }

        // Check if token is expired (or within 5 mins of expiring)
        $now = time() + 300;
        if (cache('tokenExpires') <= $now) {
            // Token is expired (or very close to it) so let's refresh
            Log::debug('Refreshing expired token with expiry of: ' . $this->prettyExpiry(cache('tokenExpires')));
            $this->refreshAccessToken();
        }

        // Token is still valid, just return it
        return cache('accessToken');
    }

    public function updateTokens($accessToken)
    {
        Log::debug("Updating new token with exp: " . $this->prettyExpiry($accessToken->getExpires()));

        Cache::put('accessToken', $accessToken->getToken());
        Cache::put('refreshToken', $accessToken->getRefreshToken());
        Cache::put('tokenExpires', $accessToken->getExpires());

        Log::debug("Cached token exp is now: " . $this->prettyExpiry(cache('tokenExpires')));
    }

    public function refreshAccessToken()
    {
        Log::debug('Initiating token refresh for token with expiry of: ' . $this->prettyExpiry(cache('tokenExpires')));

        // Initialize the OAuth client
        $oauthClient = $this->getOauthClient();

        try {
            $newToken = $oauthClient->getAccessToken('refresh_token', [
                'refresh_token' => cache('refreshToken')
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

    public function prettyExpiry($tokenExpires)
    {
        if (is_int($tokenExpires)) {
            return date('Y-m-d H:i:s', $tokenExpires);
        } else {
            return false;
        }
    }
}
