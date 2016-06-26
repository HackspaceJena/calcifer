<?php

namespace Hackspace\Bundle\CalciferBundle\Tests\Controller;

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

        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $entity = $repo->findOneBy(['slug' => $slug]);

        $this->assertInstanceOf('Hackspace\Bundle\CalciferBundle\Entity\Event', $entity);

        $this->assertTrue($startdate == $entity->startdate, "Startdate equal");
        $this->assertTrue($enddate == $entity->enddate, "Enddate equal");
        $this->assertTrue($form["summary"]->getValue() == $entity->summary, "Summary equal");
        $this->assertTrue($form["url"]->getValue() == $entity->url, "URL equal");
        $this->assertTrue($form["description"]->getValue() == $entity->description, "Description equal");

    }
}
