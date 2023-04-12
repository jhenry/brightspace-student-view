<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TokenStore\TokenSessionCache;
use App\TokenStore\TokenCacheCache;
use App\Services\BrightspaceService;

class HomeController extends Controller
{
    private $brightspace;

    public function __construct(BrightspaceService $brightspaceService)
    {
        $this->brightspace = $brightspaceService;
    }

  public function welcome()
  {
    $viewData = $this->loadViewData();

    $tokenCacheCache = new TokenCacheCache();
    $viewData['adminWhoami'] = $this->queryUser($tokenCacheCache);

    // If we have a logged in session user, we can test a query as them
    if(session('userName')) {
        $tokenSessionCache = new TokenSessionCache();
        $viewData['userWhoami'] = $this->queryUser($tokenSessionCache);
    }

    return view('welcome', $viewData);
  }

  public function queryUser($tokenCache)
  {
    $accessToken = $tokenCache->getAccessToken();
    // Initialize the OAuth client
    $oauthClient = $this->brightspace->getOauthClient();
    $userArray = $this->brightspace->whoAmI($oauthClient, $accessToken);
    return $userArray["UniqueName"];
  }
}
