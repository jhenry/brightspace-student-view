<?php

/**
 * Token Cache Storage: TokenCacheCache token management class
 * @package TokenCacheCache
 * @author Justin Henry <justin.henry@uvm.edu>
 *
 */

namespace App\TokenStore;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * File based token caching for OAuth API services
 *
 * This class handles OAuth2 token storage and caching. Tokens are stored and
 * updated using application file storage during refresh and renewal.
 *
 */
class TokenCacheCache
{
    /**
     * Write tokens to cache.
     *
     * @param mixed $accessToken
     * @param mixed $user
     *
     */
    public function storeTokens($accessToken, $user = null)
    {
        Log::debug("Storing new token with expiry: " . $this->prettyExpiry($accessToken->getExpires()));

        Cache::put('accessToken', $accessToken->getToken());
        Cache::put('refreshToken', $accessToken->getRefreshToken());
        Cache::put('tokenExpires', $accessToken->getExpires());

        Log::debug("Cached token expiry is now: " . $this->prettyExpiry(cache('tokenExpires')));
    }

    /**
     * Delete/destroy any existing tokens.
     *
     */
    public function clearTokens()
    {
        cache()->forget('accessToken');
        cache()->forget('refreshToken');
        cache()->forget('tokenExpires');
        cache()->forget('userName');
        cache()->forget('userIdentifier');
    }

    /**
     * Retrieve an access token from the cache, refreshing if it's stale and/or
     * expiring.
     *
     */
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

    /**
     * Write fresh tokens to cache.
     *
     * @param mixed $accessToken
     *
     */
    public function updateTokens($accessToken)
    {
        Log::debug("Updating new token with exp: " . $this->prettyExpiry($accessToken->getExpires()));

        Cache::put('accessToken', $accessToken->getToken());
        Cache::put('refreshToken', $accessToken->getRefreshToken());
        Cache::put('tokenExpires', $accessToken->getExpires());

        Log::debug("Cached token exp is now: " . $this->prettyExpiry(cache('tokenExpires')));
    }

    /**
     * Retrieve a new access token from the API service by making a request that
     * includes the currently cached refresh token. Returns string containing
     * the token to be cached, or an empty string if refresh failed.
     *
     * @return string
     * @todo move to shared abstraction.
     */
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

    /**
     * Initiate a new OAuth client.
     *
     * @return GenericProvider Generic OAuth2 client provider instance.
     * @todo move to shared abstraction.
     */
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

    /**
     * Format an expiration date/time stamp into a more easily readable format
     * for logging and debugging.
     *
     * @param int $tokenExpires
     * @return string|bool
     */
    public function prettyExpiry($tokenExpires)
    {
        if (is_int($tokenExpires)) {
            return date('Y-m-d H:i:s', $tokenExpires);
        } else {
            return false;
        }
    }
}
