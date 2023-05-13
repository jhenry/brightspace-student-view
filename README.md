# Brightspace API Integration

This is a generic Laravel application that integrates with D2L Brightspace in order to leverage the LMS API capabilities using oAuth to authenticate.  

## Install

### Application installation

1. Configure the necessary roles, permissions, and integration details within the LMS.  
2. Clone this into wherever the app is going to be served from
3. composer install --optimize-autoloader --no-dev 
3. cp .env.example .env
1. update configs to match those you configured above
1. run php artisan key:generate


### Initialize API Services Token Cache

An initial authentication via oAuth is needed for the application to send API calls as a specific service role.  That role will likely have different permissions than the end user who is interacting with the application. You should configure the services role to only access specific tasks in the LMS. To do this we first need to authenticate with the API account so the access token can be stored.  

1. in an incognito window sign in to lms with api_services account for sva
2. hit application launch, i.e. https://your.institution.edu/pathtoapp/signin?ou=7005
1. accept/allow when prompted 

### Test as normal user
1. in another session, sign in with normal user netid,
2. go to a course with the application link deployed
3. accept/allow prompt 
