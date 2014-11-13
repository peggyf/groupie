<?php

namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Amu\CliGrouperBundle\Entity\Groupe;

class GroupeController extends Controller {
    
     /**
   * @return  \Amu\AppBundle\Service\Ldap
   */
  private function getLdap() {
    return $this->get('amu.ldap');
  }

    /**
     * Affiche tous les groupes
     *
     * @Route("/tous_les_groupes",name="tous_les_groupes")
     * @Template()
     */
    public function touslesgroupesAction() {
          
        $arData=$this->getLdap()->arDatasFilter("cn=amu:*",array("cn","description","amuGroupFilter"));
               
        $groups = array();
        for ($i=0; $i<$arData["count"]; $i++) {
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>cn=</B>=><FONT color =green><PRE>" . $arData[$i]["cn"][0] . "</PRE></FONT></FONT>";
            $groups[$i] = new Groupe();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $groups[$i]->setMember("");
            $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }

        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos brut</B>=><FONT color =green><PRE>" . print_r($groups, true) . "</PRE></FONT></FONT>";
        
        return array('groups' => $groups);
    }

 
    /**
     * Affiche tous les groupes
     *
     * @Route("/mes_groupes",name="mes_groupes")
     * @Template()
     */
    public function mesgroupesAction() {
        
        $arData=$this->getLdap()->arDatasFilter("uid=pfernandezblanco",array("memberof"));
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arData). "</PRE></FONT></FONT>";
        $groups = array();
        
        for ($i=0; $i<$arData[0]["memberof"]["count"]; $i++) {
            $cn = strstr($arData[0]["memberof"][$i],"amu");
            $cn = strstr($cn,",ou",true);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>cn=</B>=><FONT color =green><PRE>" . $cn . "</PRE></FONT></FONT>";
                    
            $arDataGroup=$this->getLdap()->arDatasFilter("cn=".$cn,array("cn","description","amuGroupFilter"));
            
            $groups[$i] = new Groupe();
            $groups[$i]->setCn($arDataGroup[0]["cn"][0]);
            $groups[$i]->setDescription($arDataGroup[0]["description"][0]);
            $groups[$i]->setAmugroupfilter($arDataGroup[0]["amugroupfilter"][0]);
            $groups[$i]->setMember("");
            $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        
        return array('groups' => $groups);
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
