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

use Sabre\VObject;
/**
 * Event controller.
 *
 * @Route("/")
 */
class EventController extends Controller
{
    /**
     * Lists all Event entities as ICS.
     *
     * @Route("/all.ics", name="events_ics")
     * @Method("GET")
     * @Template()
     */
    public function allEventsAsICSAction()
    {
        $em = $this->getDoctrine()->getManager();

        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->orderBy('e.startdate')
            ->setParameter('startdate', $now);
        $entities = $qb->getQuery()->execute();

        $vcalendar = new VObject\Component\VCalendar();

        foreach ($entities as $entity) {
            /** @var Event $entity */
            $vcalendar->add('VEVENT',$entity->ConvertToCalendarEvent());
        }

        $response = new Response($vcalendar->serialize());
        $response->headers->set('Content-Type', 'text/calendar');

        return $response;
    }


    /**
     * Lists all Event entities.
     *
     * @Route("/", name="")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->orderBy('e.startdate')
            ->setParameter('startdate', $now);
        $entities = $qb->getQuery()->execute();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new Event entity.
     *
     * @Route("/termine/", name="_create")
     * @Method("POST")
     * @Template("CalciferBundle:Event:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Event();

        if (! $request->get('origin')) {
            $em = $this->saveEvent($request, $entity);
            $errors = $entity->isValid();
            if ( $errors === true ) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                $em->flush();
                return $this->redirect($this->generateUrl('_show', array('slug' => $entity->slug)));
            }
        } else {
            return $this->redirect($this->generateUrl(''));
        }

        return array(
            'entity' => $entity,
            'errors' => $errors,
        );
    }

    /**
     * Displays a form to create a new Event entity.
     *
     * @Route("/termine/neu", name="_new")
     * @Method("GET")
     * @Template("CalciferBundle:Event:edit.html.twig")
     */
    public function newAction(Request $request)
    {
        $entity = new Event();

        $entity->description = $request->get('description');
        $entity->summary = $request->get('summary');
        $entity->url = $request->get('url');
        if (strlen($request->get('tags')) > 0) {
            $tags = explode(",",$request->get('tags'));
            foreach($tags as $tag) {
                $_tag = new Tag();
                $_tag->name = $tag;
                $entity->tags[] = $_tag;
            }
        }

        if (strlen($request->get('location')) > 0) {
            $location = new Location();
            $location->name = $request->get('location');
            $entity->location = $location;
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/termine/{slug}", name="_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }

        return array(
            'entity' => $entity
        );
    }

    /**
     * Displays a form to edit an existing Event entity.
     *
     * @Route("/termine/{slug}/edit", name="_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Edits an existing Event entity.
     *
     * @Route("/termine/{slug}", name="_update")
     * @Method("POST")
     * @Template("CalciferBundle:Event:edit.html.twig")
     */
    public function updateAction(Request $request, $slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }



        $errors = $entity->isValid();
        if ($errors === true && (! $request->get('origin'))) {
            $em = $this->saveEvent($request, $entity);
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('_show', array('slug' => $entity->slug)));
        } else {
            return $this->redirect($this->generateUrl(''));
        }

        return array(
            'entity' => $entity,
            'errors' => $errors,

        );
    }

    /**
     * @param Request $request
     * @param $entity
     * @return EntityManager
     */
    public function saveEvent(Request $request, Event $entity)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $entity->description = $request->get('description');
        $entity->summary = $request->get('summary');
        $entity->url = $request->get('url');
        $startdate = $request->get('startdate');
        if (strlen($startdate) > 0) {
            $startdate = new \DateTime($startdate);
            $entity->startdate = $startdate;
        }
        $entity->slug = $entity->generateSlug($entity->summary, $em);

        $enddate = $request->get('enddate');
        if (strlen($enddate) > 0) {
            $enddate = new \DateTime($enddate);
            $entity->enddate = $enddate;
        } else {
            $entity->enddate = null;
        }

        $location = $request->get('location');
        $location_lat = $request->get('location_lat');
        $location_lon = $request->get('location_lon');
        if (strlen($location) > 0) {
            // check if the location already exists
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('CalciferBundle:Location');
            $results = $repo->findBy(['name' => $location]);
            if (count($results) > 0) {
                $location_obj = $results[0];
                if (strlen($location_lat) > 0) {
                    $location_obj->lat = $location_lat;
                }
                if (strlen($location_lon) > 0) {
                    $location_obj->lon = $location_lon;
                }
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($results[0]);
            } else {
                $location_obj = new Location();
                $location_obj->name = $location;
                if (strlen($location_lat) > 0) {
                    $location_obj->lat = $location_lat;
                }
                if (strlen($location_lon) > 0) {
                    $location_obj->lon = $location_lon;
                }
                $location_obj->slug = $location_obj->generateSlug($location_obj->name, $em);
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($location_obj);
            }
        }

        $tags = $request->get('tags');
        if (strlen($tags) > 0) {
            $tags = explode(',', strtolower($tags));
            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('CalciferBundle:Tag');
            $entity->clearTags();
            foreach ($tags as $tag) {
                $tag = trim($tag);
                $results = $repo->findBy(['name' => $tag]);
                if (count($results) > 0) {
                    $entity->addTag($results[0]);
                } else {
                    $tag_obj = new Tag();
                    $tag_obj->name = $tag;
                    $tag_obj->slug = $tag_obj->generateSlug($tag_obj->name, $em);
                    $em->persist($tag_obj);
                    $em->flush();
                    $entity->addTag($tag_obj);
                }
            }
        }
        return $em;
    }

    /**
     * Deletes a Event entity.
     *
     * @Route("/termine/{slug}/löschen", name="_delete")
     * @Method({"GET", "POST"})
     * @Template("CalciferBundle:Event:delete.html.twig")
     */
    public function deleteAction(Request $request, $slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }


        $confirmation = $request->get('confirmation', false);

        if (($request->getMethod() == 'POST') && ($confirmation)) {
            $em->remove($entity);
            $em->flush();

            return $this->redirect('/');
        }

        return array(
            'entity' => $entity,

        );
    }

    /**
     * Copies a Event entity.
     *
     * @Route("/termine/{slug}/kopieren", name="_copy")
     * @Method("GET")
     * @Template("CalciferBundle:Event:edit.html.twig")
     */
    public function copyAction(Request $request, $slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }

        $entity->id = null;

        return array(
            'entity' => $entity,

        );
    }
}
