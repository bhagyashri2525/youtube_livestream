<?php

namespace App\Http\Controllers;
use Laravel\Socialite\Facades\Socialite;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_LiveBroadcast;
use Google_Service_YouTube_LiveBroadcastStatus;
use Google\Client as GoogleClient;
use Google\Auth\FetchAuthTokenInterface;
use Google\Auth\OAuth2;
use Google\Auth\OAuth2\Client;
use Your\Namespace\Here\YourAuthenticationClient;
use Google_Service_YouTubeAnalytics;
use  Google\Service\YouTube\LiveBroadcastStatistics;

class GoogleLoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirect()
    {
        // return Socialite::driver('google')->redirect();
       

        // return Socialite::driver('google')->scopes(['email', 'profile', 
        // 'https://www.googleapis.com/auth/youtube.readonly',
        // 'https://www.googleapis.com/auth/youtube',
        // 'https://www.googleapis.com/auth/youtube.upload'
        // ])->redirect();

         $scopes = array(
            'email', 'profile', 
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.upload'
        );

        $parameters = ['access_type' => 'offline', "prompt" => "consent select_account",'setRefreshToken'];
        return Socialite::driver('google')->scopes($scopes)->with($parameters)->redirect();
    }
    public function createLivestream()
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            $client = new Google_Client();
            $client->setAccessToken($user->token);

            //if you want to check details of user (token ,refresh token ,expiresIn,etc)

            // if($user->email) {
            //         dd($user);
            //         $token = $user->token;
            //         $refreshToken = $user->refreshToken;
            //         $expiresIn = $user->expiresIn;
            //         dd($user);
            //         return response()->json('Existing User');
            //     }
            //     EXIT();
            
            $youtube = new Google_Service_YouTube($client);
            
            $streamSnippet = new \Google_Service_YouTube_LiveBroadcastSnippet();
            $streamSnippet->setTitle('welcome to my youtube channel new new new');
            $streamSnippet->setScheduledStartTime('2023-09-07T12:00:00Z');
            $streamSnippet->setDescription('hello to my youtube channel !');
          
            $streamStatus = new \Google_Service_YouTube_LiveBroadcastStatus();
            $streamStatus->setPrivacyStatus('public');


            $broadcast = new \Google_Service_YouTube_LiveBroadcast();
            $broadcast->setSnippet($streamSnippet);
            $broadcast->setStatus($streamStatus);
            
            $broadcastInsert = $youtube->liveBroadcasts->insert('snippet,status', $broadcast, []);
    
            // The ID of the created live broadcast
            $broadcastId = $broadcastInsert['id'];
            
            // // Output the broadcast ID
            echo "Broadcast Successfully created \n Broadcast ID: " . $broadcastId;
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function updateLivestream()
    {
        $user = Socialite::driver('google')->stateless()->user();
        $client = new Google_Client();
        $client->setAccessToken($user->token);
        $youtube = new Google_Service_YouTube($client);

        $broadcastId='08EYnyzMA6A';

        $broadcast = $youtube->liveBroadcasts->listLiveBroadcasts('snippet', ['id' => $broadcastId]);
        $existingBroadcast = $broadcast->getItems()[0];

         $newTitle = 'Updated 05 sep 23';
        $newStartTime = '2023-09-05T14:20:00Z'; // New scheduled start time in UTC

        $updatedSnippet = $existingBroadcast->getSnippet();
        $updatedSnippet->setTitle($newTitle);
        $updatedSnippet->setScheduledStartTime($newStartTime);
        $existingBroadcast->setSnippet($updatedSnippet);

        $broadcastUpdateSnippet = $youtube->liveBroadcasts->update('snippet', $existingBroadcast);

        $broadcastId = $broadcastUpdateSnippet['id'];
        echo "Broadcast Successfully updated \n Broadcast ID: " . $broadcastId;
    }
    
    public function getDetails()
    {
        $user = Socialite::driver('google')->stateless()->user();
        $client = new Google_Client();
        $client->setAccessToken($user->token);
        $youtube = new Google_Service_YouTube($client);
        $broadcastId = 'QumXxIOWFPI';

       //get live stream details (title ,time ,etc)
        $broadcast = $youtube->liveBroadcasts->listLiveBroadcasts('snippet', ['id' => $broadcastId]);
        $existingBroadcast = $broadcast->getItems()[0];

        if ($existingBroadcast) {
            $title = $existingBroadcast->getSnippet()->getTitle();
            $scheduledStartTime = $existingBroadcast->getSnippet()->getScheduledStartTime();
            echo "Broadcast Details:\n";
            echo "Title: " . $title . "\n";
            echo "Scheduled Start Time: " . $scheduledStartTime . "\n";
        } else {
            echo "Broadcast not found.";
        }


        //get analytics (views and likes) 
        $statisticsResponse = $youtube->videos->listVideos('statistics', array('id' => $broadcastId));
        $video = $statisticsResponse->getItems()[0];
        $statistics = $video->getStatistics();

        $viewCount = $statistics->getViewCount();
        $likeCount = $statistics->getLikeCount();
        echo "View Count: " . $viewCount . "<br>";
        echo "Like Count: " . $likeCount . "<br>";


        //live stream status check 
        $broadcastDetails = $youtube->liveBroadcasts->listLiveBroadcasts('snippet,status', ['id' => $broadcastId]);
        $existingBroadcast = $broadcastDetails->getItems()[0];
        $status = $existingBroadcast->getStatus()->getLifeCycleStatus();

        if ($status == 'live' || $status == 'completed') {
            echo "Broadcast Title: " . $existingBroadcast->getSnippet()->getTitle() . "\n";
            echo "Broadcast Status: " . $status . "\n";
        } else {
            echo "Broadcast is not live or completed.";
        }
    }

    public function refreshTokenWork()
    {
       $client = new Google_Client();

       // client_id => env('GOOGLE_CLIENT_ID'),
       // client_secret => env('GOOGLE_CLIENT_SECRET'),
       // redirect => env('GOOGLE_REDIRECT')

       //         CLIENT_ID="671013543570-rhl9uohj3camcbt31h2ub301g0e7atlp.apps.googleusercontent.com"
       // CLIENT_SECRET="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"
       // REDIRECT="http://127.0.0.1:8000/login/google/callback"

           $client->setClientId('CLIENT_ID');
           $client->setClientSecret('GOOGLE_CLIENT_SECRET');
           $client->setRedirectUri('REDIRECT');
           $client->setScopes('https://www.googleapis.com/auth/youtube');

           $refreshToken = '1//0gOAWWfILmHN3CgYIARAAGBASNwF-L9IrVtfXggVUsvTk6Try5FHRCM0s7uSRIlTLT1Sfoc80IT8mixsvjRTl86EFdm-afZE2SX0';

           $client->setRefreshToken('your-refresh-token');

           // Get a new access token
           $accessToken = $client->fetchAccessTokenWithRefreshToken();
    }

    public function refreshTokenTry2(){

        $access_token = 'ya29.a0AfB_byCgIdASuGMTef73oYIRB0NZTHFrmUDOBNOZZUo7vKARpms6tKKWPuDAuZvtL4lNmYZ3ISygPvZsG-8T1UmAg2SKB-YbCPdwhAz32GMvCYmaIGolyOZgAVy-sT9qlXj8qim7mNmMRJkWMGkg0Z3m_zFF1BoQK-XNUQaCgYKAfcSARISFQHsvYlsDxD_ZM0QXLAJPaXdWLcO6g0173';
        $refresh_token = '1//0gpRrmHcaqV9FCgYIARAAGBASNwF-L9Ir3t0IWJksZMq1JRs3WtlXqyIoo4uSgUqlF4S-EGIy75OiWj_mIwWXXnckuaUfCkirc9g';

        $client = new Google_Client();
        $client->setAccessType('offline'); // This is important to get a refresh token.
        $client->setAccessToken($access_token);

        if ($client->isAccessTokenExpired()) {
            // If the access token is expired, use the refresh token to get a new one.
            $client->fetchAccessTokenWithRefreshToken($refresh_token);

            $new_access_token = $client->getAccessToken();
            print_r($new_access_token);exit();
            $new_refresh_token = $client->getRefreshToken();
            print_r($new_refresh_token);exit();
        }
    }
}
