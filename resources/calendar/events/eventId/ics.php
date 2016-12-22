<?php return [
    "/calendar/events/{eventId}/ics" => [
        "get" => "Phidias\Calendar\Event\Ics\Controller->main({eventId})",
        "filter" => function($response, $output) {
            $body = new Http\Stream("php://temp", "w");
            $body->write($output);

            return $response
                ->header("Content-type",        "text/calendar; charset=utf-8")
                ->header("Content-Disposition", "attachment; filename=event.ics")
                ->header("Cache-Control",       "must-revalidate, post-check=0, pre-check=0")
                ->body($body);
        }
    ]
];