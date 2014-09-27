<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Location
 *
 * @property string $name
 * @property string $description
 * @property float $lon
 * @property float $lat
 * @property string $streetaddress
 * @property string $streetnumber
 * @property string $zipcode;
 * @property string $city
 *
 * @ORM\Table(name="locations")
 * @ORM\Entity
 */
class Location extends BaseEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="streetaddress", type="string", length=255, nullable=true)
     */
    protected $streetaddress;

    /**
     * @var string
     *
     * @ORM\Column(name="streetnumber", type="string", length=255, nullable=true)
     */
    protected $streetnumber;

    /**
     * @var string
     *
     * @ORM\Column(name="zipcode", type="string", length=255, nullable=true)
     */
    protected $zipcode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    protected $city;

    /**
     * @var float
     *
     * @ORM\Column(name="lon", type="float", nullable=true)
     */
    protected $lon;

    /**
     * @var float
     *
     * @ORM\Column(name="lat", type="float", nullable=true)
     */
    protected $lat;

    public function hasAddress() {
        return ((strlen($this->streetaddress) > 0) && (strlen($this->city)));
    }


}
