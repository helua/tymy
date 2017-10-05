<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

class EventPresenter extends SecuredPresenter {

    /** @var \Tymy\Event @inject */
    public $event;

    /** @var \Tymy\Attendance @inject */
    public $attendance;
    private $eventsFrom;
    private $eventsTo;
    private $eventsJSObject;
    private $eventsMonthly;

    public function startup() {
        parent::startup();
        $this->setLevelCaptions(["1" => ["caption" => "Události", "link" => $this->link("Event:")]]);

        $this->template->addFilter('genderTranslate', function ($gender) {
            switch ($gender) {
                case "MALE": return "Muži";
                case "FEMALE": return "Ženy";
                case "UNKNOWN": return "Nezadáno";
            }
        });

        $this->template->addFilter("prestatusClass", function ($myPreStatus, $myPostStatus, $btn, $startTime) {
            $color = $this->supplier->getStatusClass($btn);
            if (strtotime($startTime) > strtotime(date("c")))// pokud podminka plati, akce je budouci
                return $btn == $myPreStatus ? "btn-outline-$color active" : "btn-outline-$color";
            else if ($myPostStatus == "not-set") // akce uz byla, post status nevyplnen
                return $btn == $myPreStatus && $myPreStatus != "not-set" ? "btn-outline-$color disabled active" : "btn-outline-secondary disabled";
            else
                return $btn == $myPostStatus && $myPostStatus != "not-set" ? "btn-outline-$color disabled active" : "btn-outline-secondary disabled";
        });
    }

    public function renderDefault($date = NULL, $direction = NULL) {
        try {
            $this->events = $this->events->loadYearEvents($date, $direction);
            $eventTypes = $this->eventTypes->getData();
        } catch (\Tymy\Exception\APIException $ex) {
            $this->handleTapiException($ex);
        }


        foreach ($this->events->eventsMonthly as $eventMonth) {
            foreach ($eventMonth as $event) {
                $eventCaptions = $this->getEventCaptions($event, $eventTypes);
                $event->myPreStatusCaption = $eventCaptions["myPreStatusCaption"];
                $event->myPostStatusCaption = $eventCaptions["myPostStatusCaption"];
            }
        }
        $this->template->agendaFrom = date("Y-m", strtotime($this->events->eventsFrom));
        $this->template->agendaTo = date("Y-m", strtotime($this->events->eventsTo));
        $this->template->currY = date("Y");
        $this->template->currM = date("m");
        $this->template->evMonths = $this->events->eventsMonthly;
        $this->template->events = $this->events->eventsJSObject;
        $this->template->eventTypes = $eventTypes;
        if ($this->isAjax()) {
            foreach ($this->events->eventsJSObject as &$eventJs) {
                $eventJs->url = $this->link("Event:event", $eventJs->webName);
                unset($eventJs->webName);
                $eventJs->stick = true;
            }
            $this->payload->events = $this->events->eventsJSObject;
        }
    }

    public function renderEvent($udalost) {
        
        try {
            $event = $this->event
                    ->reset()
                    ->recId($this->parseIdFromWebname($udalost))
                    ->getData();
            $eventTypes = $this->eventTypes->getData();
            $users = $this->users->getResult();
        } catch (\Tymy\Exception\APIException $ex) {
            $this->handleTapiException($ex);
        }
        
        $this->setLevelCaptions(["2" => ["caption" => $event->caption, "link" => $this->link("Event:event", $event->id . "-" . $event->webName)]]);

        //array keys are pre-set for sorting purposes
        $attArray = [];
        $attArray["YES"] = NULL;
        $attArray["LAT"] = NULL;
        $attArray["DKY"] = NULL;
        $attArray["NO"] = NULL;
        $attArray["UNKNOWN"] = NULL;

        foreach ($event->attendance as $attendee) {
            $user = $users->data[$attendee->userId];
            if ($user->status != "PLAYER")
                continue; // display only players on event detail
            $gender = $user->gender;
            $user->preDescription = $attendee->preDescription;
            $attArray[$attendee->preStatus][$gender][$attendee->userId] = $user;
        }

        $event->allUsers = $attArray;
        $this->template->event = $event;
        $this->template->eventTypes = $eventTypes;
        $eventCaptions = $this->getEventCaptions($event, $eventTypes);
        $this->template->myPreStatusCaption = $eventCaptions["myPreStatusCaption"];
        $this->template->myPostStatusCaption = $eventCaptions["myPostStatusCaption"];
    }

    public function handleAttendance($id, $code, $desc) {
        try {
            $this->attendance
                    ->recId($id)
                    ->setPreStatus($code)
                    ->setPreDescription($desc)
                    ->plan();
        } catch (\Tymy\Exception\APIException $ex) {
            $this->handleTapiException($ex);
        }
        if ($this->isAjax()) {
            $this->redrawControl("attendanceWarning");
            $this->redrawControl("attendanceTabs");
        }
    }
    
    public function handleAttendanceResult($id) {
        $results = $this->getRequest()->getPost()["resultSet"];
        try {
            $this->attendance
                    ->recId($id)
                    ->confirm($results);
        } catch (\Tymy\Exception\APIException $ex) {
            $this->handleTapiException($ex);
        }
        if ($this->isAjax()) {
            $this->redrawControl("attendanceTabs");
        }
    }


    public function handleEventLoad($date = NULL, $direction = NULL) {
        $this->redrawControl("events-agenda");
    }

    private function getEventCaptions($event, $eventTypes) {
        return [
            "myPreStatusCaption" => $event->myAttendance->preStatus == "UNKNOWN" ? "not-set" : $eventTypes[$event->type]->preStatusSet[$event->myAttendance->preStatus]->code,
            "myPostStatusCaption" => $event->myAttendance->postStatus == "UNKNOWN" ? "not-set" : $eventTypes[$event->type]->postStatusSet[$event->myAttendance->postStatus]->code,
        ];
    }

}
