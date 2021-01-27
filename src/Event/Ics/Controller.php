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
            $clean_description_html = html_entity_decode($postEvent->post->description);
            $clean_description = html_entity_decode($postEvent->post->description);
            $clean_description = self::abc($clean_description);
            
            $postData = "DESCRIPTION:{$clean_description}";
            $postData .= "CATEGORY:{$postEvent->post->type->plural}".PHP_EOL;
        }
        
        if ($event->repetition) {
            $rules = [];
            switch ($event->repetition->frequency) {
                case Repetition::FREQUENCY_DAILY:
                    $rules[] = "FREQ=DAILY".PHP_EOL;
                    break;

                case Repetition::FREQUENCY_WEEKLY:
                    $rules[] = "FREQ=WEEKLY"PHP_EOL;
                    $rules[] = "BYDAY=" . strtoupper(substr(date("D", $event->startDate),0,2))PHP_EOL;
                    break;

                case Repetition::FREQUENCY_MONTHLY_DAY:
                    $rules[] = "FREQ=MONTHLY"PHP_EOL;
                    $rules[] = "BYMONTHDAY=" . date("j", $event->startDate)PHP_EOL;
                    break;

                case Repetition::FREQUENCY_MONTHLY_WEEKDAY:
                    $rules[] = "FREQ=MONTHLY"PHP_EOL;
                    $rules[] = "BYSETPOS=" . ceil(date("j", $event->startDate)/7)PHP_EOL;
                    break;

                case Repetition::FREQUENCY_YEARLY:
                    $rules[] = "FREQ=YEARLY"PHP_EOL;
                    break;
            }

            $rules[] = "INTERVAL={$event->repetition->interval}".PHP_EOL;

            if ($event->repetition->count) {
                $rules[] = "COUNT={$event->repetition->count}".PHP_EOL;
            }

            $rRule = "\nRRULE:" . implode(";", $rules);
        } else {
            $rRule = "";
        }

        $output = "";
        if ($event->allDay) {
            $startDate = date('Ymd', $event->startDate);
            $endDate   = date('Ymd', $event->endDate + 86400);

            $output = "BEGIN:VEVENT".PHP_EOL;
            $output .= "UID:{$event->id}".PHP_EOL;
            $output .= "DTSTART;VALUE=DATE:{$startDate}".PHP_EOL;
            $output .= "DTEND;VALUE=DATE:{$endDate}".PHP_EOL;
            $output .= "SUMMARY:{$event->title}{$rRule}{$postData}".PHP_EOL;
            $output .= "END:VEVENT".PHP_EOL;           

        } else {

            $output = "BEGIN:VEVENT".PHP_EOL;
            $output .= "UID:{$event->id}".PHP_EOL;
            $output .= "DTSTAMP:{$creationDate}".PHP_EOL;
            $output .= "DTSTART:{$startDate}".PHP_EOL;
            $output .= "DTEND:{$endDate}".PHP_EOL;
            $output .= "SUMMARY:{$event->title}{$rRule}{$postData}".PHP_EOL;
            $output .= "END:VEVENT".PHP_EOL;
        }

        return $output;

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
END:VCALENDAR".PHP_EOL;
    }

    public static function abc($htmlMsg)
    {
        $temp = str_replace("</p>","\n",$htmlMsg);
        $temp = str_replace("<p>","",$temp);

        $lines = explode("\n",$temp);
        $new_lines = array();
        foreach($lines as $i => $line)
        {
            if( !empty($line) && strlen(trim($line)) > 0)
            {
                $new_lines[]=trim($line);
            }
        }
        $desc = implode("\r\n".PHP_EOL,$new_lines);
        
        return $desc;
    }
}