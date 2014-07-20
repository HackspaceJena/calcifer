<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * RepeatEvent
 *
 * @ORM\Table(name="repeat_events")
 * @ORM\Entity
 */
class RepeatingEvent extends BaseEntity
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startdate", type="datetimetz")
     */
    protected $nextdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    protected $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $repeat_pattern = '';

    /**
     * @var string
     *
     * @ORM\Column(name="summary", type="string", length=255)
     */
    protected $summary;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="locations_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Tag")
     * @ORM\JoinTable(name="repeat_events2tags",
     *      joinColumns={@ORM\JoinColumn(name="repeat_events_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tags_id", referencedColumnName="id")}
     *      )
     */
    protected $tags = [];

}