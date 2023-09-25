<?php
namespace Phidias\Calendar\Event\Repetition;

use DateTime;
use DateTimeZone;
use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Entity as Repetition;

class Controller
{
    /*
    A valid repetitionData is an object with:
    {
        every: [day|week|month|year],
        on: [If every is "month" on can be "weekday" | If every is "day" on is an array or list of days]
        interval:  how often the recurrence rule repeats.  e.g.  Every 4 days
        count: optional.  Number of events in the series
        until: optional. Date when repetition series ends
    }
    */
    public static function sanitizeRepetitionData($repetitionData)
    {
        /* Parse string "RRULE" (e.g. RRULE:FREQ=WEEKLY;COUNT=3;BYDAY=TU,WE,TH) */
        if (is_string($repetitionData)) {
            $repetitionData = self::getRepetitionDataFromString($repetitionData);
        }

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

    private static function getRepetitionDataFromString($string)
    {
        $retval = new \stdClass;

        $parts = explode(":", $string);
        if (count($parts) != 2 || $parts[0] != "RRULE") {
            return null;
        }

        $data = [];
        foreach (explode(";", $parts[1]) as $rule) {
            list($name, $value) = explode('=', $rule);
            $data[$name] = $value;
        }

        if (!isset($data["FREQ"])) {
            return null;
        }

        switch (strtolower($data["FREQ"])) {
            case "daily":
                $retval->every = "day";
            break;

            case "weekly":
                $retval->every = "week";
                $retval->on    = isset($data["BYDAY"]) ? $data["BYDAY"] : null;
            break;

            case "monthly":
                $retval->every = "month";
                $retval->on    = isset($data["BYDAY"]) ? "weekday" : null;
            break;

            case "yearly":
                $retval->every = "year";
            break;

            default:
                return null;
        }

        $retval->interval = isset($data["INTERVAL"]) ? $data["INTERVAL"] : 1;
        $retval->count    = isset($data["COUNT"]) ? $data["COUNT"] : null;

        if (isset($data["UNTIL"])) {
            $retval->until = (new DateTime($data["UNTIL"]))->getTimestamp();
        }

        return $retval;
    }

    private static function daysSinceEpoch($date)
    {
        $date = mktime(0, 0, 0, date("m", $date), date("d", $date), date("Y", $date));
        $utc_timezone = new DateTimeZone("UTC");
        $current = new DateTime('@'.$date);
        $epoch   = new DateTime('1970-01-01',$utc_timezone);
        $diff    = $current->diff($epoch);
        return (int)$diff->format('%a');
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

    public static function repeat($event, $repetitionData)
    {
        //Clear all existing repetition data for this event
        Repetition::collection()
            ->match("event", $event->id)
            ->delete();

        $repetitionData = self::sanitizeRepetitionData($repetitionData);
        if (!$repetitionData) {
            return;
        }

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

        $repetition->day           = date('j', $event->startDate);
        $repetition->month         = date('n', $event->startDate);
        $repetition->year          = date('Y', $event->startDate);
        $repetition->weekDay       = date('N', $event->startDate);
        $repetition->weekDayN      = ceil($repetition->day/7);
        $repetition->weekDayIsLast = $repetition->day + 7 > date('t', $event->startDate);
        $repetition->seqDay        = self::daysSinceEpoch($event->startDate);
        $repetition->seqMonth      = $repetition->month + ($repetition->year - 1970)*12;

        $repetitionCollection->add($repetition);

        if ($repetition->frequency == Repetition::FREQUENCY_WEEKLY) {

            if (is_array($repetitionData->on)) {
                $weekDays = $repetitionData->on;
            } else {
                $weekDays = explode(",", trim($repetitionData->on));
            }

            $seenWeekdays = [];
            $seenWeekdays[$repetition->weekDay] = true;

            foreach ($weekDays as $weekDayName) {

                if (!$weekDayName = trim($weekDayName)) {
                    continue;
                }

                $weekDay = self::getWeekDayNumber($weekDayName);

                if ($weekDay === null || isset($seenWeekdays[$weekDay]) ) {
                    continue;
                }

                $seenWeekdays[$weekDay] = true;

                $dayRepetition = clone($repetition);

                $offset = $weekDay - $repetition->weekDay;
                if ($offset < 0) {
                    $offset = $offset + 7;
                }
                $dayDate = $event->startDate + $offset*86400;

                $dayRepetition->day           = date('d', $dayDate);
                $dayRepetition->month         = date('n', $dayDate);
                $dayRepetition->year          = date('Y', $dayDate);
                $dayRepetition->weekDay       = date('N', $dayDate);
                $dayRepetition->weekDayN      = ceil($dayRepetition->day/7);
                $dayRepetition->weekDayIsLast = $dayRepetition->day + 7 > date('t', $dayDate);
                $dayRepetition->seqDay        = self::daysSinceEpoch($dayDate);
                $dayRepetition->seqMonth      = $dayRepetition->month + ($repetition->year - 1970)*12;

                $repetitionCollection->add($dayRepetition);
            }
        }

        $repetitionCollection->save();

        return $repetition;
    }

    /*
    Obtains the given event's "repeat" property
    from DB data
    */
    public static function getRepeat($event)
    {
        $repetitions = Repetition::collection()
            ->attributes("frequency", "interval", "count", "until", "weekDay")
            ->match("event", $event->id)
            ->find();

        if (!$repetitions->getNumRows()) {
            return null;
        }

        $retval = new \stdClass;

        foreach ($repetitions as $repetition) {

            if (!isset($retval->every)) {

                switch($repetition->frequency) {
                    case Repetition::FREQUENCY_DAILY:
                        $retval->every = "day";
                    break;

                    case Repetition::FREQUENCY_WEEKLY:
                        $retval->every = "week";
                        $retval->on    = [];
                    break;

                    case Repetition::FREQUENCY_MONTHLY_DAY:
                        $retval->every = "month";
                    break;

                    case Repetition::FREQUENCY_MONTHLY_WEEKDAY:
                        $retval->every = "month";
                        $retval->on    = "weekday";
                    break;

                    case Repetition::FREQUENCY_YEARLY:
                        $retval->every = "year";
                    break;
                }
            }

            $retval->interval = $repetition->interval;
            $retval->count    = $repetition->count;
            $retval->until    = $repetition->until;

            if ($repetition->frequency == Repetition::FREQUENCY_WEEKLY) {
                $retval->on[] = (int)$repetition->weekDay;
            }

        }

        return $retval;
    }


    public static function filterEventsInDateRange(\Phidias\Db\Orm\Collection $events, $startDate, $endDate)
    {
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        //First, condition the query to return only events that ocurr in the specified date range
        $conditions = [];

        //non repeating events ocurring within the date range
        $conditions[] = "(repetition.id IS NULL AND startDate <= $endDate AND endDate >= $startDate)";

        //Repetition conditions for each day in the date range
        for ($date = $startDate; $date <= $endDate; $date = $date + 86400) {

            $day           = date('j', $date);
            $month         = date('n', $date);
            $year          = date('Y', $date);
            $weekDay       = date('N', $date);
            $weekDayN      = ceil($day/7);
            $weekDayIsLast = $day + 7 > date('t', $date);
            $seqDay        = self::daysSinceEpoch($date);
            $seqMonth      = $month + ($year - 1970)*12;

            $dayConditions = [];
            $dayConditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_DAILY." AND ($seqDay - repetition.seqDay) % repetition.interval = 0)";
            $dayConditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_WEEKLY." AND repetition.weekDay = $weekDay AND FLOOR(($seqDay - repetition.seqDay)/7) % repetition.interval = 0)";
            $dayConditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_MONTHLY_DAY." AND repetition.day = $day AND ($seqMonth - repetition.seqMonth) % repetition.interval = 0)";
            $dayConditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_MONTHLY_WEEKDAY." AND repetition.weekDay = $weekDay AND IF(repetition.weekDayN = 5, repetition.weekDayIsLast, repetition.weekDayN = $weekDayN) AND ($seqMonth-repetition.seqMonth) % repetition.interval = 0)";
            $dayConditions[] = "(repetition.frequency = ".Repetition::FREQUENCY_YEARLY." AND repetition.day = $day AND repetition.month = $month AND ($year - repetition.year) % repetition.interval = 0)";

            $ocurrsOnThisDay = "(".implode(" OR ", $dayConditions).")";

            $conditions[] = "( (repetition.until IS NULL OR repetition.until >= $date) AND $ocurrsOnThisDay )";
        }

        $events->where("startDate <= :endDate", ["endDate" => $endDate]);
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

                $eventDay = mktime(0, 0, 0, date("m", $event->startDate), date("d", $event->startDate), date("Y", $event->startDate));
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
                    $seqDay        = self::daysSinceEpoch($date);
                    $seqMonth      = $month + ($year - 1970)*12;

                    if (
                        ($repetition->until == null || $repetition->until >= $date)
                        && (
                            $repetition->frequency == Repetition::FREQUENCY_DAILY && ($seqDay - $repetition->seqDay) % $repetition->interval == 0
                            || $repetition->frequency == Repetition::FREQUENCY_WEEKLY && ($repetition->weekDay == $weekDay) && floor(($seqDay-$repetition->seqDay)/7) % $repetition->interval == 0
                            || $repetition->frequency == Repetition::FREQUENCY_MONTHLY_DAY && ($repetition->day == $day) && ($seqMonth - $repetition->seqMonth) % $repetition->interval == 0
                            || $repetition->frequency == Repetition::FREQUENCY_MONTHLY_WEEKDAY && ($repetition->weekDay == $weekDay) && ($repetition->weekDayN == 5 ? $repetition->weekDayIsLast : $repetition->weekDayN == $weekDayN) && ($seqMonth - $repetition->seqMonth) % $repetition->interval == 0
                            || $repetition->frequency == Repetition::FREQUENCY_YEARLY && ($repetition->day == $day) && ($repetition->month == $month) && ($year - $repetition->year) % $repetition->interval == 0
                        )
                    ) {

                        $childEvent            = clone($event);
                        $childEvent->startDate = mktime(date('H', $event->startDate), date('i', $event->startDate), date('s', $event->startDate), date('m', $date), date('d', $date), date('y', $date));
                        $childEvent->endDate   = $childEvent->startDate + ($event->endDate - $event->startDate);
                        unset($childEvent->occurrences);
                        unset($childEvent->repetition);

                        $event->occurrences[] = $childEvent;
                    }
                }

            }

        });

    }


}