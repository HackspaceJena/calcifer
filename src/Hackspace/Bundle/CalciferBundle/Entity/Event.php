<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;



use Doctrine\ORM\Mapping as ORM;
use Jsvrcek\ICS\Model\Description\Location As EventLocation;
use Symfony\Component\Validator\Constraints\DateTime;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Relationship\Attendee;
use Jsvrcek\ICS\Model\Relationship\Organizer;

use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\Model\Description\Geo;

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
        $event = new CalendarEvent();
        $event->setStart($this->startdate);
        if ($this->enddate instanceof \DateTime)
            $event->setEnd($this->enddate);
        $event->setSummary($this->summary);
        $event->setUrl($this->url);
        $event->setUid($this->id);
        if (count($this->tags) > 0) {
            $categories = [];
            foreach($this->tags as $tag) {
                $event->addCategory($tag->name);
            }
        }
        if ($this->location instanceof Location) {
            $location = new EventLocation();
            $location->setName($this->location->name);
            $event->setLocations([$location]);
            if (\is_float($this->location->lon) && \is_float($this->location->lat)) {
                $geo = new Geo();
                $geo->setLatitude($this->location->lat);
                $geo->setLongitude($this->location->lon);
                $event->setGeo($geo);
            }
        }
        $event->setDescription(str_replace("\r","",str_replace("\n",'\n',$this->description)));
        return $event;
    }
}
