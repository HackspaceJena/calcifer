<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * RepeatEvent
 *
 * @property \DateTime $nextdate
 * @property integer $duration
 * @property string $repeating_pattern
 * @property string $summary
 * @property string $description
 * @property Location $location
 * @property string $url
 * @property array $tags
 *
 * @ORM\Table(name="repeating_events")
 * @ORM\Entity
 */
class RepeatingEvent extends BaseEntity
{
    use TagTrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="nextdate", type="datetimetz")
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
     * @ORM\Column(name="repeating_pattern", type="string", length=255)
     */
    protected $repeating_pattern = '';

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
     * @ORM\JoinTable(name="repeating_events2tags",
     *      joinColumns={@ORM\JoinColumn(name="repeating_events_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tags_id", referencedColumnName="id")}
     *      )
     */
    protected $tags = [];

    public function getFormatedRepeatPattern() {
        switch($this->repeating_pattern) {
            case 'P7D':
                return 'Wöchentlich';
            case 'P14D':
                return 'Alle 2 Wochen';
            case 'P1M':
                return 'Monatlich';
            default:
                return $this->repeating_pattern;
        }
    }
}