<?php

namespace Hackspace\Bundle\CalciferBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Hackspace\Bundle\CalciferBundle\Entity\Location;
use Hackspace\Bundle\CalciferBundle\Entity\Tag;
use Jsvrcek\ICS\Model\Description\Geo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Hackspace\Bundle\CalciferBundle\Form\EventType;
use Symfony\Component\HttpFoundation\Response;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Relationship\Attendee;
use Jsvrcek\ICS\Model\Relationship\Organizer;

use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Tag controller.
 *
 * @Route("/tags")
 */
class TagController extends Controller
{
    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{slug}.{format}", defaults={"format" = "html"}, name="tag_show")
     * @Method("GET")
     * @Template("CalciferBundle:Event:index.html.twig")
     */
    public function showAction($slug, $format)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Tag');

        /** @var Tag $location */
        $tag = $repo->findOneBy(['slug' => $slug]);

        if (!$tag) {
            throw $this->createNotFoundException('Unable to find tag entity.');
        }

        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->join('e.tags', 't', 'WITH', $qb->expr()->in('t.id', $tag->id))
            ->where('e.startdate >= :startdate')
            ->orderBy('e.startdate')
            ->setParameter('startdate', $now);
        $entities = $qb->getQuery()->execute();

        if ($format == 'ics') {
            $calendar = new Calendar();
            $calendar->setProdId('-//My Company//Cool Calendar App//EN');
	    $calendar->setTimeZone(new \DateTimeZone('Europe/Berlin'));

            foreach ($entities as $entity) {
                /** @var Event $entity */
                $event = new CalendarEvent();
                $event->setStart($entity->startdate);
                if ($entity->enddate instanceof \DateTime)
                    $event->setEnd($entity->enddate);
                $event->setSummary($entity->summary);
                $event->setDescription($entity->description);
                $event->setUrl($entity->url);
                if ($entity->location instanceof Location) {
                    $location = new \Jsvrcek\ICS\Model\Description\Location();
                    $location->setName($entity->location->name);
                    $event->setLocations([$location]);
                    if (\is_float($entity->location->lon) && \is_float($entity->location->lat)) {
                        $geo = new Geo();
                        $geo->setLatitude($entity->location->lat);
                        $geo->setLongitude($entity->location->lon);
                        $event->setGeo($geo);
                    }
                }
                $calendar->addEvent($event);
            }

            $calendarExport = new CalendarExport(new CalendarStream, new Formatter());
            $calendarExport->addCalendar($calendar);

            //output .ics formatted text
            $result = $calendarExport->getStream();

            $response = new Response($result);
            $response->headers->set('Content-Type', 'text/calendar');

            return $response;
        } else {
            return array(
                'entities' => $entities,
                'tag' => $tag,
            );
        }
    }
}
