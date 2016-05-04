<?php return [

    "/calendar/events" => [

        "get" => "Phidias\Calendar\Event\Controller->collection()",

        "post" => [
            "controller" => "Phidias\Calendar\Event\Controller->save({input})",
            "filter" => function($response, $output) {
                return $response
                    ->status(201)
                    ->header("Location", "calendar/events/$output->id");
            }
        ]

    ]

];