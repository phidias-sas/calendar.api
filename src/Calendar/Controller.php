<?php
namespace Phidias\Calendar;

use Phidias\Calendar\Entity as Calendar;

class InvalidCalendarException extends \Exception
{
}

class Controller
{
    function getCollection()
    {
        return Calendar::collection()->allAttributes();
    }

    function createCalendar($incomingCalendar)
    {
        // Validar el objeto
        if ( !is_object($incomingCalendar) || !isset($incomingCalendar->title) ) {
            throw InvalidCalendarException;
        }

        $calendar = new Calendar;
        $calendar->setValues($incomingCalendar, ["title", "description", "color"]);
        $calendar->creationDate = time();
        $calendar->save();

        return $calendar;
    }

    function getCalendar($calendarId)
    {
        return new Calendar($calendarId);
    }

    function updateCalendar($calendarId, $incomingData)
    {
        $calendar = new Calendar($calendarId);
        $calendar->setValues($incomingData, ["title", "description", "color"]);
        $calendar->save();

        return $calendar;
    }

    function deleteCalendar($calendarId)
    {
        $calendar = new Calendar($calendarId);
        $calendar->delete();

        return $calendar;
    }
}