<?php return [

    "/calendars/{calendarId}" => [

        "any" => [
            "catch" => [
                "Phidias\Db\Orm\Exception\EntityNotFound" => function($request, $response) {
                    $response->status(404, "calendario no encontrado");
                }
            ]
        ],

        "get" => [
            "controller" => "Phidias\Calendar\Controller->getCalendar({calendarId})"
        ],

        "put" => [
            "controller" => "Phidias\Calendar\Controller->updateCalendar({calendarId}, {request.data})"
        ],

        "delete" => [
            "controller" => "Phidias\Calendar\Controller->deleteCalendar({calendarId})"
        ]

    ]

];
