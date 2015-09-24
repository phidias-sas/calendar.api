<?php return [

    "/calendars" => [

        "get" => [

            "controller" => "Phidias\Calendar\Controller->getCollection()",

            "filter" => function($request, $response, &$collection) {

                $params = $request->getQueryParams();

                $limit  = isset($params["limit"]) ? $params["limit"] : 5;
                $page   = isset($params["page"])  ? $params["page"]  : 1;
                $search = isset($params["q"])     ? $params["q"]     : null;
                $order  = isset($params["order"]) ? $params["order"] : null;

                $collection->limit($limit);
                $collection->page($page);

                if ($search !== null) {
                    $collection->search($search, ["title", "description"]);
                }

                if ($order !== null) {

                    $firstChar = substr($order, 0, 1);
                    if ($firstChar == "+" || $firstChar == "-") {
                        $orderAttribute = substr($order, 1);
                        $isDescending   = $firstChar == "-";
                    } else {
                        $orderAttribute = $order;
                        $isDescending   = false;
                    }

                    $collection->orderBy($orderAttribute, $isDescending);
                }

                $collection = $collection->find()->fetchAll();
            }
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
