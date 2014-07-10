<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Hackspace\Bundle\CalciferBundle\Entity\Location;
use Hackspace\Bundle\CalciferBundle\Entity\Tag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140710120656 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var  ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('CalciferBundle:Event');
        $entities = $repo->findAll();
        if (count($entities) > 0) {
            foreach($entities as $entity) {
                /** @var Event $entity */
                $entity->setSlug(\URLify::filter($entity->getSummary(),255,'de'));
                $em->persist($entity);
                $em->flush();
            }
        }

        $repo = $em->getRepository('CalciferBundle:Location');
        $entities = $repo->findAll();
        if (count($entities) > 0) {
            foreach($entities as $entity) {
                /** @var Location $entity */
                $entity->setSlug(\URLify::filter($entity->getName(),255,'de'));
                $em->persist($entity);
                $em->flush();
            }
        }


        $repo = $em->getRepository('CalciferBundle:Tag');
        $entities = $repo->findAll();
        if (count($entities) > 0) {
            foreach($entities as $entity) {
                /** @var Tag $entity */
                $entity->setSlug(\URLify::filter($entity->getName(),255,'de'));
                $em->persist($entity);
                $em->flush();
            }
        }


    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
