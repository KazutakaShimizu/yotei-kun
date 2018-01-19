<?php

// next todo
// 処理の見直し
// minimumUnitを考慮する
// 　→datetimeで考える必要があるかも
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
        $freebusy->setTimeMin(date("c", strtotime("2018-01-21")));
        $freebusy->setTimeMax(date("c", strtotime("2018-01-21")));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);

        $freeTimes = [];
        // 前の予定の終わった時間
        $beforeBusyEndTime = "0時";
        foreach ($results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy() as $busy) {
            // 予定の開始時間や終わり時間など
            $startYear = date("Y年", strtotime($busy->getStart()));
            $startMonth = date("m月", strtotime($busy->getStart()));
            $startDay = date("d日", strtotime($busy->getStart()));
            $startTime = date("H時", strtotime($busy->getStart()));
            $endYear = date("Y年", strtotime($busy->getEnd()));
            $endMonth = date("m月", strtotime($busy->getEnd()));
            $endDay = date("d日", strtotime($busy->getEnd()));
            $endTime = date("H時", strtotime($busy->getEnd()));

            if (!isset($freeTimes["{$startYear}{$startMonth}{$startDay}"])) {
                $beforeBusyEndTime = "0時";
                $freeTimes["{$startYear}{$startMonth}{$startDay}"][] = "{$beforeBusyEndTime}~{$startTime}";
            }else{
                $freeTimes["{$startYear}{$startMonth}{$startDay}"][] = "{$beforeBusyEndTime}~{$startTime}";
            }
            $beforeBusyEndTime = $endTime;
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
        $freebusy->setTimeMin(date("c", strtotime("2018-01-22")));
        $freebusy->setTimeMax(date("c", strtotime("2018-02-23")));
        $freebusy->setTimeZone('Asia/Tokyo');
        $freebusy->setItems($calendarArray);
        $results = $calendarService->freebusy->query($freebusy);

        $freeTimes = [];
        // 前の予定の終わった時間
        $beforeBusyEndTime = $scheduleSettingEntity->getTimeFrom()->format('H00');
        // 空き時間を出力する範囲の開始
        $timeFrom = $scheduleSettingEntity->getTimeFrom()->format('H00');
        // 空き時間を出力する範囲の終了
        $timeTo = $scheduleSettingEntity->getTimeTo()->format('H00');
        foreach ($results->getCalendars()["s.kazutaka55555@gmail.com"]->getBusy() as $busy) {
            // 予定の開始関連
            $startYear = date("Y", strtotime($busy->getStart()));
            $startMonth = date("m", strtotime($busy->getStart()));
            $startDay = date("d", strtotime($busy->getStart()));
            $startTime = date("Hi", strtotime($busy->getStart()));

            // 予定の終了関連
            $endYear = date("Y", strtotime($busy->getEnd()));
            $endMonth = date("m", strtotime($busy->getEnd()));
            $endDay = date("d", strtotime($busy->getEnd()));
            $endTime = date("Hi", strtotime($busy->getEnd()));

            // その日最初の予定の場合は、beforeBusyEndTimeをリセットする
            if (!array_key_exists("{$startYear}年{$startMonth}月{$startDay}日", $freeTimes)){
                $beforeBusyEndTime =$scheduleSettingEntity->getTimeFrom()->format('Hi');
            }
            
            if ($timeFrom > $endTime) {// 範囲の開始時間の方が、予定の終わり時間よりもおそい場合
                if ($startTime > $timeTo) {// 予定の開始時間の方が、範囲の開始時間よりも遅い
                    $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "{$beforeBusyEndTime}~{$timeTo}";
                }else{// 予定の開始時間の方が、範囲の開始時間よりも早い
                    $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "skip";
                }
                $beforeBusyEndTime = $endTime;
            }elseif($timeFrom >= $startTime && $timeFrom <= $endTime){// 範囲の開始時間の方が、予定の開始時間よりもおそく、予定の終わり時間よりも早い
                $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "skip";
                $beforeBusyEndTime = $endTime;
            }else{// 開始時間の方が、予定の終わり時間よりもおそい場合
                $freeTimes["{$startYear}年{$startMonth}月{$startDay}日"][] = "{$beforeBusyEndTime}~{$startTime}";
                $beforeBusyEndTime = $endTime;
            }
            $beforeBusyYearMonthDate = "{$startYear}年{$startMonth}月{$startDay}日";
        }

        $freeTimesText = [];
        foreach ($freeTimes as $day => $times) {
            $str = "{$day}:";
            foreach ($times as $time) {
                if ($time !== "skip") {
                    $time = substr_replace($time, ':', 2, 0);
                    $time = substr_replace($time, ':', 8, 0);
                    $str .= "{$time}, ";
                }
            }
            $freeTimesText[] = rtrim($str, ", ");
        }

        // replace this example code with whatever you need
        return $this->render('default/answer.html.twig', array(
            "freeTimesText" => $freeTimesText,
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
