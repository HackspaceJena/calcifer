<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 28.07.14
 * Time: 22:19
 */

namespace Hackspace\Bundle\CalciferBundle\Command;


use Doctrine\ORM\EntityManager;
use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Hackspace\Bundle\CalciferBundle\Entity\RepeatingEvent;
use Hackspace\Bundle\CalciferBundle\Entity\RepeatingEventLogEntry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use enko\RelativeDateParser\RelativeDateParser;

class GenerateEventsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('calcifer:events:generate')
            ->setDescription('Generate events from repeating events')
            ->addOption('duration', 'd', InputOption::VALUE_OPTIONAL, 'The duration you want to generate events into the future. Default is 2 monts','2 months')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $duration = \DateInterval::createFromDateString($input->getOption('duration'));
        if ($duration instanceof \DateInterval) {
            $now = new \DateTime();
            $end = new \DateTime();
            $end->add($duration);
            /** @var EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine')->getManager();
            $repo = $entityManager->getRepository('CalciferBundle:RepeatingEvent');
            $entities = $repo->findBy([],['id' => 'asc']);
            foreach($entities as $entity) {
                /** @var RepeatingEvent $entity */
                $next_date = is_null($entity->nextdate) ? new DateTime() : $entity->nextdate;
                /** This is a fuggly hack. It would be best to store the named timezone also in the
                 *  database to avoid problems with daylight savings.  */
                $next_date->setTimezone(new \DateTimeZone('Europe/Berlin'));
                $parser = new RelativeDateParser($entity->repeating_pattern,$next_date,'de');
                $event = null;
                while (($next_date = $parser->getNext()) < $end) {
                    /** @var \DateTime $next_date */
                    $event = new Event();
                    $event->location = $entity->location;
                    $event->startdate = $next_date;
                    if ($entity->duration > 0) {
                        $duration = new \DateInterval("PT".$entity->duration.'M');
                        /** @var \DateTime $enddate */
                        $enddate = clone $next_date;
                        $enddate->add($duration);
                        $event->enddate = $enddate;
                    }
                    $event->summary = $entity->summary;
                    $event->description = $entity->description;
                    $event->url = $entity->url;
                    $entityManager->persist($event);
                    $entityManager->flush();
                    $event->slug = \URLify::filter($event->id . '-' . $event->summary,255,'de');
                    $entityManager->persist($event);
                    $entityManager->flush();
                    foreach($entity->getTags() as $tag) {
                        $event->addTag($tag);
                    }
                    $entityManager->persist($event);
                    $logEntry = new RepeatingEventLogEntry();
                    $logEntry->event = $event;
                    $logEntry->repeating_event = $entity;
                    $logEntry->event_startdate = $event->startdate;
                    $logEntry->event_enddate = $event->enddate;
                    $entityManager->persist($logEntry);
                    $entityManager->flush();
                    $parser->setNow($next_date);
                }
                if (!is_null($event)) {
                    $entity->nextdate = $event->startdate;
                    $entityManager->persist($entity);
                    $entityManager->flush();
                }
            }
        } else {
            $output->writeln('Invalid duration');
        }

    }
}
