<?php
namespace Phidias\Calendar\Event\Ics;

use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Entity as Repetition;

use Phidias\Post\Entity as Post;
use Phidias\Core\Noun\Entity as PostType;
use Phidias\Calendar\Post\Event\Entity as PostEvent;

class Controller
{
    public static function toIcs(Event $event)
    {
        $creationDate = gmdate('Ymd\THis\Z', $event->creationDate);
        $startDate    = gmdate('Ymd\THis\Z', $event->startDate);
        $endDate      = gmdate('Ymd\THis\Z', $event->endDate);

        $postEvent = PostEvent::single()
            ->attribute("post", Post::single()
                    ->allAttributes()
                    ->attribute("type", PostType::single()->allAttributes())
                    )
            ->where("event = :evtId", ["evtId" => $event->id])
            ->find()
            ->first();

        $postData = "";
        
        if($postEvent){
            $clean_description = html_entity_decode(strip_tags($postEvent->post->description));
            $clean_description = str_replace( "<p>", '', $clean_description ); 
            $clean_description = str_replace( "</p>", '\n', $clean_description ); 

            $postData = "
DESCRIPTION:{$clean_description}
CATEGORY:{$postEvent->post->type->plural}";
        }
        
        if ($event->repetition) {
            $rules = [];
            switch ($event->repetition->frequency) {
                case Repetition::FREQUENCY_DAILY:
                    $rules[] = "FREQ=DAILY";
                    break;

                case Repetition::FREQUENCY_WEEKLY:
                    $rules[] = "FREQ=WEEKLY";
                    $rules[] = "BYDAY=" . strtoupper(substr(date("D", $event->startDate),0,2));
                    break;

                case Repetition::FREQUENCY_MONTHLY_DAY:
                    $rules[] = "FREQ=MONTHLY";
                    $rules[] = "BYMONTHDAY=" . date("j", $event->startDate);
                    break;

                case Repetition::FREQUENCY_MONTHLY_WEEKDAY:
                    $rules[] = "FREQ=MONTHLY";
                    $rules[] = "BYSETPOS=" . ceil(date("j", $event->startDate)/7);
                    break;

                case Repetition::FREQUENCY_YEARLY:
                    $rules[] = "FREQ=YEARLY";
                    break;
            }

            $rules[] = "INTERVAL={$event->repetition->interval}";

            if ($event->repetition->count) {
                $rules[] = "COUNT={$event->repetition->count}";
            }

            $rRule = "\nRRULE:" . implode(";", $rules);
        } else {
            $rRule = "";
        }

        if ($event->allDay) {
            $startDate = date('Ymd', $event->startDate);
            $endDate   = date('Ymd', $event->endDate + 86400);

            return "BEGIN:VEVENT
UID:{$event->id}
DTSTART;VALUE=DATE:{$startDate}
DTEND;VALUE=DATE:{$endDate}
SUMMARY:{$event->title}{$rRule}{$postData}
END:VEVENT";

        } else {

            return "BEGIN:VEVENT
UID:{$event->id}
DTSTAMP:{$creationDate}
DTSTART:{$startDate}
DTEND:{$endDate}
SUMMARY:{$event->title}{$rRule}{$postData}
END:VEVENT";
        }

    }

    public static function toCalendar(array $events)
    {
        $allEvents = [];
        foreach ($events as $event) {
            $allEvents[] = self::toIcs($event);
        }
        $allEvents = implode("\n", $allEvents);

        return "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Phidias//NONSGML Phidias Academico//EN
NAME:Phidias Academico
X-WR-CALNAME:Phidias Academico
DESCRIPTION:Mis eventos en el colegio
X-WR-CALDESC:Mis eventos en el colegio
{$allEvents}
END:VCALENDAR";
    }

    public function main($eventId)
    {
        $event = Event::single()
            ->allAttributes()
            ->attribute("repetition", Repetition::single()->allAttributes())
            ->fetch($eventId);

        $icsEvent = self::toIcs($event);

        return "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Phidias//NONSGML Phidias Academico//EN
{$icsEvent}
END:VCALENDAR";
    }
}