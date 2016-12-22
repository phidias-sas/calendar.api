<?php
namespace Phidias\Calendar\Event\Ics;
use Phidias\Calendar\Event\Entity as Event;

class Controller
{
    public function main($eventId)
    {
        $event = new Event($eventId);

        $creationDate = date('Ymd\THis\Z', $event->creationDate);
        $startDate    = date('Ymd\THis\Z', $event->startDate);
        $endDate      = date('Ymd\THis\Z', $event->endDate);

        $icsContents = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Phidias//NONSGML Phidias Academico//EN
BEGIN:VEVENT
UID:{$event->id}
DTSTAMP:{$creationDate}
DTSTART:{$startDate}
DTEND:{$endDate}
SUMMARY:{$event->title}
END:VEVENT
END:VCALENDAR";

        return $icsContents;
    }
}