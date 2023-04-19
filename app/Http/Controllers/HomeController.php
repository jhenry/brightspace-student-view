<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TokenStore\TokenSessionCache;
use App\TokenStore\TokenCacheCache;
use App\Services\BrightspaceService;
use App\Services\StudentViewService;

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
        if (session('userName')) {
            $tokenSessionCache = new TokenSessionCache();
            $viewData['userWhoami'] = $this->queryUser($tokenSessionCache);
        }


        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        //$studentView->createUser();
        //$studentView->createEnrollment();
        //$studentView->deleteEnrollment();


        return view('welcome', $viewData);
    }

    public function queryUser($tokenCache)
    {
        $accessToken = $tokenCache->getAccessToken();
        // Initialize the OAuth client
        $userArray = $this->brightspace->whoAmI($accessToken);
        return $userArray["UniqueName"];
    }
}
