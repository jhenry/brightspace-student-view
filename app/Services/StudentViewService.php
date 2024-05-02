<?php

/**
 * Student View: StudentView api/service class
 * @package StudentViewService
 * @author Justin Henry <justin.henry@uvm.edu>
 *
 */
namespace App\Services;
use Session;
use App\Services\BrightspaceService;
use App\TokenStore\TokenSessionCache;
use Illuminate\Support\Facades\Log;

/**
 * Self-service student view account management for Brightspace.
 *
 * Account provisioning and enrollment of demo/dummy student accounts in
 * Brightspace courses via the Valence API's.  Methods for building payloads,
 * deletion of accounts, access control, and other ancillary tasks are also
 * included.
 *
 * @var string $accountPostfix Label/pattern for provisioned accounts.
 * @todo move $accountPostfix to .env
 */
class StudentViewService
{
    /**
     * Label/pattern fragment for provisioned account usernames.
     *
     * @var string
     */
    public $accountPostfix = '_sv';

    /**
     * D2LID for the role that is assigned to the provisioned accounts.
     *
     * @var int
     */
    public $studentViewRoleId;

    /**
     * List of allowed roles that can provision these accounts.
     *
     * @var array
     */
    public $allowedRoles;

    /**
     * D2LID of the user who is initiating the account provisioning request.
     *
     * @var int
     */
    public $parentUserId;

    /**
     * D2LID of the course the account is being enrolled into.
     *
     * @var int
     */
    public $orgUnitId;

    /**
     * Array containing user payload data for the provisioned account.
     *
     * @var mixed
     */
    public $studentViewUser;

    /**
     * Array containing enrollment payload for the provisioned account.
     *
     * @var mixed
     */
    public $studentViewEnrollment;

    /**
     * OAuth tokens for making authenticated calls to the API service.
     *
     * @var mixed
     */
    private $accessCode;

    /**
     * Instance of the service class handling low-level api requests.
     *
     * @var BrightspaceService
     */
    private $brightspace;

    /**
     * Initialize properties and check for pre-existing accounts.
     *
     * @param BrightspaceService $brightspaceService API service class.
     * @param mixed $accessTokens OAuth tokens used when sending requests.
     */
    public function __construct(BrightspaceService $brightspaceService, $accessTokens)
    {
        $this->brightspace = $brightspaceService;
        $this->accessCode = $accessTokens;
        $this->studentViewRoleId = config('services.lms.sva_role_id');
        $this->allowedRoles = explode(",", config('services.lms.end_user_roles'));
        $this->parentUserId = session('userIdentifier');
        $this->orgUnitId = session('orgUnitId');
        $this->accountPostfix = $this->accountPostfix . $this->orgUnitId;

        //check for existing and set member data
        $userName = session('userName') . $this->accountPostfix;
        $userResponse = $this->getUserByName($userName);
        $this->studentViewUser = ($userResponse) ? $userResponse : array();
    }

    /**
     *  Make a demo student account for a specific user/course combination.
     *  Check for duplicates, initialize user payload, and send the account
     *  provisioning call.
     *
     */
    public function createUser()
    {
        $newUser = $this->buildUserData();
        $userExists = false;
        if ( $newUser ) {
            $userResponse = $this->getUserByName($newUser['UserName']);
            // Check for existence of the user we're about to create
            if (!$userResponse) {
                // No user exists with this username, so let's make one
                $response = $this->brightspace->doRequest('/users/', $this->accessCode, 'POST', $newUser);
                $status = $response->getStatusCode();
                if($status == '200')
                {
                    $body = (string) $response->getBody();
                    $this->studentViewUser = json_decode($body, true);
                    $userExists = true;
                }
                else
                {
                  Log::warning('User creation failed with status code ' . $status);
                }
            }
            else{
                // User already exists
                $this->studentViewUser = $userResponse;
                $userExists = true;
            }
        }
        if ($userExists){
            Session::flash('alert', "Successfully created a student view account with the username " . $this->studentViewUser['UserName'] . ".");
            Session::flash('alert-class', 'alert-success');
        }
    }


    /**
     * Create a new enrollment payload and send the request.
     *
     */
    public function createEnrollment()
    {
        $enrollmentData = $this->buildEnrollmentData();
        if ($enrollmentData) {
            $response = $this->brightspace->doRequest('/enrollments/', $this->accessCode, 'POST', $enrollmentData);
            $status = $response->getStatusCode();
            if ($status == '200') {
                $body = (string) $response->getBody();
                $this->studentViewEnrollment = json_decode($body, true);
                Session::flash('alert', session('alert') . " This account has been enrolled in the course. ");
                Session::flash('alert-class', 'alert-success');
            }
        }
    }

