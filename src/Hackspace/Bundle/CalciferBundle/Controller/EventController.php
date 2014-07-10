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

/**
 * Event controller.
 *
 * @Route("/")
 */
class EventController extends Controller
{

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

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb ->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->orderBy('e.startdate')
            ->setParameter('startdate',new \DateTime());
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
     * @Template("CalciferBundle:Event:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Event();
        $entity->setDescription($request->get('description'));
        $entity->setSummary($request->get('summary'));
        $entity->setUrl($request->get('url'));
        $startdate = $request->get('startdate');
        $startdate = new \DateTime($startdate);
        $entity->setStartdate($startdate);
        $entity->setSlug(\URLify::filter($entity->getSummary(),255,'de'));

        $enddate = $request->get('enddate');
        if (strlen($enddate) > 0) {
            $enddate = new \DateTime($enddate);
            $entity->setenddate($enddate);
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
                $location_obj->setLat($location_lat);
                $location_obj->setLon($location_lon);
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($results[0]);
            } else {
                $location_obj = new Location();
                $location_obj->setName($location);
                $location_obj->setLat($location_lat);
                $location_obj->setLon($location_lon);
                $location_obj->setSlug(\URLify::filter($location_obj->getName(),255,'de'));
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($location_obj);
            }
        }

        $tags = $request->get('tags');
        if (strlen($tags) > 0) {
            $tags = explode(',',$tags);
            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('CalciferBundle:Tag');
            foreach ($tags as $tag) {
                $tag = trim($tag);
                $results = $repo->findBy(['name' => $tag]);
                if (count($results) > 0) {
                    $entity->addTag($results[0]);
                } else {
                    $tag_obj = new Tag();
                    $tag_obj->setName($tag);
                    $tag_obj->setSlug(\URLify::filter($tag_obj->getName(),255,'de'));
                    $em->persist($tag_obj);
                    $em->flush();
                    $entity->addTag($tag_obj);
                }
            }
        }


        if ($entity->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('_show', array('slug' => $entity->getSlug())));
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Displays a form to create a new Event entity.
     *
     * @Route("/termine/neu", name="_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Event();

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
            'entity'      => $entity
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
            'entity'      => $entity,
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

        $entity->setDescription($request->get('description'));
        $entity->setSummary($request->get('summary'));
        $entity->setUrl($request->get('url'));
        $startdate = $request->get('startdate');
        $startdate = new \DateTime($startdate);
        $entity->setStartdate($startdate);
        $entity->setSlug(\URLify::filter($entity->getSummary(),255,'de'));

        $enddate = $request->get('enddate');
        if (strlen($enddate) > 0) {
            $enddate = new \DateTime($enddate);
            $entity->setenddate($enddate);
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
                $location_obj->setLat($location_lat);
                $location_obj->setLon($location_lon);
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($results[0]);
            } else {
                $location_obj = new Location();
                $location_obj->setName($location);
                $location_obj->setLat($location_lat);
                $location_obj->setLon($location_lon);
                $location_obj->setSlug(\URLify::filter($location_obj->getName(),255,'de'));
                $em->persist($location_obj);
                $em->flush();
                $entity->setLocation($location_obj);
            }
        }

        $tags = $request->get('tags');
        if (strlen($tags) > 0) {
            $tags = explode(',',$tags);
            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('CalciferBundle:Tag');
            foreach ($tags as $tag) {
                $tag = trim($tag);
                $results = $repo->findBy(['name' => $tag]);
                if (count($results) > 0) {
                    $entity->addTag($results[0]);
                } else {
                    $tag_obj = new Tag();
                    $tag_obj->setName($tag);
                    $tag_obj->setSlug(\URLify::filter($tag_obj->getName(),255,'de'));
                    $em->persist($tag_obj);
                    $em->flush();
                    $entity->addTag($tag_obj);
                }
            }
        }


        if ($entity->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('_show', array('slug' => $entity->getSlug())));
        }

        return array(
            'entity'      => $entity,

        );
    }
}
