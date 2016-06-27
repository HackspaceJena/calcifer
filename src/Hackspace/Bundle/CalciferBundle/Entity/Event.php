<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;



use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Event
 *
 * @property \DateTime $startdate
 * @property \DateTime $enddate
 * @property string $summary
 * @property string $description
 * @property Location $location
 * @property string $url
 * @property array $tags
 *
 * @ORM\Table(name="events")
 * @ORM\Entity
 */
class Event extends BaseEntity
{
    use TagTrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startdate", type="datetimetz")
     */
    protected $startdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate", type="datetimetz", nullable=true)
     */
    protected $enddate;

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
     * @var string
     *
     * @ORM\Column(name="locations_id", type="integer", nullable=true)
     */
    protected $locations_id;

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
     * @ORM\JoinTable(name="events2tags",
     *      joinColumns={@ORM\JoinColumn(name="events_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tags_id", referencedColumnName="id")}
     *      )
     */
    protected $tags = [];

    /**
     * @param \Hackspace\Bundle\CalciferBundle\Entity\Location $location
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return \Hackspace\Bundle\CalciferBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function isValid() {
        $errors = [];
        if (!($this->startdate instanceof \DateTime)) {
            $errors['startdate'] = 'Bitte gebe ein Startdatum ein.';
        }
        if ((!is_null($this->startdate)) && (!is_null($this->enddate)) && ($this->enddate < $this->startdate)) {
            $errors['enddate'] = 'Bitte setze ein Enddatum das nach dem Startdatum ist.';
        }
        if (strlen($this->summary) == 0) {
            $errors['summary'] = 'Bitte gebe eine Zusammenfassung an.';
        }

        return (count($errors) > 0) ? $errors : true;
    }

    public function getFormatedDate() {
        $retval = $this->startdate->format('Y-m-d H:i');
        if (!is_null($this->enddate)) {
            $retval .= " — ";
            if ($this->startdate->format('Y-m-d') == $this->enddate->format('Y-m-d')) {
                $retval .= $this->enddate->format('H:i');
            } else {
                $retval .= $this->enddate->format('Y-m-d H:i');
            }
        }
        return $retval;
    }


    public function ConvertToCalendarEvent() {
        $categories = [];
        foreach($this->tags as $tag) {
            $categories[] = $tag->name;
        }

        if (array_key_exists('HTTP_HOST',$_SERVER)) {
            $uid = sprintf("https://%s/termine/%s",$_SERVER['HTTP_HOST'],$this->slug);
        } else {
            $uid = sprintf("https://localhost/termine/%s",$this->slug);
        }

        $event = [
            'SUMMARY' => $this->summary,
            'DTSTART' => $this->startdate,
            'DESCRIPTION' => $this->description,
            'URL' => $this->url,
            'CATEGORIES' => $categories,
            'UID' => $uid,
        ];
        if (!is_null($this->enddate))
            $event["DTEND"] = $this->enddate;

        if ($this->location instanceof Location) {
            $event["LOCATION"] = $this->location->name;
            if (\is_float($this->location->lon) && \is_float($this->location->lat)) {
                $event["GEO"] = [$this->location->lat, $this->location->lon];
            }
        }
        if (!array_key_exists('HTTP_HOST',$_SERVER)) {
            $dtstamp = new \DateTime();
            $dtstamp->setDate(2016,06,27);
            $dtstamp->setTime(0,0,0);
            $event['DTSTAMP'] = $dtstamp;
        }

        return $event;
    }
}
