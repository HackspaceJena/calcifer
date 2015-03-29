<?php

namespace Hackspace\Bundle\CalciferBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/über", name="about_calcifer")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }
}
