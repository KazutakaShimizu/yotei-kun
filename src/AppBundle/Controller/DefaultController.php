<?php

namespace AppBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ScheduleSetting;
use AppBundle\Form\ScheduleSettingType;
// use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="yotei-kun")
     */
    public function indexAction(Request $request)
    {
        $scheduleSettingEntity = new ScheduleSetting();
        $form = $this->createScheduleSettingForm($scheduleSettingEntity);

        

        return $this->render('default/index.html.twig', array(
            "form" => $form->createView(),
        ));
    }

    private function createScheduleSettingForm(ScheduleSetting $scheduleSettingEntity)
    {
        $form = $this->createForm(new ScheduleSettingType(), $scheduleSettingEntity, array(
            'action' => $this->generateUrl('yotei-kun-get-freetime'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'post'));

        return $form;
    }

    /**
     * @Route("/hoge", name="hoge")
     * @Method("GET")
     */
    public function hogeAction(Request $request)
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
        $freebusy->setTimeMax(date("c", strtotime("2018-02-17")));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);

        $freeTimes = [];
        $boforeBusyEndTime = "0時";
        foreach ($results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy() as $busy) {
            $startYear = date("Y年", strtotime($busy->getStart()));
            $startMonth = date("m月", strtotime($busy->getStart()));
            $startDay = date("d日", strtotime($busy->getStart()));
            $startTime = date("H時", strtotime($busy->getStart()));
            $endYear = date("Y年", strtotime($busy->getEnd()));
            $endMonth = date("m月", strtotime($busy->getEnd()));
            $endDay = date("d日", strtotime($busy->getEnd()));
            $endTime = date("H時", strtotime($busy->getEnd()));
            if (!isset($freeTimes["{$startYear}{$startMonth}{$startDay}"])) {
                $boforeBusyEndTime = "0時";
                $freeTimes["{$startYear}{$startMonth}{$startDay}"][] = "{$boforeBusyEndTime}~{$startTime}";
            }else{
                $freeTimes["{$startYear}{$startMonth}{$startDay}"][] = "{$boforeBusyEndTime}~{$startTime}";
            }
            $boforeBusyEndTime = $endTime;
        }

        foreach ($freeTimes as $day => $times) {
            $str = "{$day}:";
            foreach ($times as $time) {
                $str .= "{$time}, ";
            }
            dump(rtrim($str, ", "));
        }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(

        ));
    }

    /**
     * @Route("/hoge", name="yotei-kun-get-freetime")
     * @Method("POST")
     */
    public function getFreeTimeAction(Request $request)
    {            
        $scheduleSettingEntity = new ScheduleSetting();
        $form = $this->createScheduleSettingForm($scheduleSettingEntity);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            var_dump($form->getErrors(true));
            dump($hogehoge);
        }


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
        $freebusy->setTimeMax(date("c", strtotime("2018-02-17")));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);


        $freeTimes = [];
        $boforeBusyEndTime = $scheduleSettingEntity->getTimeFrom()->format('H');
        $timeFrom = $scheduleSettingEntity->getTimeFrom()->format('H');
        $timeTo = $scheduleSettingEntity->getTimeTo()->format('H');

        foreach ($results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy() as $busy) {
            $startYear = date("Y", strtotime($busy->getStart()));
            $startMonth = date("m", strtotime($busy->getStart()));
            $startDay = date("d", strtotime($busy->getStart()));
            $startTime = date("H", strtotime($busy->getStart()));
            $endYear = date("Y", strtotime($busy->getEnd()));
            $endMonth = date("m", strtotime($busy->getEnd()));
            $endDay = date("d", strtotime($busy->getEnd()));
            $endTime = date("H", strtotime($busy->getEnd()));
            if (!isset($freeTimes["{$startYear}{$startMonth}{$startDay}"])) {
                $boforeBusyEndTime =$scheduleSettingEntity->getTimeFrom()->format('H');
            }
            // 開始時間、終了時間周りの設定
            if ($timeFrom < $endTime) {
                if ($startTime > $timeTo) {
                    $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "{$beforeBusyEndTime}時~{$timeTo}時";
                }else{
                    $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "{$beforeBusyEndTime}時~{$startTime}時";
                }
                $beforeBusyEndTime = $endTime;
            }elseif($timeFrom >= $startTime && $timeFrom <= $endTime){
                $beforeBusyEndTime = $endTime;                
            }
            $beforeBusyYearMonthDate = "{$startYear}年{$startMonth}月{$startDay}日";
        }

        foreach ($freeTimes as $day => $times) {
            $str = "{$day}:";
            foreach ($times as $time) {
                $str .= "{$time}, ";
            }
            dump(rtrim($str, ", "));
        }

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
        $client->setApprovalPrompt('force');
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
            header("Location: {$authUrl}");
            exit;
            // 標準入力からinputを待つコードになっている
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
            dump($client->getRefreshToken());
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}
