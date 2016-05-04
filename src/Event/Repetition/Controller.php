<?php
namespace Phidias\Calendar\Event\Repetition;

use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Entity as Repetition;

class Controller
{
    private function sanitizeRepetitionData($repetitionData)
    {
        if (!is_object($repetitionData) || !isset($repetitionData->every) || !$repetitionData->every) {
            return null;
        }

        $repetitionData->every    = strtolower($repetitionData->every);

        if (isset($repetitionData->on)) {
            $repetitionData->on = is_array($repetitionData->on) ? strtolower(implode(", ", $repetitionData->on)) : strtolower($repetitionData->on);
        } else {
            $repetitionData->on = null;
        }

        $repetitionData->interval = isset($repetitionData->interval) ? $repetitionData->interval : 1;
        $repetitionData->count    = isset($repetitionData->count) ? $repetitionData->count : null;
        $repetitionData->until    = isset($repetitionData->until) ? $repetitionData->until : null;

        return $repetitionData;
    }

    private static function getWeekDayNumber($name)
    {
        if (is_numeric($name)) {
            return $name;
        }

        switch ( strtolower($name) ) {
            case 'mo':
            case 'mon':
            case 'monday':
                return 1;

            case 'tu':
            case 'tue':
            case 'tuesday':
                return 2;

            case 'we':
            case 'wed':
            case 'wednesday':
                return 3;

            case 'th':
            case 'thu':
            case 'thursday':
                return 4;

            case 'fr':
            case 'fri':
            case 'friday':
                return 5;

            case 'sa':
            case 'sat':
            case 'saturday':
                return 6;

            case 'su':
            case 'sun':
            case 'sunday':
                return 7;

            default:
                return null;
        }
    }

    public function save($eventId, $repetitionData)
    {
        //Clear all existing repetition data for this event
        Repetition::collection()
            ->match("event", $eventId)
            ->delete();

        $repetitionData = $this->sanitizeRepetitionData($repetitionData);
        if (!$repetitionData) {
            return;
        }

        $event = new Event($eventId);

        $repetitionCollection = Repetition::collection()->allAttributes();

        $repetition        = new Repetition;
        $repetition->event = $event->id;

        switch ($repetitionData->every) {
            case "day":
                $repetition->frequency = Repetition::FREQUENCY_DAILY;
            break;

            case "week":
                $repetition->frequency = Repetition::FREQUENCY_WEEKLY;
            break;

            case "month":
                $repetition->frequency = $repetitionData->on == "weekday" ? Repetition::FREQUENCY_MONTHLY_WEEKDAY : Repetition::FREQUENCY_MONTHLY_DAY;
            break;

            case "year":
                $repetition->frequency = Repetition::FREQUENCY_YEARLY;
            break;
        }

        $repetition->interval      = $repetitionData->interval;
        $repetition->count         = $repetitionData->count;
        $repetition->until         = $repetitionData->until;

        $repetition->day           = date('j', $event->start);
        $repetition->month         = date('n', $event->start);
        $repetition->year          = date('Y', $event->start);
        $repetition->weekDay       = date('N', $event->start);
        $repetition->weekDayN      = ceil($repetition->day/7);
        $repetition->weekDayIsLast = $repetition->day + 7 > date('t', $event->start);
        $repetition->seqDay        = ceil($event->start / 86400);
        $repetition->seqMonth      = $repetition->month + ($repetition->year - 1970)*12;

        $repetitionCollection->add($repetition);

        if ($repetition->frequency == Repetition::FREQUENCY_WEEKLY) {

            $weekDays = explode(",", trim($repetitionData->on));
            foreach ($weekDays as $weekDayName) {

                if (!$weekDayName = trim($weekDayName)) {
                    continue;
                }

                $weekDay = self::getWeekDayNumber($weekDayName);

                if ($weekDay === null || $repetition->weekDay == $weekDay) {
                    continue;
                }

                $dayRepetition = clone($repetition);

                $dayDate = $event->start + ($weekDay - $repetition->weekDay)*86400;

                $dayRepetition->day           = date('d', $dayDate);
                $dayRepetition->month         = date('n', $dayDate);
                $dayRepetition->year          = date('Y', $dayDate);
                $dayRepetition->weekDay       = date('N', $dayDate);
                $dayRepetition->weekDayN      = ceil($dayRepetition->day/7);
                $dayRepetition->weekDayIsLast = $dayRepetition->day + 7 > date('t', $dayDate);
                $dayRepetition->seqDay        = ceil($dayDate / 86400);
                $dayRepetition->seqMonth      = $dayRepetition->month + ($repetition->year - 1970)*12;

                $repetitionCollection->add($dayRepetition);

            }

        }

        $repetitionCollection->save();

        return $repetition;
    }


