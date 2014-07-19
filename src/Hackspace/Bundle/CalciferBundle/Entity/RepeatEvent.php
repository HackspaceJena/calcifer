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
class RepeatEvent extends Event
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $repeat_pattern = '';

}