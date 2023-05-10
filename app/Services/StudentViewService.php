<?php

namespace App\Services;
use App\Services\BrightspaceService;
use Illuminate\Support\Facades\Log;

class StudentViewService
{
    public $accountPostfix = '_sv';
    public $studentViewRoleId = 126;

    public $parentUserId;
    public $orgUnitId;
    public $studentViewUser;

    private $accessCode;
    private $brightspace;

    public function __construct(BrightspaceService $brightspaceService, $accessTokens)
    {
        $this->brightspace = $brightspaceService;
        $this->accessCode = $accessTokens;

        $this->parentUserId = session('userIdentifier');
        $this->orgUnitId = session('orgUnitId');
        $this->accountPostfix = $this->accountPostfix . $this->orgUnitId;

        //check for existing and set member data
        $userName = session('userName') . $this->accountPostfix;
        $userResponse = $this->getUserByName($userName);
        $this->studentViewUser = ($userResponse) ? $userResponse : array();
    }

    /**
     * Make a demo student account for a specific user/course combination
     * https://docs.valence.desire2learn.com/res/user.html#post--d2l-api-lp-(version)-users-
     *
     */
    public function createUser()
    {
        $newUser = $this->buildUserData();

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
                }
            }
            else{
                $this->studentViewUser = $userResponse;
            }
        }
    }


    /**
     * Create a new enrollment
     *
     */
    public function createEnrollment()
    {
        $enrollmentData = $this->buildEnrollmentData();
        if( $enrollmentData )
        {
            $response = $this->brightspace->doRequest('/enrollments/', $this->accessCode, 'POST', $enrollmentData);
                $status = $response->getStatusCode();
                if($status == '200')
                {
                    $body = (string) $response->getBody();
                    $this->studentViewEnrollment = json_decode($body, true);
                }
        }

    }

    /**
     * Remove the student view user enrollment
     *
     */
    public function deleteEnrollment()
    {
        $request = '/enrollments/orgUnits/' . $this->orgUnitId . '/users/' . $this->studentViewUser['UserId'];
        $response = $this->brightspace->doRequest($request, $this->accessCode, 'DELETE');
        $status = $response->getStatusCode();
        if ($status == '200') {
            $this->studentViewEnrollment = array();
        }
    }

    /**
     * Remove the student view user
     *
     */
    public function deleteUser()
    {
        $request = '/users/' . $this->studentViewUser['UserId'];
        $response = $this->brightspace->doRequest($request, $this->accessCode, 'DELETE');
        $status = $response->getStatusCode();
        if ($status == '200') {
            $this->studentViewUser = array();
        }
    }


    /**
     * Retrieve a user record using a D2LID
     *
     */
    public function getUser($userId)
    {
        $request = '/users/' . $userId;
        return $this->getRecord($request);
    }

    /**
     * Retrieve a user record based off of username
     *
     */
    public function getUserByName($userName)
    {
        $request = '/users/?userName=' . $userName;
        return $this->getRecord($request);
    }

    /**
     * retrieve a user, enrollment, or other record
     * return false if we get a 404
     *
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
     * Does this record exist
     *
     */
    private function recordExists($response)
    {
        $exists = ($response->getStatusCode() == '404') ? false : true;
        return $exists;
    }
    /**
     * Construct a new user data payload
     * https://docs.valence.desire2learn.com/res/user.html#User.CreateUserData
     *
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
                "MiddleName" => "test",
                "LastName" => $user["LastName"] . $postFix,
                "ExternalEmail" => $email,
                "UserName" => $user["UserName"] . $postFix,
                "RoleId" => 126,
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
   * Construct a new enrollment data payload
   *
   */
  public function buildEnrollmentData() {
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
}
