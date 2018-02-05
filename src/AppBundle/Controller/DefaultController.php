<?php

//　リファクタ


namespace AppBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ScheduleSetting;
use AppBundle\Entity\User;
use AppBundle\Form\ScheduleSettingType;

// use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


class DefaultController extends BaseController
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
            'action' => $this->generateUrl('yotei-kun-result'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'post'));
        return $form;
    }


    /**
     * @Route("/result
     ", name="yotei-kun-form-post")
     * @Method("POST")
     */
    public function formPostAction(Request $request)
    {            
        $scheduleSettingEntity = new ScheduleSetting();
        $form = $this->createScheduleSettingForm($scheduleSettingEntity);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            dump($form->getErrors(true));
            exit;
            return $this->redirect($this->generateUrl('yotei-kun'));
        }
        $em = $this->getDoctrine()->getManager();
        $client = $this->createGoogleClient();

        $accessToken = $this->getAttribute("accessToken");
        if ($accessToken) {
            $client = $this->createGoogleClient();
            $client->setAccessToken($accessToken);
            // tokenはあっても、期限が切れている場合
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            if ($client->getAccessToken()) {
              $token_data = $client->verifyIdToken();
            }
            $freeTimesText = $this->getFreetime($client, $this->getAttribute("post_data"));
            $this->setAttribute("accessToken", $accessToken);
            return $this->render('default/result.html.twig', array(
                "freeTimesText" => $freeTimesText,
                "email" => $token_data["email"],
            ));
        }else{
            $em->persist($scheduleSettingEntity);
            $em->flush();
            $this->setAttribute("post_data", $form->getData());
            $authUrl = $client->createAuthUrl();
            return $this->redirect($authUrl);
        }
    }

    private function createGoogleClient(){
        $client = new \Google_Client();
        $client->setApplicationName("yotei-kun");
        $client->addScope(implode(' ', array(\Google_Service_Calendar::CALENDAR_READONLY)));
        $client->addScope("email");
        $client->setAuthConfig($this->get('kernel')->getRootDir()."/client_secret.json");
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        return $client;
    }

    /**
     * @Route("/result", name="yotei-kun-result")
     * @Method("GET")
     */
    public function resultAction(Request $request)
    {   
        $authCode = $request->query->get('code');
        $client = $this->createGoogleClient();
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);
        if ($client->getAccessToken()) {
          $token_data = $client->verifyIdToken();
        }
        $freeTimesText = $this->getFreetime($client, $this->getAttribute("post_data"));
        $this->setAttribute("accessToken", $accessToken);
        return $this->render('default/result.html.twig', array(
            "freeTimesText" => $freeTimesText,
            "email" => $token_data["email"],
        ));
    }


    private function getFreetime($client, $scheduleSettingEntity){
        $calendarService = new \Google_Service_Calendar($client);
        $calendarList = $calendarService->calendarList->listCalendarList();
        $calendarArray = [];
        // 認証したユーザーの情報
        $token_data = $client->verifyIdToken();

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

        $beforeBusyEndTime;
        $i = 0;
        $busyArray = $results->getCalendars()[$token_data["email"]]->getBusy();
        $isTodayFirstBusy = true;
        $minimumUnit = $scheduleSettingEntity->getMinimumUnit();
        $intervals = $scheduleSettingEntity->getIntervals();
        
        $dateInterval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($scheduleSettingEntity->getDayFrom(), $dateInterval ,$scheduleSettingEntity->getDayTo());
        // dump($scheduleSettingEntity->getTimeFrom());

        foreach ($daterange as $date) {
            $this->freeTimes[$date->format("Y年m月d日")] = ["{$scheduleSettingEntity->getTimeFrom()->format("H:i")}~{$scheduleSettingEntity->getTimeTo()->format("H:i")}"];
            // $this->freeTimes[$date->format("Y年m月d日")] = ["終日"];
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

            if ($startDateTime->format("Y年m月d日") == "2018年01月18日") {
                // dump("ビンゴ");
            }
            // dump($this->freeTimes[$startDateTime->format("Y年m月d日")]);
            // dump(["{$scheduleSettingEntity->getTimeFrom()->format("H:i")}~{$scheduleSettingEntity->getTimeTo()->format("H:i")}"]);
            if ($this->freeTimes[$startDateTime->format("Y年m月d日")] == ["{$timeFrom->format("H:i")}~{$timeTo->format("H:i")}"]) {
                $this->freeTimes[$startDateTime->format("Y年m月d日")] = null;

            }
            if ($timeFrom < $startDateTime) {
                if($isTodayFirstBusy){// その日最初の予定だった場合
                    // dump(0);
                    $this->addFreeTime($startDateTime, $timeFrom, $startDateTime, $minimumUnit, $intervals, 1);
                }else{
                    // その予定のstartTimeがtimeToよりも後ならtimeToを使うべき
                    if ($startDateTime > $timeTo) {
                        // dump(1);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $intervals);
                        // dump($timeTo);
                    }else{
                        // dump(2);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $startDateTime, $minimumUnit, $intervals);
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
                            // dump(3);
                            $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $intervals, 2);
                        }else{
                            // dump(4);
                            $this->addFreeTime($startDateTime, $endDateTime, $timeTo, $minimumUnit, $intervals, 2);
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
                        // dump(5);
                        $this->addFreeTime($startDateTime, $beforeBusyEndTime, $timeTo, $minimumUnit, $intervals, 2);
                    }else{
                        // dump(6);
                        $this->addFreeTime($startDateTime, $endDateTime, $timeTo, $minimumUnit, $intervals, 2);
                    }
                }
            }
            $i++;
            $isTodayFirstBusy = false;
        }
        ksort($this->freeTimes);

        $freeTimesText = [];
        foreach ($this->freeTimes as $day => $times) {
            $str = "{$day}:";
            if ($times) {
                foreach ((array)$times as $time) {
                    $str .= "{$time}, ";
                }
                $freeTimesText[] = rtrim($str, ", ");
            }
        }
        return $freeTimesText;
    }

    private function addFreeTime($thisDay, $timeFrom, $timeTo, $minimumUnit, $intervals, $type=0){
        $from = clone $timeFrom;
        $to = clone $timeTo;
        $freeTime = $from->diff($to)->format("%h")*60 + $from->diff($to)->format("%i");
        $minimumUnit += $intervals*2;
        if ($freeTime >= $minimumUnit) {
            if ($type == 0 || $type == 2) {
                $from->modify("{$intervals} minutes");
            }
            if ($type == 0 || $type == 1) {
                $to->modify("-{$intervals} minutes");
            }
            $this->freeTimes[$thisDay->format("Y年m月d日")][] = "{$from->format("H:i")}~{$to->format("H:i")}";
        }
    }
}
