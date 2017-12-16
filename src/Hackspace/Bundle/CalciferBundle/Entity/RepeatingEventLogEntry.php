<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 21.11.2016
 * Time: 21:15
 */

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RepeatingEventLogEntry
 * @package Hackspace\Bundle\CalciferBundle\Entity
 *
 * @property RepeatingEvent $repeating_event
 * @property Event $event
 * @property \DateTime $event_startdate
 * @property \DateTime $event_enddate
 *
 * @ORM\Table(name="repeating_events_log_entries")
 * @ORM\Entity
 */
class RepeatingEventLogEntry extends BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="repeating_events_id", type="integer", nullable=false)
     */
    protected $repeating_events_id;

    /**
     * @var RepeatingEvent
     *
     * @ORM\ManyToOne(targetEntity="RepeatingEvent")
     * @ORM\JoinColumn(name="repeating_events_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $repeating_event;

    /**
     * @var string
     *
     * @ORM\Column(name="events_id", type="integer", nullable=false)
     */
    protected $events_id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="events_id", referencedColumnName="id")
     */
    protected $event;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="event_startdate", type="datetimetz")
     */
    protected $event_startdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="event_enddate", type="datetimetz", nullable=true)
     */
    protected $event_enddate;
}
