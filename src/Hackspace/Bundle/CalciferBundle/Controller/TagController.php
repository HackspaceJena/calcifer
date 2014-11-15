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
use Doctrine\ORM\Query\ResultSetMappingBuilder;

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
        $tags = [];
        $operator = 'or';
        if (strpos($slug, '|') !== false) {
            $slugs = explode('|', $slug);
            foreach ($slugs as $item) {
                /** @var Tag $tag */
                $tag = $repo->findOneBy(['slug' => $item]);

                if ($tag instanceof Tag) {
                    $tags[] = $tag;
                }
            }
        } else if (strpos($slug, '&') !== false) {
            $slugs = explode('&', $slug);
            $operator = 'and';
            foreach ($slugs as $item) {
                /** @var Tag $tag */
                $tag = $repo->findOneBy(['slug' => $item]);

                if ($tag instanceof Tag) {
                    $tags[] = $tag;
                }
            }
        } else {
            /** @var Tag $tag */
            $tag = $repo->findOneBy(['slug' => $slug]);

            if ($tag instanceof Tag) {
                $tags[] = $tag;
            }
        }

        if (count($tags) == 0) {
            throw $this->createNotFoundException('Unable to find tag entity.');
        }

        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        $entities = null;
        if ($operator == 'and') {
            $sql = <<<EOF
SELECT * FROM events AS e
WHERE id IN (
WITH events_on_tags AS (
  SELECT events_id, array_agg(tags_id) as tags
  FROM events2tags
  GROUP BY events_id
)
SELECT events_id FROM events_on_tags
WHERE tags @> array[@tags@]
)
AND e.startdate >= :startdate
ORDER BY e.startdate
EOF;
            $tag_ids = array_reduce($tags, function ($carry, $item) {
                if (strlen($carry) == 0) {
                    return $item->id;
                } else {
                    return $carry . ',' . $item->id;
                }
            });

            $sql = str_replace('@tags@', $tag_ids, $sql);

            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata('CalciferBundle:Event', 'e');

            $query = $em->createNativeQuery($sql, $rsm);

            $query->setParameter('startdate', $now);

            $entities = $query->getResult();

        } else {
            /** @var QueryBuilder $qb */
            $qb = $em->createQueryBuilder();
            $qb->select(array('e'))
                ->from('CalciferBundle:Event', 'e')
                ->where('e.startdate >= :startdate')
                ->orderBy('e.startdate')
                ->setParameter('startdate', $now);

            $qb->join('e.tags', 't', 'WITH', $qb->expr()->in('t.id', array_reduce($tags, function ($carry, $item) {
                if (strlen($carry) == 0) {
                    return $item->id;
                } else {
                    return $carry . ',' . $item->id;
                }
            })));
            $entities = $qb->getQuery()->execute();
        }

        if ($format == 'ics') {
            $calendar = new Calendar();
            $calendar->setProdId('-//My Company//Cool Calendar App//EN');
            $calendar->setTimeZone(new \DateTimeZone('Europe/Berlin'));

            foreach ($entities as $entity) {
                /** @var Event $entity */
                $event = $entity->ConvertToCalendarEvent();
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
                'tags' => $tags,
                'operator' => $operator,
            );
        }
    }
}
