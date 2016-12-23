<?php 
use Phidias\Api\Http\Stream;
return [
    "/calendar/events/{eventId}/ics" => [
        "get" => [
            "controller" => "Phidias\Calendar\Event\Ics\Controller->main({eventId})",

            "filter" => function($response, $output, $eventId) {
                $body = new Stream("php://temp", "w");
                $body->write($output);

                return $response
                    ->header("Content-type",        "text/calendar; charset=utf-8")
                    ->header("Content-Disposition", "inline; filename=event_{$eventId}.ics")
                    ->header("Cache-Control",       "must-revalidate, post-check=0, pre-check=0")
                    ->body($body);
            }            
        ]
    ]
];