<?php

namespace Hackspace\Bundle\CalciferBundle\Tests\Controller;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityRepository;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{

    private function initClient() {
        $this->loadFixtures([]);

        $client = static::makeClient();
        return $client;
    }

    public function testEmptyListing() {
        $client = $this->initClient();
        $crawler = $client->request('GET', '/');
        $this->assertStatusCode(200, $client);
    }

    public function testPostEventForm()
    {
        $client = $this->initClient();

        $url = $client->getContainer()->get('router')->generate('_new');

        $crawler = $client->request('GET', $url);
        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('save')->form();

        $now = new \DateTime();
        $now->setTime(0,0,0);

        $tz = new \DateTimeZone("Europe/Berlin");
        $now->setTimezone($tz);

        $dateformat = "Y-m-d H:i";
        $startdate = clone $now;
        $startdate->add(new \DateInterval("P1D"));
        $enddate = clone $now;
        $enddate->add(new \DateInterval("P1DT2H"));
        $form['startdate'] = $startdate->format("Y-m-d H:i");
        $form['enddate'] = $enddate->format("Y-m-d H:i");
        $form['summary'] = "Testevent";
        $form['url'] = "https://calcifer.datenknoten.me";
        $form["location"] = "Krautspace";
        $form["location_lat"] = 1;
        $form["location_lon"] = 2;
        $form["tags"] = "foo,bar,krautspace";
        $form["description"] = "Testdescription";

        $crawler = $client->submit($form);

        $this->assertStatusCode(302, $client);

        $target = $client->getResponse()->headers->get('location');

        $slug = explode("/",$target)[2];

        $this->assertGreaterThan(0,strlen($slug));

        /** @var EntityManagerDecorator $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->innerJoin('e.tags','t')
            ->innerJoin('e.location','l')
            ->where('e.slug>= :slug')
            ->setParameter('slug', $slug);
        $entities = $qb->getQuery()->execute();

        $this->assertCount(1,$entities);

        /** @var Event $entity */
        $entity = $entities[0];

        $this->assertInstanceOf('Hackspace\Bundle\CalciferBundle\Entity\Event', $entity);

        $this->assertTrue($startdate == $entity->startdate, "Startdate equal");
        $this->assertTrue($enddate == $entity->enddate, "Enddate equal");
        $this->assertTrue($form["summary"]->getValue() == $entity->summary, "Summary equal");
        $this->assertTrue($form["url"]->getValue() == $entity->url, "URL equal");
        $this->assertTrue($form["description"]->getValue() == $entity->description, "Description equal");

        $tags = explode(",",$form["tags"]->getValue());
        foreach($entity->tags as $tag) {
            $this->assertTrue(in_array($tag->name,$tags));
        }

        $this->assertTrue($form["location"]->getValue() == $entity->location->name);
        $this->assertTrue($form["location_lat"]->getValue() == $entity->location->lat);
        $this->assertTrue($form["location_lon"]->getValue() == $entity->location->lon);

    }

    public function testICS() {
        $this->testPostEventForm();

        $client = static::makeClient();

        // events_ics

        $url = $client->getContainer()->get('router')->generate('events_ics');

        $crawler = $client->request('GET', $url);
        $this->assertStatusCode(200, $client);

        $test_doc = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:https://localhost/termine/testevent
DTSTAMP;TZID=Europe/Berlin:20160627T000000
SUMMARY:Testevent
DTSTART;TZID=Europe/Berlin:20160628T000000
DESCRIPTION:Testdescription
URL;VALUE=URI:https://calcifer.datenknoten.me
CATEGORIES:foo,bar,krautspace
DTEND;TZID=Europe/Berlin:20160628T020000
LOCATION:Krautspace
GEO:1;2
END:VEVENT
END:VCALENDAR

EOF;

        $this->assertEquals($test_doc, $client->getResponse()->getContent());
    }
}
