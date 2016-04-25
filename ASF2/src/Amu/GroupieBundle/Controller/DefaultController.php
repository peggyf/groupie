<?php

namespace Amu\GroupieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /*
     * @Template()
     */
    public function indexAction($request)
    {
        return $this->redirect($this->generateUrl('welcome'));
    }
}
