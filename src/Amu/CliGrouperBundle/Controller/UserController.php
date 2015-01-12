<?php

namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Amu\CliGrouperBundle\Entity\Groupe;
use Amu\CliGrouperBundle\Form\GroupeType;
use Amu\CliGrouperBundle\Entity\GroupeSearch;
use Amu\CliGrouperBundle\Form\GroupeSearchType;
use Amu\CliGrouperBundle\Entity\User;
use Amu\CliGrouperBundle\Form\UserType;
use Amu\CliGrouperBundle\Form\UserEditType;
use Amu\CliGrouperBundle\Entity\UserSearch;
use Amu\CliGrouperBundle\Form\UserSearchType;
use Amu\CliGrouperBundle\Entity\Membership;

/**
 * user controller
 *
 * 
 */
class UserController extends Controller {
    
      /**
   * @return  \Amu\AppBundle\Service\Ldap
   */
  private function getLdap() {
    return $this->get('amu.ldap');
  }
  
  /**
     * Affiche l'utilisateur courant.
     *
     * @Route("/", name="user")
     * //Method("POST")
     * @Template()
     * // AMU Modif's 06/2014
     */
    public function indexAction(Request $request)
    {
        $searchForm = $this->createForm(
            new UserSearchType(), null, array(
                                                  'action' => $this->generateUrl('user'),
                                                  'method' => 'POST',
                                                  )
        );
        
        $userSearchForm = $request->get('usersearch');
        $uid = $userSearchForm['uid'];
        
        $users = array();
                   
        

        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted()) {

            // Recherche des utilisateurs dans le LDAP
            $arData=$this->getLdap()->arDatasFilter("uid=".$uid, array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($arData, true) . "</PRE></FONT></FONT>";
            $user = new User();
            $user->setUid($arData[0]['uid'][0]);
            $user->setDisplayname($arData[0]['displayname'][0]);
            $user->setMail($arData[0]['mail'][0]);
            $user->setSn($arData[0]['sn'][0]);
            $user->setTel($arData[0]['telephonenumber'][0]);
            $user->setMemberof(array_splice($arData[0]['memberof'], 1));
        
            $users[] = $user;
            
            $this->getRequest()->getSession()->set('_saved',1);
            
             
        }
        else {
            $this->getRequest()->getSession()->set('_saved', 0);
            
        }
        
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos brut</B>=><FONT color =green><PRE>" . print_r($this->getRequest()->getSession(), true) . "</PRE></FONT></FONT>";
        
        return array(
            'users' => $users,
            'form' => $searchForm->createView()
             );
 
       

       
    }
    
