<?php

namespace Hackspace\Bundle\CalciferBundle\Controller;

use Doctrine\Common\Annotations\Annotation\Required;
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
use Symfony\Component\HttpFoundation\AcceptHeader;

use Sabre\VObject;
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
     * @Route("/{slug}.{format}", name="location_show", defaults={"format" = "html"})
     * @Method("GET")
     * @Template("CalciferBundle:Event:index.html.twig")
     */
    public function showAction($slug, $format)
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

        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->andWhere('e.locations_id = :location')
            ->orderBy('e.startdate')
            ->setParameter('startdate', $now)
            ->setParameter('location', $location->id);
        $entities = $qb->getQuery()->execute();

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
                'location' => $location,
            );
        }
    }

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{slug}/bearbeiten", name="location_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($slug)
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

        return [
            'entity' => $location
        ];
    }

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{slug}/bearbeiten", name="location_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $slug)
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

        if ($location->name != $request->get('name')) {
            // someone changed the name of the location, lets check if the location already exists
            $new_location = $repo->findOneBy(['name' => $request->get('name')]);
            if (is_null($new_location)) {
                $location->name = $request->get('name');
                $location->slug = $location->generateSlug($location->name, $em);
            } else {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    'Ort mit diesem Namen existiert bereits.'
                );
                return $this->redirect($this->generateUrl('location_edit', array('slug' => $location->slug)));
            }
        }
        $location->streetaddress = $request->get('streetaddress');
        $location->streetnumber = $request->get('streetnumber');
        $location->zipcode = $request->get('zipcode');
        $location->city = $request->get('city');
        $location->description = $request->get('description');

        $latlon = $request->get('geocords');
        $latlon = explode(',', $latlon);
        if (count($latlon) == 2) {
            $location->lat = $latlon[0];
            $location->lon = $latlon[1];
        }

        $em->persist($location);
        $em->flush();

        return $this->redirect($this->generateUrl('location_show', array('slug' => $location->slug)));
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
            $qb->select(['l'])
                ->from('CalciferBundle:Location', 'l')
                ->where('lower(l.name) LIKE lower(:location)')
                ->orderBy('l.name')
                ->setParameter('location', sprintf('%%%s%%',$this->getRequest()->query->get('q')));

            $entities = $qb->getQuery()->execute();

            $locations = [];
            foreach($entities as $location) {
                /** @var Location $location */
                $locations[] = array(
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => \Michelf\Markdown::defaultTransform($location->description),
                    'streetaddress' => $location->streetaddress,
                    'streetnumber' => $location->streetnumber,
                    'zipcode' => $location->zipcode,
                    'city' => $location->city,
                    'lon' => $location->lon,
                    'lat' => $location->lat,
                );
            }


            $response = new Response(json_encode($locations));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $this->redirect('/');
        }
    }
}
