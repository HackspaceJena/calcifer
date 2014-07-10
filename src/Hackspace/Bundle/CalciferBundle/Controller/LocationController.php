<?php

namespace Hackspace\Bundle\CalciferBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Hackspace\Bundle\CalciferBundle\Entity\Location;
use Hackspace\Bundle\CalciferBundle\Entity\Tag;
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
 * Location controller.
 *
 * @Route("/orte")
 */
class LocationController extends Controller
{
    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{slug}", name="location_show")
     * @Method("GET")
     * @Template("CalciferBundle:Event:index.html.twig")
     */
    public function showAction($slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Location');

        /** @var Location $location */
        $location = $repo->findOneBy(['slug' => $slug]);

        if (!$location) {
            throw $this->createNotFoundException('Unable to find Location entity.');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb ->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->andWhere('e.locations_id = :location')
            ->orderBy('e.startdate')
            ->setParameter('startdate',new \DateTime())
            ->setParameter('location',$location->getId());
        $entities = $qb->getQuery()->execute();

        return array(
            'entities' => $entities,
            'location' => $location,
        );
    }

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{id}.ics", requirements={"id" = "\d+"}, name="location_show_ics")
     * @Method("GET")
     */
    public function showActionICS($id)
    {
        $results = $this->showAction(str_replace('.ics','',$id));
        $entities = $results['entities'];

        $calendar = new Calendar();
        $calendar->setProdId('-//My Company//Cool Calendar App//EN');

        foreach($entities as $entity) {
            /** @var Event $entity */
            $event = new CalendarEvent();
            $event->setStart($entity->getStartdate());
            if ($entity->getEnddate() instanceof DateTime)
                $event->setEnd($entity->getEnddate());
            $event->setSummary($entity->getSummary());
            $event->setDescription($entity->getDescription());
            $location = new \Jsvrcek\ICS\Model\Description\Location();
            $location->setName($entity->getLocation()->getName());
            $event->setLocations([$location]);
            $calendar->addEvent($event);
        }

        $calendarExport = new CalendarExport(new CalendarStream, new Formatter());
        $calendarExport->addCalendar($calendar);

        //output .ics formatted text
        $result =  $calendarExport->getStream();

        $response = new Response($result);
        $response->headers->set('Content-Type', 'text/calendar');

        return $response;
    }
}
