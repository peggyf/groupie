<?php

namespace Amu\CliGrouperBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/hello",name="hello")
     * @Template()
     */
    public function indexAction()
    {
        $name = "peg";
        return array('name' => $name);
    }
}
