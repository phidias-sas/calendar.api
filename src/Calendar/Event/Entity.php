<?php
namespace Phidias\Calendar\Event;

class Entity extends \Phidias\Db\Orm\Entity
{
    var $id;
    var $title;
    var $description;
    var $location;
    var $startDate;
    var $endDate;
    var $creationDate;
    var $modificationDate;

    protected static $schema = [

        "table" => "events",
        "keys"  => ["id"],

        "attributes" => [

            "id" => [
                "type" => "uuid"
            ],

            "title" => [
                "type"   => "varchar",
                "length" => 255
            ],

            "description" => [
                "type"       => "text",
                "acceptNull" => true,
                "default"    => null
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