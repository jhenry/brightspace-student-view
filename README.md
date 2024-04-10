# Brightspace Student View

A "real" student view for Brightspace instructors.

## Background
Current Role Switching functionality does not fully encapsulate learner behavior. For example activities such as grading, group enrollment, assignment submission are not represented when switching to Learner view.  

D2L has indicated this functionality is not planned (see this Product Idea Exchange [post](https://desire2learn.brightidea.com/ideas/D2375)).

To meet that need, this application allows users to create and enroll a student in their course which they can subsequently impersonate. When they are finished, they can delete these accounts/enrollments.   

## Installation and Configuration

Deploying this tool looks something like this:

1. Create a service account, roles, and an integration in the LMS.
2. Install and deploy the application in your hosted environment.
3. Initiallize refresh token.  

### Preparation

#### Create an API Service Account and Role

You will need to create a specific LMS user account, along with an associated role with permissions tailored to the needs of the application.  

Never, (ever!) use an administrator account for this service in production. Permissions should be scoped only to include what is necessary for execution of it specific tasks such as user creation, enrollment, etc.

### Create a Student View Role

In order to allow instructors to impersonate their student view accounts but not real Learner enrollments you'll need to create a separate role. This can be done by copying the Learner role and adjusting it's permissions to allow impersonation by the desired roles (i.e. Instructor, TA, Builder).  

### Create a New OAuth Integration

Register a new OAuth 2.0 application in the LMS, with the following set:

* Redirect URI: `https://your.application.address.edu/callback`
* Scope: `core:*:* enrollment:*:* role:*:* users:*:*`
* Enable Refresh Tokens: `true`

### Application Installation

This is a Laravel application, so you will need an environment with an up to date version of PHP.  Once you have that, the basic steps look like this:
 
1. Download/unpack this repository into it's new home
3. For a production install, run `composer install --optimize-autoloader --no-dev` 
3. Then `cp .env.example .env`
1. Update configs to match those you configured above
1. Run `php artisan key:generate`

### Configuration

In `.env` edit the values for OAUTH_APP_ID, OAUTH_APP_SECRET, OAUTH_REDIRECT_URI, OAUTH_SCOPES to match the integration you created above in the LMS.

Set LMS_BASE_URL to be the address of your Brightspace instance.  

Update LMS_API_SERVICE_USER to reflect the username of the API services account you created for this application.

Set LMS_END_USER_ROLES to match the names of the course roles you want to be able to use and access this tool.   

Finally, LMS_SVA_ROLE_ID will be the D2LID of the role you created above for the Student View Accounts to use. 

### Initialize API Services Token

NOTE: Never initialize this service using an administrator account in a production environment!  As noted above, you'll need to create a separate account and role. That role will need different permissions than the end user who is interacting with the application— but you should configure this services role to only access specific tasks in the LMS. 

An initial authentication via oAuth is needed for the application to send API calls as a specific service role.  To do this we first need to authenticate with that API services account so the access token can be stored.  

1. In an incognito window sign in to the LMS with the API Services Account you created above.
2. In that same window launch the application, i.e. using https://your.application.address.edu/signin?ou=7005 

### Create a navbar link 

In order for your users to access the deployed integration, they'll need a link to it, including the D2LID of the course they are adding a student to.  One way to do this is to add a link to a navbar.  This URL will need to include the OrgUnitId replace string, such that it looks something like this:

https://your.application.address.edu/signin?ou={OrgUnitId}

Note also that to avoid confusion, you will want to limit access to the link so that it matches the roles which can use it (i.e. Instructor, TA, Builder, Designer).

### Test as normal user

This should now allow you to access the tool as a user with an appropriate role. For example, to test as an instructor:

1. Sign in with normal user account 
2. Go to a course that this user is enrolled as an instructor in
2. Access the navbar link as deployed above.

