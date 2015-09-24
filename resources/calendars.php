<?php return [

    "/calendars" => [

        "get" => [
            "controller" => "Phidias\Calendar\Controller->getCollection()"
        ],

        "post" => [
            "controller" => "Phidias\Calendar\Controller->createCalendar({request.data})",

            "filter" => function($request, $response, $calendar) {
                $response->status(201, "calendario creado");
                $response->header("Location", "/calendars/{$calendar->id}");
            }
        ]

    ]

];
