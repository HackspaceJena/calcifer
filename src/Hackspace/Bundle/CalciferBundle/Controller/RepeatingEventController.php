<?php

namespace Hackspace\Bundle\CalciferBundle\Controller;

use Hackspace\Bundle\CalciferBundle\Entity\RepeatingEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Hackspace\Bundle\CalciferBundle\Entity\Location;
use Hackspace\Bundle\CalciferBundle\Entity\Tag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Hackspace\Bundle\CalciferBundle\Form\EventType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Location controller.
 *
 * @Route("/termine/wiederholend")
 */
class RepeatingEventController extends Controller
{
    /**
     * Displays all repeating events
     *
     * @Route("/", name="repeating_event_show")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:RepeatingEvent');

        $entities = $repo->findAll();

        return [
            'entities' => $entities,
        ];
    }

    /**
     * Displays all repeating events
     *
     * @Route("/logs", name="repeating_event_logs")
     * @Method("GET")
     * @Template()
     */
    public function logIndexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:RepeatingEventLogEntry');

        $entities = $repo->findBy([], ['event_startdate' => 'DESC']);

        return [
            'entities' => $entities,
        ];
    }

    /**
     *  Displays a form to create a repeating event
     *
     * @Route("/neu", name="repeating_event_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new RepeatingEvent();

        return [
            'entity' => $entity,
        ];
    }

    /**
     * Creates a repeating event
     *
     * @Route("/neu", name="repeating_event_create")
     * @Method("POST")
     * @Template("CalciferBundle:RepeatingEvent:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new RepeatingEvent();
        $this->fillEntity($request, $entity);
        $errors = $entity->isValid();
        if (count($errors) == 0) {
            $ret = $this->saveRepeatingEvent($request, $entity);
            if ($entity->id > 0) {
                return $this->redirect($this->generateUrl('repeating_event_show'));
            } else {
                throw new \Exception('Could not save repeating event?!?');
            }
        }
        return [
            'entity' => $entity,
            'errors' => $errors,
        ];

    }

    /**
     * Displays a form to edit a repeating event
     *
     * @Route("/{slug}/bearbeiten",name="repeating_event_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:RepeatingEvent');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find RepeatingEvent entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }

    /**
     * Updates a repeating event
     *
     * @Route("/{slug}/bearbeiten",name="repeating_event_update")
     * @Method("POST")
     * @Template("CalciferBundle:RepeatingEvent:edit.html.twig")
     */
    public function updateAction(Request $request, $slug)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:RepeatingEvent');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find RepeatingEvent entity.');
        }

        $this->fillEntity($request, $entity);
        $errors = $entity->isValid();
        if (count($errors) == 0) {
            $ret = $this->saveRepeatingEvent($request, $entity);
            if ($entity->id > 0) {
                return $this->redirect($this->generateUrl('repeating_event_show'));
            } else {
                throw new \Exception('Could not save repeating event?!?');
            }
        }
        return [
            'entity' => $entity,
            'errors' => $errors,
        ];
    }

    private function fillEntity(Request $request, RepeatingEvent $entity)
    {
        $fields = [
            'duration',
            'repeating_pattern',
            'summary',
            'description',
            'url',
        ];
        foreach ($fields as $field) {
            $entity->$field = $request->get($field);
        }
        if (strlen($entity->duration) == 0)
            $entity->duration = null;
        $nextdate = $request->get('nextdate');
        $nextdate = new \DateTime($nextdate);
        $entity->nextdate = $nextdate;

    }

    private function saveRepeatingEvent(Request $request, RepeatingEvent $entity)
    {
        $location = $request->get('location');
        $location_lat = $request->get('location_lat');
        $location_lon = $request->get('location_lon');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        if (strlen($location) > 0) {
            // check if the location already exists
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
                $entity->location = $results[0];
            } else {
                $location_obj = new Location();
                $location_obj->name = $location;
                if (strlen($location_lat) > 0) {
                    $location_obj->lat = $location_lat;
                }
                if (strlen($location_lon) > 0) {
                    $location_obj->lon = $location_lon;
                }
                $location_obj->slug = $location_obj->generateSlug($location_obj->name,$em);
                $em->persist($location_obj);
                $em->flush();
                $entity->location = $location_obj;
            }
        } else {
            $entity->location = null;
        }

        $tags = $request->get('tags');
        if (strlen($tags) > 0) {
            $tags = explode(',', $tags);
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
                    $tag_obj->slug = $tag_obj->generateSlug($tag_obj->name,$em);
                    $em->persist($tag_obj);
                    $em->flush();
                    $entity->addTag($tag_obj);
                }
            }
        } else {
            $entity->clearTags();
        }

        $entity->slug = $entity->generateSlug($entity->summary,$em);

        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * Deletes a Event entity.
     *
     * @Route("/{slug}/löschen", name="repeating_event_delete")
     * @Method({"GET", "POST"})
     * @Template("CalciferBundle:RepeatingEvent:delete.html.twig")
     */
    public function deleteAction(Request $request, $slug) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:RepeatingEvent');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }


        $confirmation = $request->get('confirmation',false);

        if (($request->getMethod() == 'POST') && ($confirmation)) {
            $em->remove($entity);
            $em->flush();

            return $this->redirect('/');
        }

        return array(
            'entity'      => $entity,

        );
    }

    /**
     * Deletes a Event entity.
     *
     * @Route("/wiederholungsmuster", name="repeating_patterns")
     * @Method({"GET", "POST"})
     * @Template("CalciferBundle:RepeatingEvent:repeating_patterns.html.twig")
     */
    public function repeatingPatternsHelpAction(Request $request) {
        return null;
    }
}
