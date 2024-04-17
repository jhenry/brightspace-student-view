<?php
/**
 * Brightspace Service: BrightspaceService request/service class
 * @package BrightspaceService
 * @author Justin Henry <justin.henry@uvm.edu>
 *
 */
namespace App\Services;
use Illuminate\Support\Facades\Log;

/**
 * Support integrations with D2L's Brightspace LMS.
 *
 * This class handles low-level tasks such as OAuth token management and request
 * abstraction. It leverages the API endpoints described here:
 * https://docs.valence.desire2learn.com/index.html
 *
 * @var string $oauthClient Instance of OAuth2 client provider.
 * @todo Convert to package or module for portability between applications.
 */
class BrightspaceService
{
    /**
     * Beginning portion of the URI. Includes targethost, lms product component,
     * and API version.
     *
     * @var string
     */
    public $basePath;

    /**
     * Instance of OAuth2 client provider.
     *
     * @var class
     */
    public $oauthClient;

    /**
     * Build a base path url from targethost, component, and api version.
     * Instantiate oauthClient for use by request methods.
     */
    public function __construct()
    {
        $this->basePath = config('services.lms.base') . config('services.lms.api') . '/' . config('services.lms.lp');

        $this->oauthClient = $this->getOauthClient();
    }
    /**
     * Instantiate an instance of the OAuth2 client library using configuration
     * from .env and config/services.php.
     *
     * @return GenericProvider $oauthClient
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
 * Prepare an authenticated request object.
 *
 * @param mixed $requestPath
 * @param mixed $accessToken
 * @param string $method
 * @param array $options
 * @return mixed $request
 */
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

    /**
     * Send an authenticated request. Set to false to prevent exceptions being
     * thrown for results such as 404, which are handled separately.
     *
     * @param mixed $request
     * @return ResponseInterface $response
     */
    public function sendRequest($request)
    {
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->send($request, ['http_errors' => false]);
        return $response;
    }
/**
 * Prepare, build, and send an authenticated request.
 *
 * @param mixed $path
 * @param mixed $accessToken
 * @param string $method
 * @param mixed $body
 * @return ResponseInterface $response
 */
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
/**
 * Confirm which user is authenticated with the provided token.
 *
 * @param mixed $accessToken
 * @return mixed $userArray
 */
    public function whoAmI($accessToken)
    {
        $response = $this->doRequest('/users/whoami', $accessToken);
        $user = $response->getBody();
        $userArray = json_decode($user, true);
        return $userArray;
    }
}
