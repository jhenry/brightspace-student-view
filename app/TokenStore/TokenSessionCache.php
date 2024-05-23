<?php

/**
 * Token Session Cache Storage: TokenSessionCache token management class
 * @package TokenSessionCache
 * @author Justin Henry <justin.henry@uvm.edu>
 *
 */

namespace App\TokenStore;

use Illuminate\Support\Facades\Log;

/**
 * Session based token caching for OAuth API services
 *
 * This class handles OAuth2 token storage and caching. Tokens are stored and
 * updated using application session storage during refresh and renewal.
 *
 */
class TokenSessionCache
{
    /**
     * Write tokens to session, along with the username and identifier.
     *
     * @param mixed $accessToken
     * @param mixed $user
     */
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

    /**
     * Delete/destroy tokens stored in session, along with username and id.
     *
     */
    public function clearTokens()
    {
        session()->forget('accessToken');
        session()->forget('refreshToken');
        session()->forget('tokenExpires');
        session()->forget('userName');
        session()->forget('userIdentifier');
    }

    /**
     * Retrieve access tokens.  If there are none, return a blank string. If
     * they are expired or about to expire, then initiate a refresh so we are
     * not passing back stale tokens.
     *
     * @return string|mixed
     */
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

    /**
     * Write fresh tokens to cache.
     *
     * @param mixed $accessToken
     */
    public function updateTokens($accessToken)
    {
        session([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'tokenExpires' => $accessToken->getExpires()
        ]);
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
}
