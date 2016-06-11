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
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\AcceptHeader;

use Sabre\VObject;

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
            $vcalendar = new VObject\Component\VCalendar();

            foreach ($entities as $entity) {
                 /** @var Event $entity */
                $vcalendar->add('VEVENT',$entity->ConvertToCalendarEvent());
            }

            $response = new Response($vcalendar->serialize());
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

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/")
     * @Method("GET")
     */
    public function indexAction() {
        $accepts = AcceptHeader::fromString($this->getRequest()->headers->get('Accept'));
        if ($accepts->has('application/json')) {
            $em = $this->getDoctrine()->getManager();

            /** @var QueryBuilder $qb */
            $qb = $em->createQueryBuilder();
            $qb->select(['t'])
                ->from('CalciferBundle:Tag', 't')
                ->where('t.name LIKE :tag')
                ->orderBy('t.name')
                ->setParameter('tag', sprintf('%%%s%%',strtolower($this->getRequest()->query->get('q'))));

            $entities = $qb->getQuery()->execute();

            $tags = [];
            foreach($entities as $tag) {
                /** @var Tag $tag */
                $tags[] = [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            }

            $retval = [
                'success' => true,
                'results' => $tags,
            ];


            $response = new Response(json_encode($retval));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $this->redirect('/');
        }
    }
}
