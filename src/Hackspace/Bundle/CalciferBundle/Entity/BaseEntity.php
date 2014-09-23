<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 13.07.14
 * Time: 13:55
 */

namespace Hackspace\Bundle\CalciferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * A baseclass for all other entities
 *
 * @property integer $id
 * @property string $slug
 *
 * @ORM\MappedSuperclass
 */
abstract class BaseEntity {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255,options={"default" = ""})
     */
    protected $slug = '';

    public function __isset($name) {
        if (property_exists($this,$name)) {
            return true;
        } else {
            return false;
        }
    }

    public function __get($name) {
        if (property_exists($this,$name)) {
            return $this->$name;
        } else {
            throw new \Exception("Property {$name} does not Exists");
        }
    }

    public function __set($name,$value) {
        if (property_exists($this,$name)) {
            $this->$name = $value;
            return $this;
        } else {
            throw new \Exception("Property {$name} does not Exists");
        }
    }

    public function generateSlug($name,EntityManager $em) {
        $slug = \URLify::filter($name, 255, 'de');

        /** @var EntityRepository $repo */
        $repo = $em->getRepository(get_class($this));

        $entity = $repo->findOneBy(['slug' => $slug]);

        if (is_null($entity)) {
            return $slug;
        } else {
            $counter = 1;
            while (true) {
                $new_slug = $slug . '-' . $counter;
                $entity = $repo->findOneBy(['slug' => $new_slug]);
                if (is_null($entity)) {
                    return $new_slug;
                }
                if ($counter === 100) {
                    throw new \Exception('There are 100 events with the same name, pick a fresh one!');
                }
                $counter++;
            }
        }
        return null;
    }
} 