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

        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        $viewData["svExists"] = (!empty($studentView->studentViewUser)) ? true : false;

        $viewData["orgUnitId"] = $studentView->orgUnitId;
        $viewData["lmsBaseUrl"] = $studentView->orgUnitId;
        $viewData["classlistUrl"] = config('services.lms.base') . '/d2l/lms/classlist/classlist.d2l?ou=' . $studentView->orgUnitId;

        return view('welcome', $viewData);
    }

    public function addStudentView()
    {
        $tokenCacheCache = new TokenCacheCache();
        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        if( $_POST['action-add'] == 'add' ) {
            $studentView->createUser();
            $studentView->createEnrollment();
        }

        return redirect('/');
    }

    public function removeStudentView()
    {
        $tokenCacheCache = new TokenCacheCache();
        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        if( $_POST['action-remove'] == 'remove' ) {
            $studentView->deleteEnrollment();
            $studentView->deleteUser();
        }

        return redirect('/');
    }

    public function queryUser($tokenCache)
    {
        $accessToken = $tokenCache->getAccessToken();
        // Initialize the OAuth client
        $userArray = $this->brightspace->whoAmI($accessToken);
        return $userArray["UniqueName"];
    }
}
