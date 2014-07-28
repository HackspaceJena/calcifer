<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 28.07.14
 * Time: 22:19
 */

namespace Hackspace\Bundle\CalciferBundle\Command;


use Hackspace\Bundle\CalciferBundle\Entity\Event;
use Hackspace\Bundle\CalciferBundle\Entity\RepeatingEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            $output->writeln(sprintf("Generating Dates from %s to %s",$now->format('Y-m-d'),$end->format('Y-m-d')));
            $output->writeln("Fetching repeating events");
            $entityManager = $this->getContainer()->get('doctrine')->getManager();
            $repo = $entityManager->getRepository('CalciferBundle:RepeatingEvent');
            $entities = $repo->findAll();
            foreach($entities as $entity) {
                /** @var RepeatingEvent $entity */
                $period = new \DatePeriod($entity->nextdate,new \DateInterval($entity->repeating_pattern),$end);
                foreach($period as $date) {
                    /** @var \DateTime $date */
                    $output->writeln(sprintf("Creating Event %s for %s",$entity->summary,$date->format('Y-m-d H:i')));
                    $event = new Event();
                    $event->location = $entity->location;
                    $event->startdate = $date;
                    if ($entity->duration > 0) {
                        $duration = new \DateInterval("PT".$duration.'H');
                        /** @var \DateTime $enddate */
                        $enddate = clone $date;
                        $enddate->add($duration);
                        $entity->enddate = $enddate;
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
                    $entityManager->flush();
                }
            }
        } else {
            $output->writeln('Invalid duration');
        }

    }
}
