<?php

namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class GroupeController extends Controller {

    /**
     * Affiche tous les groupes
     *
     * @Route("/tous_les_groupes",name="tous_les_groupes")
     */
    public function TouslesGroupesAction() {
        
        
        return $this->render('AmuCliGrouperBundle:Groupe:touslesgroupes.html.twig');
    }

 
    /**
     * Affiche tous les groupes
     *
     * @Route("/mes_groupes",name="mes_groupes")
     */
    public function MesGroupesAction() {
        
        return $this->render('AmuCliGrouperBundle:Groupe:mesgroupes.html.twig');
    }
 
  /**
     * Création d'un groupe
     *
     * @Route("/creation_groupe",name="creation_groupe")
     */
    public function CreationGroupeAction() {
        
        return $this->render('AmuCliGrouperBundle:Groupe:creationgroupe.html.twig');
    }
    
    /**
     * Suppression d'un groupe
     *
     * @Route("/suppression_groupe",name="suppression_groupe")
     */
    public function SuppressionGroupeAction() {
        
        return $this->render('AmuCliGrouperBundle:Groupe:suppressiongroupe.html.twig');
    }
  
}
