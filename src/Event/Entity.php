<?php
namespace Phidias\Calendar\Event;

class Entity extends \Phidias\Db\Orm\Entity
{
    var $id;
    // var $uid;
    var $title;
    var $location;
    var $startDate;
    var $endDate;
    var $allDay;
    var $creationDate;
    var $modificationDate;

    protected static $schema = [

        "table" => "calendar_events",
        "keys"  => ["id"],

        "attributes" => [

            "id" => [
                "type" => "uuid"
            ],

            // "uid" => [
            //     "type"       => "varchar",
            //     "length"     => 255,
            //     "acceptNull" => true,
            //     "default"    => null
            // ],

            "title" => [
                "type"   => "varchar",
                "length" => 255
            ],

            "location" => [
                "type"       => "varchar",
                "length"     => 255,
                "acceptNull" => true,
                "default"    => null
            ],

            "startDate" => [
                "type"     => "integer",
                "unsigned" => true
            ],

            "endDate" => [
                "type"       => "integer",
                "unsigned"   => true
            ],

            "allDay" => [
                "type"       => "integer",
                "length"     => 1,
                "unsigned"   => true,
                "default"    => 0
            ],

            "creationDate" => [
                "type"     => "integer",
                "unsigned" => true
            ],

            "modificationDate" => [
                "type"       => "integer",
                "unsigned"   => true,
                "acceptNull" => true,
                "default"    => null
            ]

        ]

    ];
}