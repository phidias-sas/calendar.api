<?php
namespace Phidias\Calendar;

class Entity extends \Phidias\Db\Orm\Entity
{
    var $id;
    var $title;
    var $description;
    var $creationDate;
    var $color;

    protected static $schema = [

        "table" => "calendars",
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

            "color" => [
                "type"       => "varchar",
                "length"     => 6,
                "acceptNull" => true,
                "default"    => null
            ],

            "creationDate" => [
                "type"     => "integer",
                "unsigned" => true
            ]

        ]

    ];
}