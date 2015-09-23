<?php
namespace Phidias\Calendar\Event\Repetition;

class Entity extends \Phidias\Db\Orm\Entity
{
    //Types of repetition
    const FREQUENCY_DAILY              = 1;    //Every day
    const FREQUENCY_WEEKLY             = 2;    //Every monday
    const FREQUENCY_MONTHLY_DAY        = 3;    //Every 15th
    const FREQUENCY_MONTHLY_WEEKDAY    = 4;    //Every 3rd monday
    const FREQUENCY_YEARLY             = 5;    //Every January 15th

    var $id;
    var $event;

    var $frequency;
    var $interval;
    var $count;
    var $until;

    //Stripped down information about the repetition start date
    var $day;       //Month day.  [1,31]
    var $month;     //Month, [1,12]
    var $year;      //number of years since year 0.  So, the current year. It's sequential as it is.

    var $weekDay;       //Weekday [1,7] 1 being monday.
    var $weekDayN;      //Nth weekday of the month.  (i.e. 3, means this is the 3rd wday* of the month]
    var $weekDayIsLast; //Boolean. Indicates if this is the last wday* of the month
    var $seqDay;        //number of days since epoch
    var $seqMonth;      //number of months since epoch

    protected static $schema = [

        "table" => "event_repetitions",
        "keys"  => ["id"],

        "attributes" => [

            "id" => [
                "type" => "uuid"
            ],

            "event" => [
                "entity"    => "Phidias\Calendar\Event\Entity",
                "attribute" => "id",
                "onDelete"  => "cascade",
                "onUpdate"  => "cascade"
            ],

            "frequency" => [
                "type"     => "integer",
                "length"   => 1,
                "unsigned" => true
            ],

            "interval" => [
                "type"     => "integer",
                "length"   => 1,
                "unsigned" => true,
                "default"  => "1"
            ],

            "count" => [
                "type"       => "integer",
                "length"     => 4,
                "unsigned"   => true,
                "acceptNull" => true,
                "default"    => null
            ],

            "until" => [
                "type"       => "integer",
                "acceptNull" => true,
                "default"    => null
            ],


            "day" => [
                "type"     => "integer",
                "length"   => 2,
                "unsigned" => true
            ],

            "month" => [
                "type"     => "integer",
                "length"   => 2,
                "unsigned" => true
            ],

            "year" => [
                "type"     => "integer",
                "length"   => 4,
                "unsigned" => true
            ],

            "weekDay" => [
                "type"     => "integer",
                "length"   => 1,
                "unsigned" => true
            ],

            "weekDayN" => [
                "type"     => "integer",
                "length"   => 1,
                "unsigned" => true
            ],

            "weekDayIsLast" => [
                "type"     => "integer",
                "length"   => 1,
                "unsigned" => true
            ],

            "seqDay" => [
                "type"   => "integer",
                "length" => 6
            ],

            "seqMonth" => [
                "type"   => "integer",
                "length" => 6
            ]

        ]

    ];
}