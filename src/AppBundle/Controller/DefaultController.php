<?php

//　前の予定から◯○分あけるとか。一時間あけて予定を出すなら、最低二時間でとっておいて、後半の一時間を出力するとか


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
        $freebusy->setTimeMin($scheduleSettingEntity->getDayFrom()->format("c"));
        $freebusy->setTimeMax($scheduleSettingEntity->getDayTo()->format("c"));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);

        $freeTimes = [];
        $beforeBusyEndTime;
        $i = 0;
        $busyArray = $results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy();
        $isTodayFirstBusy = true;
        $minimumUnit = $scheduleSettingEntity->getMinimumUnit();
        $interval = $scheduleSettingEntity->getInterval();

        foreach ($busyArray as $busy) {
            // 予定の開始関連
            $startDateTime = new \DateTime($busy->getStart());
            $startYear = $startDateTime->format("Y");
            $startMonth = $startDateTime->format("m");
            $startDay = $startDateTime->format("d");
            // 予定の終了関連
            $endDateTime = new \DateTime($busy->getEnd());

            // ここでtimeFromをそのbusyの日付に直すべき
            $timeFrom = $scheduleSettingEntity->getTimeFrom()->setDate($startYear, $startMonth, $startDay);
            $timeTo = $scheduleSettingEntity->getTimeTo()->setDate($startYear, $startMonth, $startDay);

            if ($timeFrom < $startDateTime) {
                if($isTodayFirstBusy){// その日最初の予定だった場合
                    if ($this->isEnoughFreeTime($timeFrom, $startDateTime, $minimumUnit, $interval)) {
                        $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$timeFrom->format("H:i")}~{$startDateTime->format("H:i")}";
                    }
                }else{
                    // その予定のstartTimeがtimeToよりも後ならtimeToを使うべき
                    if ($startDateTime > $timeTo) {
                        if ($this->isEnoughFreeTime($beforeBusyEndTime, $timeTo, $minimumUnit, $interval)) {
                            $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$beforeBusyEndTime->format("H:i")}~{$timeTo->format("H:i")}";
                        }
                    }else{
                        if ($this->isEnoughFreeTime($beforeBusyEndTime, $startDateTime, $minimumUnit, $interval)) {
                            $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$beforeBusyEndTime->format("H:i")}~{$startDateTime->format("H:i")}";
                        }
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
                            if ($this->isEnoughFreeTime($beforeBusyEndTime, $timeTo, $minimumUnit, $interval)) {
                                $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$beforeBusyEndTime->format("H:i")}~{$timeTo->format("H:i")}";
                            }
                        }else{
                            if ($this->isEnoughFreeTime($endDateTime, $timeTo, $minimumUnit, $interval)) {
                                $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$endDateTime->format("H:i")}~{$timeTo->format("H:i")}";
                            }

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
                        if ($this->isEnoughFreeTime($beforeBusyEndTime, $timeTo, $minimumUnit, $interval)) {
                            $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$beforeBusyEndTime->format("H:i")}~{$timeTo->format("H:i")}";
                        }
                    }else{
                        if ($this->isEnoughFreeTime($endDateTime, $timeTo, $minimumUnit, $interval)) {
                            $freeTimes[$startDateTime->format("Y年m月d日")][] = "{$endDateTime->format("H:i")}~{$timeTo->format("H:i")}";
                        }
                    }
                }
            }
            $i++;
            $isTodayFirstBusy = false;
        }

        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($scheduleSettingEntity->getDayFrom(), $interval ,$scheduleSettingEntity->getDayTo());
        foreach ($daterange as $date) {
            if (!array_key_exists($date->format("Y年m月d日"), $freeTimes)) {
                $freeTimes[$date->format("Y年m月d日")][] = "{$timeFrom->format("H:i")}~{$timeTo->format("H:i")}";
            }
        }
        ksort($freeTimes);

        $freeTimesText = [];
        foreach ($freeTimes as $day => $times) {
            $str = "{$day}:";
            foreach ($times as $time) {
                $str .= "{$time}, ";
            }
            $freeTimesText[] = rtrim($str, ", ");
        }


        // replace this example code with whatever you need
        return $this->render('default/answer.html.twig', array(
            "freeTimesText" => $freeTimesText,
        ));
    }

    private function addFreeTime($thisDay, $timeFrom, $timeTo, $minimumUnit, $interval){
        $diff = $timeFrom->diff($timeTo)->format("%h")*60 + $timeFrom->diff($timeTo)->format("%i") + $interval*2;
        if ($diff >= $minimumUnit) {
            $timeFrom->modify("{$interval} minutes");
            $timeTo->modify("-{$interval} minutes");
            $freeTimes[$thisDay][] = "{$timeFrom->format("H:i")}~{$timeTo->format("H:i")}";
        }
    }

    private function isEnoughFreeTime($timeFrom, $timeTo, $minimumUnit, $interval){
        $diff = $timeFrom->diff($timeTo)->format("%h")*60 + $timeFrom->diff($timeTo)->format("%i") + $interval*2;
        return $diff >= $minimumUnit ? true : false;
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
