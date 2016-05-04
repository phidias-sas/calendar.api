<?php
namespace Phidias\Calendar\Event;

use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Controller as RepetitionController;

class Controller
{
    public function collection()
    {
        return Event::collection()->allAttributes();
    }

    public function feed($startDate, $endDate = null)
    {
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
        $event = new Event($eventId);
        $event->setValues($eventData);
        $event->save();

        return $event;
    }

    public function delete($eventId)
    {
        $event = new Event($eventId);
        $event->delete();

        return $event;
    }
}