    public static function filterEventsInDateRange(\Phidias\Db\Orm\Collection $events, $startDate, $endDate = null)
    {
        if ($endDate === null || $endDate < $startDate) {
            $endDate = $startDate;
        }


        //First, condition the query to return only events that ocurr in the specified date range
        $conditions = array();

        //non repeating events ocurring within the date range
        $conditions[] = "(repetition.frequency IS NULL AND start >= $startDate AND (end IS NULL OR end <= $endDate))";

        //Repetition conditions for each day in the date range
        for ($date = $startDate; $date <= $endDate; $date = $date + 86400) {

            $day           = date('j', $date);
            $month         = date('n', $date);
            $year          = date('Y', $date);
            $weekDay       = date('N', $date);
            $weekDayN      = ceil($day/7);
            $weekDayIsLast = $day + 7 > date('t', $date);
            $seqDay        = ceil($date / 86400);
            $seqMonth      = $month + ($year - 1970)*12;

            $conditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_DAILY." AND ($seqDay - repetition.seqDay) % repetition.interval = 0)";
            $conditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_WEEKLY." AND repetition.weekDay = $weekDay) AND (($seqDay-repetition.seqDay)/7) % repetition.interval = 0";
            $conditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_MONTHLY_DAY." AND repetition.day = $day AND ($seqMonth - repetition.seqMonth) % repetition.interval = 0)";
            $conditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_MONTHLY_WEEKDAY." AND repetition.weekDay = $weekDay AND IF(repetition.weekDayN = 5, repetition.weekDayIsLast, repetition.weekDayN = $weekDayN) AND ($seqMonth-repetition.seqMonth) % repetition.interval=0)";
            $conditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_YEARLY." AND repetition.day = $day AND repetition.month = $month AND ($year - repetition.year) % repetition.interval = 0)";
        }

        $events->where(implode(" OR ", $conditions));


        //Fetch all repetition data for each event
        $events->attribute("repetition", Repetition::collection()
            ->allAttributes()
        );


        //Now, in post-processing, determine in which day in the date range the event ocurred, and create
        //an entry in the "occurrences" attribute


        $events->addFilter(function($event) use ($startDate, $endDate) {

            if (!isset($event->occurrences)) {
                $event->occurrences = array();
            }


            for ($date = $startDate; $date <= $endDate; $date = $date + 86400) {

                $eventDay = mktime(0, 0, 0, date("m", $event->start), date("d", $event->start), date("Y", $event->start));
                if ($eventDay > $date) {
                    continue;
                }

                foreach ($event->repetition as $repetition) {

                    $day           = date('j', $date);
                    $month         = date('n', $date);
                    $year          = date('Y', $date);
                    $weekDay       = date('N', $date);
                    $weekDayN      = ceil($day/7);
                    $weekDayIsLast = $day + 7 > date('t', $date);
                    $seqDay        = ceil($date / 86400);
                    $seqMonth      = $month + ($year - 1970)*12;

                    if (
                           $repetition->frequency == Repetition::FREQUENCY_DAILY && ($seqDay - $repetition->seqDay) % $repetition->interval == 0
                        || $repetition->frequency == Repetition::FREQUENCY_WEEKLY && ($repetition->weekDay == $weekDay) && (($seqDay-$repetition->seqDay)/7) % $repetition->interval == 0
                        || $repetition->frequency == Repetition::FREQUENCY_MONTHLY_DAY && ($repetition->day == $day) && ($seqMonth - $repetition->seqMonth) % $repetition->interval == 0
                        || $repetition->frequency == Repetition::FREQUENCY_MONTHLY_WEEKDAY && ($repetition->weekDay == $weekDay) && ($repetition->weekDayN == 5 ? $repetition->weekDayIsLast : $repetition->weekDayN == $weekDayN) && ($seqMonth - $repetition->seqMonth) % $repetition->interval == 0
                        || $repetition->frequency == Repetition::FREQUENCY_YEARLY && ($repetition->day == $day) && ($repetition->month == $month) && ($year - $repetition->year) % $repetition->interval == 0
                    ) {

                        $childEvent = clone($event);
                        $childEvent->start = mktime(date('h', $event->start), date('i', $event->start), date('s', $event->start), date('m', $date), date('d', $date), date('y', $date));
                        unset($childEvent->occurrences);
                        unset($childEvent->repetition);

                        $event->occurrences[] = $childEvent;
                    }
                }

            }


        });

    }

}