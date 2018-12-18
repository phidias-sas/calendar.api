<?php
namespace Phidias\Calendar\Event;

use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Entity as Repetition;
use Phidias\Calendar\Event\Repetition\Controller as RepetitionController;

class Controller
{
    public function collection()
    {
        return Event::collection()
            ->allAttributes()
            ->attribute("repetition", Repetition::single()->allAttributes());
    }

    public function feed($startDate, $endDate)
    {
        $startDate = is_numeric($startDate) ? $startDate : strtotime($startDate);
        $endDate   = is_numeric($endDate)   ? $endDate   : strtotime($endDate);

        $events = Event::collection()->allAttributes();
        RepetitionController::filterEventsInDateRange($events, $startDate, $endDate);

        return $events;
    }

    public function details($eventId)
    {
        return new Event($eventId);
    }

    public function save($eventData, $eventId = null)
    {
        $event         = new Event($eventId);
        $event->allDay = 0;
        $event->setValues($eventData);

        $event->startDate        = !is_numeric($event->startDate) ? strtotime($event->startDate) : $event->startDate;
        $event->endDate          = !is_numeric($event->endDate)   ? strtotime($event->endDate)   : $event->endDate;
        $event->creationDate     = $event->creationDate ? $event->creationDate : time();
        $event->modificationDate = time();
        $event->save();

        RepetitionController::repeat($event, isset($eventData->repeat) ? $eventData->repeat : null);

        return $event;
    }

    public function delete($eventId)
    {
        $event = new Event($eventId);
        $event->delete();

        return $event;
    }
}