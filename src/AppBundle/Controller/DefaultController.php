<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
// use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="yotei-kun")
     */
    public function indexAction(Request $request)
    {            
        
        return $this->render('default/index.html.twig', array(

        ));
    }

    /**
     * @Route("/hoge", name="yotei-kun-get-freetime")
     */
    public function getFreeTimeAction(Request $request)
    {            
        $client = $this->getClient();
        $calendarService = new \Google_Service_Calendar($client);
        $calendarList = $calendarService->calendarList->listCalendarList();
        $calendarArray = [];

        // Put together our calendar array
        while(true) {
          foreach ($calendarList->getItems() as $calendarListEntry) {
              $calendarArray[] = ['id' => $calendarListEntry->id ];
          }
          $pageToken = $calendarList->getNextPageToken();
          if ($pageToken) {
              $optParams = array('pageToken' => $pageToken);
              $calendarList = $calendarService->calendarList->listCalendarList($optParams);
          } else {
              break;
          }
        } 

        $freebusy = new \Google_Service_Calendar_FreeBusyRequest();
        $freebusy->setTimeMin(date("c"));
        $freebusy->setTimeMax(date("c", strtotime("2018-01-15")));
        // $freebusy->setTimeZone('Japan/Tokyo');
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $result = $calendarService->freebusy->query($freebusy);
        dump($result->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy());

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(

        ));
    }


    private function getClient(){
        $client = new \Google_Client();
        $client->setApplicationName("yotei-kun");
        $client->setScopes(implode(' ', array(\Google_Service_Calendar::CALENDAR_READONLY)));
        $client->setAuthConfig($this->get('kernel')->getRootDir()."/client_secret.json");
        $client->setAccessType('offline');
        // Load previously authorized credentials from a file.
        $path = '~/.credentials/calendar-php-quickstart.json';
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        $credentialsPath = str_replace('~', realpath($homeDirectory), $path);

        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath))) {
              mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}
