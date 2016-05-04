<?php return [

    "/calendar/events/{eventId}" => [

        "get"    => "Phidias\Calendar\Event\Controller->details({eventId})",
        "put"    => "Phidias\Calendar\Event\Controller->save({input}, {eventId})",
        "delete" => "Phidias\Calendar\Event\Controller->delete({eventId})"

    ]

];