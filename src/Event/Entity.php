<?php
namespace Phidias\Calendar\Event;

class Entity extends \Phidias\Db\Orm\Entity
{
    var $id;
    var $title;
    var $location;
    var $startDate;
    var $endDate;
    var $creationDate;
    var $modificationDate;

    protected static $schema = [

        "table" => "calendar_events",
        "keys"  => ["id"],

        "attributes" => [

            "id" => [
                "type" => "uuid"
            ],

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
                "unsigned"   => true,
                "acceptNull" => true,
                "default"    => null
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