    /**
     * Remove the student view user enrollment.
     *
     */
    public function deleteEnrollment()
    {
        $request = '/enrollments/orgUnits/' . $this->orgUnitId . '/users/' . $this->studentViewUser['UserId'];
        $response = $this->brightspace->doRequest($request, $this->accessCode, 'DELETE');
        $status = $response->getStatusCode();
        if ($status == '200') {
            $this->studentViewEnrollment = array();
            Session::flash('alert', "The Student View Account has been unenrolled ");
            Session::flash('alert-class', 'alert-success');
        }
    }

    /**
     * Delete the provisioned student view account for this course/requestor.
     *
     */
    public function deleteUser()
    {
        $request = '/users/' . $this->studentViewUser['UserId'];
        $response = $this->brightspace->doRequest($request, $this->accessCode, 'DELETE');
        $status = $response->getStatusCode();
        if ($status == '200') {
            $this->studentViewUser = array();
            Session::flash('alert', session('alert') . " and deleted.");
            Session::flash('alert-class', 'alert-success');
        }
    }

    /**
     * Retrieve a user record using a D2LID. Returns false if not found (404),
     * otherwise a decoded JSON data block.
     *
     * @param int $userId
     * @return mixed
     */
    public function getUser($userId)
    {
        $request = '/users/' . $userId;
        return $this->getRecord($request);
    }

    /**
     * Retrieve a user record based off of username.
     *
     * @param string $userName
     * @return mixed
     */
    public function getUserByName($userName)
    {
        $request = '/users/?userName=' . $userName;
        return $this->getRecord($request);
    }

    /**
     * Make a pre-constructed request to the API service.  Return false if the
     * record is not found (i.e. on 404 HTTP error).
     *
     * @param mixed $request
     * @return mixed
     */
    private function getRecord($request)
    {
        $response = $this->brightspace->doRequest($request, $this->accessCode);
        if ($this->recordExists($response)) {
            $body = (string) $response->getBody();
            return json_decode($body, true);
        } else {
            return false;
        }
    }

    /**
     * Check the status code for a response to determine if the record exists.
     *
     * @param ResponseInterface $response
     * @return bool
     * @todo move to api service class.
     */
    private function recordExists($response)
    {
        $exists = ($response->getStatusCode() == '404') ? false : true;
        return $exists;
    }

    /**
     * Construct a new user data payload data block. Fail if the id for the
     * requesting account can't be found.
     * https://docs.valence.desire2learn.com/res/user.html#User.CreateUserData
     *
     * @return mixed
     * @todo move email domain to a configurable in .env
     */
    public function buildUserData()
    {
        $user = $this->getUser($this->parentUserId);
        if ( $user ) {
            $postFix = $this->accountPostfix;
            $email = $user["UserName"] . "+brightspace" . $postFix . "@uvm.edu";
            $createUserData = array(
                "OrgDefinedId" => "",
                "FirstName" => $user["FirstName"] . $postFix,
                "MiddleName" => "",
                "LastName" => $user["LastName"] . $postFix,
                "ExternalEmail" => $email,
                "UserName" => $user["UserName"] . $postFix,
                "RoleId" => $this->studentViewRoleId,
                "IsActive" => "True",
                "SendCreationEmail" => "False"
            );

            return $createUserData;
        } else {
            Log::warning('Parent user id not found? Id: ' . $this->parentUserId);
            return false;
        }
    }

    /**
     * Construct a new enrollment data payload.
     *
     * @return mixed
     */
    public function buildEnrollmentData()
    {
        if (!empty($this->studentViewUser['UserId'])) {
            $newEnrollmentData = array(
                "OrgUnitId" => $this->orgUnitId,
                "UserId" => $this->studentViewUser['UserId'],
                "RoleId" => $this->studentViewRoleId,
                "IsCascading" => "False"
            );
            return $newEnrollmentData;
        } else {
            Log::warning('Attempting to build enrollment record without existing user?');
            return false;
        }
    }

    /**
     * Confirm if the end user/session user has access to the tool by pulling
     * Enrollment.MyOrgUnitAccessInfo block using their own session.
     *
     * @return bool
     */
    public function isAllowed()
    {
        $tokenSessionCache = new TokenSessionCache();
        $accessToken = $tokenSessionCache->getAccessToken();

        $allowed = false;

        $request = '/enrollments/myenrollments/' . $this->orgUnitId;
        $response = $this->brightspace->doRequest($request, $accessToken);
        $status = $response->getStatusCode();
        if ($status == '200') {
            $body = (string) $response->getBody();
            $enrollment = json_decode($body, true);
            $canAccess = $enrollment['Access']['CanAccess'];
            $role = $enrollment['Access']['ClasslistRoleName'];
            if ($canAccess) {
                $allowed = (in_array($role, $this->allowedRoles)) ? true : false;
            }
        }

        return $allowed;
    }

}
