<?php

namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
// these import the "@Route" and "@Template" annotations

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Amu\CliGrouperBundle\Entity\Groupe;
use Amu\CliGrouperBundle\Form\GroupeType;
use Amu\CliGrouperBundle\Entity\GroupeSearch;
use Amu\CliGrouperBundle\Form\GroupeSearchType;
use Amu\CliGrouperBundle\Entity\User;

/**
 * groupe controller
 *
 * 
 */
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
          
        $arData=$this->getLdap()->arDatasFilter("(objectClass=groupofNames)",array("cn","description","amuGroupFilter"));
               
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
    public function mesgroupesAction(Request $request) {
        
        $arData=$this->getLdap()->arDatasFilter("member=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arData). "</PRE></FONT></FONT>";
        $groups = array();
        
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Groupe();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $groups[$i]->setMember("");
            $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        
        return array('groups' => $groups);
    }
    
    /**
     * Recherche d'un groupe
     *
     * @Route("/groupesearch",name="groupesearch")
     * @Template()
     */
    public function groupesearchAction()
    {
        $form = $this->createForm(new GroupeSearchType(), new GroupeSearch());

        return $this->render('AmuCliGrouperBundle:Groupe:groupesearch.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * Recherche de groupes
     *
     * @Route("/recherche_groupe",name="recherche_groupe")
     * @Template()
     */
    public function recherchegroupeAction(Request $request) {
        $groupesearch = new GroupeSearch();
        $groups = array();
        
        $form = $this->createForm(new GroupeSearchType(), new GroupeSearch());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupesearch = $form->getData();
            
            // Recherche des groupes dans le LDAP
            $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=*" . $groupesearch->getCn() . "*))",array("cn","description","amuGroupFilter"));
               
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
            
           return $this->render('AmuCliGrouperBundle:Groupe:recherchegroupe.html.twig',array('groups' => $groups));
                       
        }
        return $this->render('AmuCliGrouperBundle:Groupe:groupesearch.html.twig', array('form' => $form->createView(), 'del' => false));
        
    }
    
    /**
     * Voir les membres et administrateurs d'un groupe.
     *
     * @Route("/voir/{cn}", name="voir_groupe")
     * @Template()
     * // AMU Modif's
     */
    public function voirAction(Request $request, $cn)
    {
        $users = array();
        
        // Recherche des membres dans le LDAP
        $arUsers = $this->getLdap()->getMembersGroup($cn);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos users</B>=><FONT color =green><PRE>" . print_r($arUsers, true) . "</PRE></FONT></FONT>";
          
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $users[$i] = new User();
            $users[$i]->setUid($arUsers[$i]["uid"][0]);
            $users[$i]->setSn($arUsers[$i]["sn"][0]);
            $users[$i]->setDisplayname($arUsers[$i]["displayname"][0]);
            $users[$i]->setMail($arUsers[$i]["mail"][0]);
            $users[$i]->setTel($arUsers[$i]["telephonenumber"][0]);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        
        return array('cn' => $cn,
                    'nb_membres' => $arUsers["count"], 
                    'users' => $users);
    }
    
    /**
     * Création d'un groupe
     *
     * @Route("/groupe",name="groupe")
     * @Template()
     */
    public function groupeAction()
    {
        $form = $this->createForm(new GroupeType(), new Groupe());

        return $this->render('AmuCliGrouperBundle:Groupe:groupe.html.twig', array('form' => $form->createView()));
    }
    
  /**
     * Création d'un groupe
     *
     * @Route("/creation_groupe",name="creation_groupe")
     * @Template("AmuCliGrouperBundle:Groupe:groupe.html.twig")
     */
    public function creationgroupeAction(Request $request) {
        
        $groupe = new Groupe();
        $groups = array();
        
        $form = $this->createForm(new GroupeType(), new Groupe());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupe = $form->getData();
            
            // Création du groupe dans le LDAP
            $infogroupe = $groupe->infosGroupeLdap();
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($infogroupe, true) . "</PRE></FONT></FONT>";
            $b = $this->getLdap()->createGroupeLdap($infogroupe['dn'], $infogroupe['infos']);
            if ($b==true)
            {
                //Le groupe a bien été créé
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
                 // affichage groupe créé
                $groups[0] = $groupe;
                return $this->render('AmuCliGrouperBundle:Groupe:creationgroupe.html.twig',array('groups' => $groups));
            }
            else 
                return $this->render('AmuCliGrouperBundle:Groupe:groupe.html.twig', array('form' => $form->createView()));
            
        }
        return $this->render('AmuCliGrouperBundle:Groupe:groupe.html.twig', array('form' => $form->createView()));

        //return array('groups' => $groups, 
        //             'form' => $form->createView());
    }
    
    
     /**
     * Supprimer un groupe.
     *
     * @Route("/delete/{cn}", name="delete_groupe")
     * @Template()
     * // AMU Modif's
     */
    public function deleteAction(Request $request, $cn)
    {
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>delete groupe ldap </B>=><FONT color =green><PRE>" . $cn . "</PRE></FONT></FONT>";
        $b = $this->getLdap()->deleteGroupeLdap($cn);
        if ($b==true)
        {
            //Le groupe a bien été supprimé
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
            // affichage groupe supprimé
            $groups[0] = $groupe;
            return $this->render('AmuCliGrouperBundle:Groupe:suppressiongroupe.html.twig',array('cn' => $cn));
        }
        else 
            return $this->render('AmuCliGrouperBundle:Groupe:groupesearch.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * Suppression d'un groupe
     *
     * @Route("/suppression_groupe",name="suppression_groupe")
     * @Template()
     */
    public function SuppressionGroupeAction(Request $request) {
        
        $groupesearch = new GroupeSearch();
        $groups = array();
        
        $form = $this->createForm(new GroupeSearchType(), new GroupeSearch());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupesearch = $form->getData();
            
            // Recherche des groupes dans le LDAP
            $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=*" . $groupesearch->getCn() . "*))",array("cn","description","amuGroupFilter"));
               
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
            
           return $this->render('AmuCliGrouperBundle:Groupe:recherchegroupesup.html.twig',array('groups' => $groups));
                       
        }
        return $this->render('AmuCliGrouperBundle:Groupe:groupesearch.html.twig', array('form' => $form->createView(), 'del' => TRUE));
        
    }
  
}
