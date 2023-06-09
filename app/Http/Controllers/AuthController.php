<?php

namespace App\Http\Controllers;

use App\TokenStore\TokenSessionCache;
use App\TokenStore\TokenCacheCache;
use App\Services\BrightspaceService;
use Browser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $brightspace;

    public function __construct(BrightspaceService $brightspaceService)
    {
        $this->brightspace = $brightspaceService;
    }
    public function signin(Request $request)
    {
        // Client check to allow re-launching in new window in Safari/FF
	// This gets around lack of same-site=lax defaults in those browsers
        if(Browser::isSafari() || Browser::isFirefox()) {
            if(empty($request->popout)) {
                return redirect('/popout?ou=' . $request->ou);
            }
        }

        // Initialize the OAuth client
        $oauthClient = $this->brightspace->getOauthClient();

        $authUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in callback
        session(['oauthState' => $oauthClient->getState()]);

        if( !empty($request->ou) ) {
            session(['orgUnitId' => $request->ou]);
        }

        // Redirect to AAD signin page
        return redirect()->away($authUrl);
    }
    public function signout()
    {
        $tokenCache = new TokenSessionCache();
        $tokenCache->clearTokens();
        return redirect('/');
    }

    public function callback(Request $request)
    {

        $this->validateState($request);

        // Authorization code should be in the "code" query param
        $authCode = $request->query('code');
        if (isset($authCode)) {
            // Initialize the OAuth client
            $oauthClient = $this->brightspace->getOauthClient();

            try {
                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);

                $userArray = $this->brightspace->whoAmI($accessToken);
                $userName = $userArray['UniqueName'];
                $apiUserName = config('services.lms.api_service_user');

                // If the authenticated user matches the api services account
                // specified in application configs, then we want to use the
                // Cache to store the tokens so that the application can make
                // calls as needed for that user.
                if ($userName == $apiUserName) {
                    $tokenCache = new TokenCacheCache();
                } else {
                    // Otherwise, we'll use session storage
                    $tokenCache = new TokenSessionCache();
                }

                $tokenCache->storeTokens($accessToken, $userArray);

                return redirect('/');
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                $errors = json_encode($e->getResponseBody());
                Log::error('Error requesting access token: ' . $errors);

                return redirect('/')
                    ->with('error', 'Error requesting access token')
                    ->with('errorDetail', $errors);
            }
        }

        return redirect('/')
            ->with('error', $request->query('error'))
            ->with('errorDetail', $request->query('error_description'));
    }

    private function validateState($request)
    {
        // Validate state
        $expectedState = session('oauthState');
        $request->session()->forget('oauthState');
        $providedState = $request->query('state');

        if (!isset($expectedState)) {
            // If there is no expected state in the session,
            // do nothing and redirect to the home page.
            return redirect('/');
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            return redirect('/')
                ->with('error', 'Invalid auth state')
                ->with('errorDetail', 'The provided auth state did not match the expected value');
        }
    }
}
