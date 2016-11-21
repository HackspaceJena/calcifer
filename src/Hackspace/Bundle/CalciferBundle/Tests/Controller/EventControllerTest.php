<?php

namespace Hackspace\Bundle\CalciferBundle\Tests\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityRepository;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    /** @var \DateTime */
    private $now = null;

    /** @var \DateTime */
    private $startdate = null;

    /** @var \DateTime */
    private $enddate = null;

    const dateformat = "Y-m-d H:i";

    /**
     * EventControllerTest constructor.
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name,$data,$dataName);
        $this->now = new \DateTime();
        $this->now->setTime(0,0,0);

        $tz = new \DateTimeZone("Europe/Berlin");
        $this->now->setTimezone($tz);

        $this->startdate = clone $this->now;
        $this->startdate->add(new \DateInterval("P1D"));
        $this->enddate = clone $this->now;
        $this->enddate->add(new \DateInterval("P1DT2H"));

    }

    public static function runCommandStatic($name) {
        $command = sprintf("php app/console %s", $name);
        $output = "";
        exec($command,$output);
        return $output;
    }

    public static function setUpBeforeClass()
    {
        EventControllerTest::runCommandStatic("doctrine:database:drop --force --env=test");
        EventControllerTest::runCommandStatic("doctrine:database:create --env=test");
        EventControllerTest::runCommandStatic("doctrine:schema:create --env=test");
    }


    public function testEmptyListing() {
        $client = static::makeClient();
        $crawler = $client->request('GET', '/');
        $this->assertStatusCode(200, $client);
    }

    public function testPostEventForm()
    {
        $client = static::makeClient();

        $url = $client->getContainer()->get('router')->generate('_new');

        $crawler = $client->request('GET', $url);
        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('save')->form();




        $form['startdate'] = $this->startdate->format(EventControllerTest::dateformat);
        $form['enddate'] = $this->enddate->format(EventControllerTest::dateformat);
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

        $this->assertTrue($this->startdate == $entity->startdate, "Startdate equal");
        $this->assertTrue($this->enddate == $entity->enddate, "Enddate equal");
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

        $em->close();

        /** @var Registry $doc */
        $doc = $this->getContainer()->get('doctrine');

        foreach($doc->getConnections() as $connection) {
            $connection->close();
        }
    }

    public function testICS() {

        $client = static::makeClient();

        // events_ics

        $url = $client->getContainer()->get('router')->generate('events_ics');

        $crawler = $client->request('GET', $url);
        $this->assertStatusCode(200, $client);

        $test_doc = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.1.1//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:https://localhost/termine/testevent
DTSTAMP;TZID=Europe/Berlin:20160627T000000
SUMMARY:Testevent
DTSTART:%s
DESCRIPTION:Testdescription
URL;VALUE=URI:https://calcifer.datenknoten.me
CATEGORIES:foo,bar,krautspace
DTEND:%s
LOCATION:Krautspace
GEO:1;2
END:VEVENT
END:VCALENDAR

EOF;
        $new_tz = new \DateTimeZone("UTC");
        $this->startdate->setTimezone($new_tz);
        $this->enddate->setTimezone($new_tz);
        $start = $this->startdate->format("Ymd") . "T" . $this->startdate->format("His") . "Z";
        $end = $this->enddate->format("Ymd") . "T" . $this->enddate->format("His") . "Z";

        $test_doc = sprintf($test_doc,$start,$end);

        $content = $client->getResponse()->getContent();

        $content = preg_replace('~\R~u', "\r\n", $content);
        $test_doc = preg_replace('~\R~u', "\r\n", $test_doc);

        $this->assertGreaterThan(0,strlen($content));
        $this->assertEquals($test_doc, $content);
    }
}
