<?php

// 予定が全く入ってない日の対応
// 予定が全く入ってない日、もしくは入ってるが範囲に入ってない日の際に、どうやってintervalを反映させないようにするか


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
    public $freeTimes;

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
     * @Route("/hoge", name="yotei-kun-get-freetime")
     * @Method("POST")
     */
    public function getFreeTimeAction(Request $request)
    {            
        $scheduleSettingEntity = new ScheduleSetting();
        $form = $this->createScheduleSettingForm($scheduleSettingEntity);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            dump($form->getErrors(true));
            exit;
            return $this->redirect($this->generateUrl('yotei-kun'));
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
        $freebusy->setTimeMin($scheduleSettingEntity->getDayFrom()->format("c"));
        $freebusy->setTimeMax($scheduleSettingEntity->getDayTo()->format("c"));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);

        // $freeTimes = [];
        $beforeBusyEndTime;
        $i = 0;
        $busyArray = $results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy();
        $isTodayFirstBusy = true;
        $minimumUnit = $scheduleSettingEntity->getMinimumUnit();
        $interval = $scheduleSettingEntity->getInterval();
        dump($scheduleSettingEntity->getTimeFrom());
        exit;
        
        $dateInterval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($scheduleSettingEntity->getDayFrom(), $dateInterval ,$scheduleSettingEntity->getDayTo());
        foreach ($daterange as $date) {
            $this->freeTimes[$date->format("Y年m月d日")] = null;
        }

        foreach ($busyArray as $busy) {
            // 予定の開始関連
            $startDateTime = new \DateTime($busy->getStart());
            $startYear = $startDateTime->format("Y");
            $startMonth = $startDateTime->format("m");
            $startDay = $startDateTime->format("d");
            // 予定の終了関連
            $endDateTime = new \DateTime($busy->getEnd());

            $timeFrom = $scheduleSettingEntity->getTimeFrom()->setDate($startYear, $startMonth, $startDay);
            $timeTo = $scheduleSettingEntity->getTimeTo()->setDate($startYear, $startMonth, $startDay);

            if ($timeFrom < $startDateTime) {
                if($isTodayFirstBusy){// その日最初の予定だった場合
                    dump(0);
                    $this->addFreeTime($startDateTime, $timeFrom, $startDateTime, $minimumUnit, $interval, 1);
                }else{
                    // その予定のstartTimeがtimeToよりも後ならtimeToを使うべき
                    if ($startDateTime > $timeTo) {
                        dump(1);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $interval);
                    }else{
                        dump(2);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $startDateTime, $minimumUnit, $interval);
                    }
                }
                $beforeBusyEndTime = $endDateTime;
            }elseif($timeFrom > $endDateTime){
                $beforeBusyEndTime = $timeFrom;
            }else{
                $beforeBusyEndTime = $endDateTime;
            }

            if (array_key_exists($i+1, $busyArray)) {
                $nextBusyStartDateTime = new \DateTime($busyArray[$i+1]->getStart());
                if ($startDateTime->format("Ymd") !== $nextBusyStartDateTime->format("Ymd")) {//その日最後の予定だった場合
                    // 予定のスタート時間 < 範囲の終わり時間かつ予定の終わり
                    if ($endDateTime < $timeTo) {
                        if ($isTodayFirstBusy) {
                            dump(3);
                            $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $interval, 2);
                        }else{
                            dump(4);
                            $this->addFreeTime($startDateTime, $endDateTime, $timeTo, $minimumUnit, $interval, 2);
                        }
                    }
                    $i++;
                    $isTodayFirstBusy = true;
                    continue;
                }
            }else{
                if ($endDateTime < $timeTo) {
                    // ここでもbeforebusyEndtimeを使う必要のあるパターン
                    if ($isTodayFirstBusy) {
                        dump(5);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $interval, 2);
                    }else{
                        dump(6);
                        $this->addFreeTime($startDateTime, $endDateTime, $timeTo, $minimumUnit, $interval, 2);
                    }
                }
            }
            $i++;
            $isTodayFirstBusy = false;
        }

        // busyArrayとdateRangeを比較して日付が入ってなければみたいな？


        // $interval = new \DateInterval('P1D');
        // $daterange = new \DatePeriod($scheduleSettingEntity->getDayFrom(), $interval ,$scheduleSettingEntity->getDayTo());
        // foreach ($daterange as $date) {
        //     if (!array_key_exists($date->format("Y年m月d日"), $this->freeTimes)) {
        //         $freeTimes[$date->format("Y年m月d日")][] = "{$timeFrom->format("H:i")}~{$timeTo->format("H:i")}";
        //     }
        // }
        ksort($this->freeTimes);

        $freeTimesText = [];
        foreach ($this->freeTimes as $day => $times) {
            $str = "{$day}:";
            foreach ((array)$times as $time) {
                $str .= "{$time}, ";
            }
            $freeTimesText[] = rtrim($str, ", ");
        }


        // replace this example code with whatever you need
        return $this->render('default/answer.html.twig', array(
            "freeTimesText" => $freeTimesText,
        ));
    }

    private function addFreeTime($thisDay, $timeFrom, $timeTo, $minimumUnit, $interval, $type=0){
        $freeTime = $timeFrom->diff($timeTo)->format("%h")*60 + $timeFrom->diff($timeTo)->format("%i");
        $minimumUnit += $interval*2;
        if ($freeTime >= $minimumUnit) {
            if ($type == 0 || $type == 2) {
                $timeFrom->modify("{$interval} minutes");
            }
            if ($type == 0 || $type == 1) {
                $timeTo->modify("-{$interval} minutes");
            }
            $this->freeTimes[$thisDay->format("Y年m月d日")][] = "{$timeFrom->format("H:i")}~{$timeTo->format("H:i")}";
        }
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
