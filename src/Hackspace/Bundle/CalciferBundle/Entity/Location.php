<?php

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Location
 *
 * @property string $name
 * @property float $lon
 * @property float $lat
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


}
