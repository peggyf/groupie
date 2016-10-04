<?php

namespace Amu\CasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class CasController extends Controller
{
    /**
     * @return \Amu\CasBundle\Service\Cas;
     */
    private function getCas()
    {
        return ($this->get('amu.cas'));
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        $login = $login = $this->getCas()->logout();
        $session = $this->getRequest()->getSession();
        $session->clear();
        $session->save();
        // retour à la page d'accueil si on été pas authentifié CAS
        return $this->redirect($this->generateUrl("homepage"));
    }
}