    /**
     * Affiche le formulaire pour éditer un utilisateur
     *
     * @Route("/edit/{uid}", name="user_edit")
     * @Template()
     * // AMU Modif's 06/2014
     */
    public function editAction($uid)
    {
        // Recherche des utilisateurs dans le LDAP
        $arData=$this->getLdap()->arDatasFilter("uid=".$uid, array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
            
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($arData, true) . "</PRE></FONT></FONT>";
        $user = new User();
        $user->setUid($uid);
        $user->setDisplayname($arData[0]['displayname'][0]);
        $user->setMail($arData[0]['mail'][0]);
        $user->setSn($arData[0]['sn'][0]);
        $user->setTel($arData[0]['telephonenumber'][0]);
        $arGroups = array_splice($arData[0]['memberof'], 1);
        $user->setMemberof($arGroups);
        $memberships = new ArrayCollection();
        foreach($arGroups as $groupname)
        {
            $membership = new Membership();
            $membership->setGroupname($groupname);
            $membership->setMemberof(TRUE);
            $membership->setAdminof(FALSE);
            $memberships[] = $membership;
        }
        $user->setMemberships($memberships);
                
        $users[] = $user;
        
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $user->getUid())),
            'method' => 'POST',
        ));

       $this->getRequest()->getSession()->set('_saved',0);
        return array(
            'users'      => $users,
            'form'   => $editForm->createView(),
        );
    }
    
    /**
     * Edite un utilisateur issu du LDAP.
     *
     * @Route("/update/{uid}", name="user_update")
     * @Template("AmuCliGrouperBundle:User:edit.html.twig")
     * // AMU Modif's 06/2014
     */
    public function updateAction(Request $request, $uid)
    {
        // Recherche des utilisateurs dans le LDAP
        $arData=$this->getLdap()->arDatasFilter("uid=".$uid, array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
            
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($arData, true) . "</PRE></FONT></FONT>";
        $user = new User();
        $user->setUid($uid);
        $user->setDisplayname($arData[0]['displayname'][0]);
        $user->setMail($arData[0]['mail'][0]);
        $user->setSn($arData[0]['sn'][0]);
        $user->setTel($arData[0]['telephonenumber'][0]);
        $arGroups = array_splice($arData[0]['memberof'], 1);
        $user->setMemberof($arGroups);
        $memberships = new ArrayCollection();
        foreach($arGroups as $groupname)
        {
            $membership = new Membership();
            $membership->setGroupname($groupname);
            $membership->setMemberof(TRUE);
            $membership->setAdminof(FALSE);
            $memberships[] = $membership;
        }
        $user->setMemberships($memberships);
                                
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $user->getUid())),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $userupdate = new User();
            $userupdate = $editForm->getData();
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $userupdate->getMemberships();
            foreach($m_update as $memb)
            {
                if ($memb->getMemberof())
                {
                    $this->getLdap()->addMemberGroup($memb->getGroupname(), array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delMemberGroup($memb->getGroupname(), array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
            }
            
            
            $this->getRequest()->getSession()->set('_saved',1);
        }
        else {
            $this->getRequest()->getSession()->set('_saved',0);
        }

        return array(
            'user'      => $user,
            'form'   => $editForm->createView(),
        );
    }
  
    /**
     * Ajoute l'appartenance d'un utilisateur à un groupe.
     *
     * @Route("/user_add/{uid}/{group}", name="user_add")
     * @Template("AmuCliGrouperBundle:User:add.html.twig")
     * // AMU Modif's 06/2014
     */
    public function addAction(Request $request, $uid, Groupe $group)
    {
        $dn_group = "cn=" . $group->getCn() . ", ou=groups, dc=univ-amu, dc=fr";
        $b = $this->getLdap()->addMemberGroup($dn_group, array($uid));
        if ($b==true)
        {
            //Le groupe a bien été ajouté pour l'utilsateur
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
            // affichage groupe ajouté
            $groups[0] = $groupe;
            return $this->render('AmuCliGrouperBundle:User:useradd.html.twig',array('cn' => $group->getCn()));
        }
        else 
            return $this->render('AmuCliGrouperBundle:Groupe:groupesearch.html.twig', array('form' => $form->createView()));
        
    }
    
     /**
     * Recherche d'une personne
     *
     * @Route("/usersearch",name="usersearch")
     * @Template()
     */
    public function usersearchAction()
    {
        $form = $this->createForm(new UserSearchType(), new UserSearch());

        return $this->render('AmuCliGrouperBundle:user:usersearch.html.twig', array('form' => $form->createView()));
    }
    /**
    * Recherche de personnes
    *
    * @Route("/user_recherche",name="user_recherche")
    * @Template()
    */
    public function userrechercheAction(Request $request) {
        $usersearch = new UserSearch();
        $users = array();
        
        $form = $this->createForm(new UserSearchType(), new UserSearch());
        $form->handleRequest($request); 
        if ($form->isValid()) {
            $usersearch = $form->getData();
            
            // Recherche des utilisateurs dans le LDAP
            $arData=$this->getLdap()->arDatasFilter("uid=".$usersearch->getUid(), array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($arData, true) . "</PRE></FONT></FONT>";
            $user = new User();
            $user->setUid($usersearch->getUid());
            $user->setDisplayname($arData[0]['displayname'][0]);
            $user->setMail($arData[0]['mail'][0]);
            $user->setSn($arData[0]['sn'][0]);
            $user->setTel($arData[0]['telephonenumber'][0]);
            $user->setMemberof(array_splice($arData[0]['memberof'], 1));
            
            $users[] = $user;
            $this->getRequest()->getSession()->set('_saved',1);
            return array('users' => $users);
            //return $this->render('AmuCliGrouperBundle:User:rechercheuser.html.twig',array('users' => $users));
                       
        }
        $this->getRequest()->getSession()->set('_saved',0);
        return array('form' => $form->createView());
        
        //return $this->render('AmuCliGrouperBundle:User:usersearch.html.twig', array('form' => $form->createView()));
        
    }

  
}
