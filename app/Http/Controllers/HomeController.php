<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TokenStore\TokenSessionCache;
use App\TokenStore\TokenCacheCache;

class HomeController extends Controller
{
  public function welcome()
  {
    $viewData = $this->loadViewData();

    $tokenCacheCache = new TokenCacheCache();
    $viewData['adminWhoami'] = $this->queryUser($tokenCacheCache);

    $tokenSessionCache = new TokenSessionCache();
    $viewData['userWhoami'] = $this->queryUser($tokenSessionCache);

    return view('welcome', $viewData);
  }

  public function queryUser($tokenCache)
  {
    $accessToken = $tokenCache->getAccessToken();
    // Initialize the OAuth client
    $oauthClient = $this->getOauthClient();
    $userArray = $this->whoAmI($oauthClient, $accessToken);
    return $userArray["UniqueName"];
  }
}
