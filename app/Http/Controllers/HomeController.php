<?php
/**
 * Home Controller: Facilitate general application requests and actions.
 *
 * @package HomeController
 * @author Justin Henry <justin.henry@uvm.edu>
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TokenStore\TokenCacheCache;
use App\Services\BrightspaceService;
use App\Services\StudentViewService;

/**
 * Handle requests for adding/removing student view accounts.
 *
 * This controller implements high level support for managing student view
 * accounts in Brightspace.
 *
 * @todo Promote accessToken to class member.
 */
class HomeController extends Controller
{
    /**
     * Instance of the BrightspaceService class.
     *
     * @var BrightspaceService
     */
    private $brightspace;

    /**
     * Initiate Brightspace service.
     *
     * @param BrightspaceService $brightspaceService
     */
    public function __construct(BrightspaceService $brightspaceService)
    {
        $this->brightspace = $brightspaceService;
    }

    /**
     * Set up view data and perform preliminary checks needed (i.e. checking for duplicate/existing student view account) before offering actions to the user.
     *
     * @return View $view
     * @todo Move duplicate account check to it's own method.
     */
    public function welcome()
    {
        $viewData = $this->loadViewData();

        // Check for existing student view account, so the user can be guided to
        // the classlist and/or presented with a removal option
        $tokenCacheCache = new TokenCacheCache();
        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        $viewData["svExists"] = false;
        if (!empty($studentView->studentViewUser)) {
            $viewData["svExists"] = true;
            $viewData["svUserName"] = $studentView->studentViewUser;
        }

        $viewData["orgUnitId"] = $studentView->orgUnitId;
        $viewData["lmsBaseUrl"] = $studentView->orgUnitId;
        $viewData["classlistUrl"] = config('services.lms.base') . '/d2l/lms/classlist/classlist.d2l?ou=' . $studentView->orgUnitId;

        $viewData["isAllowed"] = $studentView->isAllowed();

        return view('welcome', $viewData);
    }

    /**
     * Handle account creation and enrollment.
     *
     * @return RedirectResponse
     */
    public function addStudentView()
    {
        $tokenCacheCache = new TokenCacheCache();
        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        if ($_POST['action-add'] == 'add') {
            $studentView->createUser();
            $studentView->createEnrollment();
        }

        return redirect('/');
    }

    /**
     * Handle account removal and unenrollment.
     *
     * @return RedirectResponse
     */
    public function removeStudentView()
    {
        $tokenCacheCache = new TokenCacheCache();
        $accessToken = $tokenCacheCache->getAccessToken();
        $studentView = new StudentViewService($this->brightspace, $accessToken);
        if ($_POST['action-remove'] == 'remove') {
            $studentView->deleteEnrollment();
            $studentView->deleteUser();
        }

        return redirect('/');
    }

    /**
     * Get the username for the user authenticated with an access token. Provided token cache may be session or file based, so that methods can query either end user or api user.
     *
     * @param mixed $tokenCache
     * @return array
     */
    public function queryUser($tokenCache)
    {
        $accessToken = $tokenCache->getAccessToken();
        // Initialize the OAuth client
        $userArray = $this->brightspace->whoAmI($accessToken);
        return $userArray["UniqueName"];
    }
}
