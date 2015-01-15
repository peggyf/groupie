<?php

namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
// these import the "@Route" and "@Template" annotations

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Amu\CliGrouperBundle\Entity\Group;
use Amu\CliGrouperBundle\Form\GroupCreateType;
use Amu\CliGrouperBundle\Form\GroupSearchType;
use Amu\CliGrouperBundle\Entity\User;
use Amu\CliGrouperBundle\Entity\Member;
use Amu\CliGrouperBundle\Form\MemberType;
use Amu\CliGrouperBundle\Form\GroupEditType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * group controller
 * @Route("/group")
 * 
 */
class GroupController extends Controller {
    
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
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
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
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        
        return array('groups' => $groups);
    }
    
        
    /**
     * Recherche de groupes
     *
     * @Route("/search/{opt}/{uid}",name="group_search")
     * @Template()
     */
    public function searchAction(Request $request, $opt='search', $uid='') {
        $groupsearch = new Group();
        $groups = array();
        
        $form = $this->createForm(new GroupSearchType(), new Group());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupsearch = $form->getData(); 
            
            // Recherche des groupes dans le LDAP
            $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=*" . $groupsearch->getCn() . "*))",array("cn","description","amuGroupFilter"));
               
            for ($i=0; $i<$arData["count"]; $i++) {
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>cn=</B>=><FONT color =green><PRE>" . $arData[$i]["cn"][0] . "</PRE></FONT></FONT>";
                $groups[$i] = new Group();
                $groups[$i]->setCn($arData[$i]["cn"][0]);
                $groups[$i]->setDescription($arData[$i]["description"][0]);
                $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
                $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
            }
            
           return $this->render('AmuCliGrouperBundle:Group:recherchegroupe.html.twig',array('groups' => $groups, 'opt' => $opt, 'uid' => $uid));
                       
        }
        return $this->render('AmuCliGrouperBundle:Group:groupesearch.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'uid' => $uid));
        
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
     * Voir les membres et administrateurs d'un groupe.
     *
     * @Route("/update/{cn}", name="group_update")
     * @Template("AmuCliGrouperBundle:Group:edit.html.twig")
     * // AMU Modif's
     */
    public function updateAction(Request $request, $cn)
    {
        $group = new Group();
        $group->setCn($cn);
        $members = new ArrayCollection();
               
        // Recherche des membres dans le LDAP
        $arUsers = $this->getLdap()->getMembersGroup($cn);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos users</B>=><FONT color =green><PRE>" . print_r($arUsers, true) . "</PRE></FONT></FONT>";
          
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $members[$i] = new Member();
            $members[$i]->setUid($arUsers[$i]["uid"][0]);
            $members[$i]->setDisplayname($arUsers[$i]["displayname"][0]);
            $members[$i]->setMail($arUsers[$i]["mail"][0]);
            $members[$i]->setTel($arUsers[$i]["telephonenumber"][0]);
            $members[$i]->setMember(TRUE);
            $members[$i]->setAdmin(FALSE);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        $group ->setMembers($members);
        
        $editForm = $this->createForm(new GroupEditType(), $group, array(
            'action' => $this->generateUrl('group_update', array('cn' => $cn)),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $groupupdate = new Group();
            $groupupdate = $editForm->getData();
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $groupupdate->getMembers();
            foreach($m_update as $memb)
            {
                if ($memb->getMember())
                {
                    //$this->getLdap()->addMemberGroup($memb->getGroupname(), array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delMemberGroup($cn, array($memb->getUid()));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
            }
            
            
            $this->getRequest()->getSession()->set('_saved',1);
        }
        else {
            $this->getRequest()->getSession()->set('_saved',0);
        }

        return array(
            'group'      => $group,
            'form'   => $editForm->createView(),
        );
        
    }
    
    /**
     * Création d'un groupe
     *
     * @Route("/create",name="group_create")
     * @Template("AmuCliGrouperBundle:Group:groupe.html.twig")
     */
    public function createAction(Request $request) {
        
        $group = new Group();
        $groups = array();
        
        $form = $this->createForm(new GroupCreateType(), new Group());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $group = $form->getData();
            
            // Création du groupe dans le LDAP
            $infogroup = $group->infosGroupeLdap();
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($infogroupe, true) . "</PRE></FONT></FONT>";
            $b = $this->getLdap()->createGroupeLdap($infogroup['dn'], $infogroup['infos']);
            if ($b==true)
            {
                //Le groupe a bien été créé
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
                 // affichage groupe créé
                $groups[0] = $group;
                return $this->render('AmuCliGrouperBundle:Group:creationgroupe.html.twig',array('groups' => $groups));
            }
            else 
                return $this->render('AmuCliGrouperBundle:Group:groupe.html.twig', array('form' => $form->createView()));
            
        }
        return $this->render('AmuCliGrouperBundle:Group:groupe.html.twig', array('form' => $form->createView()));

        //return array('groups' => $groups, 
        //             'form' => $form->createView());
    }
    
    
     /**
     * Supprimer un groupe.
     *
     * @Route("/delete/{cn}", name="group_delete")
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
            
            return $this->render('AmuCliGrouperBundle:Group:suppressiongroupe.html.twig',array('cn' => $cn));
        }
        else 
            return $this->render('AmuCliGrouperBundle:Group:groupesearch.html.twig', array('form' => $form->createView()));
    }
    
   
      
}
