<?php
namespace Phidias\Calendar\Event;

use Phidias\Calendar\Event\Entity as Event;
use Phidias\Calendar\Event\Repetition\Entity as Repetition;
use Phidias\Calendar\Event\Repetition\Controller as RepetitionController;
use Phidias\Calendar\Post\Event\Entity as PostEvent;
use Phidias\Post\Entity as Post;
use Phidias\Post\Stub\Entity as Stub;

class Controller
{   
    public function collection()
    {
     //something   
        return Event::collection()
            ->allAttributes()
            ->attribute("repetition", Repetition::single()->allAttributes());
    }

    public function getEvents($personId,$query)
    {
        $selectedFeeds = explode(",",$query->feeds);
        $startDate = '1000-01-01'; // 01-04-2021 00:00:00
        $endDate = '3000-12-31'; // 31-04-2021 00:00:00

        $feeds = [];

        if ( gettype( array_search("feed",$selectedFeeds) ) <> "boolean" ){
            $output = $this->collection();
            $output->attribute("postEvent", PostEvent::single()
                    ->notEmpty()
                    ->attribute("post", Post::single()
                        ->notEmpty()
                        ->attributes("id", "type", "thread", "author")
                        ->join("stub", Stub::single()->notEmpty(), "postEvent.post.stub.post = postEvent.post.id AND postEvent.post.stub.person = '$personId'")
                    )
            );
            $output->where("postEvent.post.author = :personId OR postEvent.post.stub.person = :personId", ["personId" => $personId]);
            $output->where("postEvent.post.deleteDate IS NULL");
            $output->where("postEvent.post.publishDate IS NOT NULL");

            if (isset($query->type) && !empty($query->type)) {
                $types = explode(",", trim($query->type));
                $output->match("postEvent.post.type", $types);
            }

            if (isset($query->node)) {
                $output->match("postEvent.post.node", $query->node);
            }

            $allEvents = $output->find()->fetchAll();
            array_push($feeds,$allEvents);
            // array_push($feeds,[['nume' => 'Nitu', 'prenume' => 'Andrei'],['nume' => 'Nitu', 'prenume' => 'Andrei']]);
        }

        
        $query->start=$startDate;
        $query->end  =$endDate;
        array_push($feeds, \Phidias\Core\Communication\Node\Post\Controller::getEvents($personId, $query) );

        array_push($feeds, \Phidias\V3\Calendar\Google\Controller::googleEvents($personId,$startDate, $endDate));

        array_push($feeds, \Phidias\V3\Academic\Exam\Controller::feed($personId, $startDate, $endDate) );

        array_push($feeds, \Phidias\V3\Academic\Assignment\Controller::feed($personId, $startDate, $endDate) );
        
        array_push($feeds, \Phidias\V3\Academic\Evaluation\Controller::feed($personId, $startDate, $endDate) );

        // return $feeds;

        $events = [];
        foreach($feeds as $value){
            for($i=0;$i<count($value);$i++){
                if(gettype($value[$i])=="array"){
                    $value[$i] = (object)$value[$i];
                }   
                $events[] =  $value[$i] ;
            }
        }
        return $events;
    }

    public function feed($startDate, $endDate)
    {
        $startDate = is_numeric($startDate) ? $startDate : strtotime($startDate);
        $endDate   = is_numeric($endDate)   ? $endDate   : strtotime($endDate);

        $events = Event::collection()->allAttributes();
        RepetitionController::filterEventsInDateRange($events, $startDate, $endDate);

        return $events;
    }

    public function details($eventId)
    {
        return new Event($eventId);
    }

    public function save($eventData, $eventId = null)
    {
        $event         = new Event($eventId);
        $event->allDay = 0;
        $event->setValues($eventData);

        $event->startDate        = !is_numeric($event->startDate) ? strtotime($event->startDate) : $event->startDate;
        $event->endDate          = !is_numeric($event->endDate)   ? strtotime($event->endDate)   : $event->endDate;
        $event->creationDate     = $event->creationDate ? $event->creationDate : time();
        $event->modificationDate = time();
        $event->save();

        RepetitionController::repeat($event, isset($eventData->repeat) ? $eventData->repeat : null);

        return $event;
    }

    public function delete($eventId)
    {
        $event = new Event($eventId);
        $event->delete();

        return $event;
    }
}