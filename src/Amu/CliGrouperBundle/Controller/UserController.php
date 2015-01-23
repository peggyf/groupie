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
use Amu\CliGrouperBundle\Entity\Group;
use Amu\CliGrouperBundle\Entity\User;
use Amu\CliGrouperBundle\Form\UserEditType;
use Amu\CliGrouperBundle\Form\UserSearchType;
use Amu\CliGrouperBundle\Entity\Membership;

/**
 * user controller
 * @Route("/user")
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
            // Récupération du cn des groupes (memberof)
            $tab = array_splice($arData[0]['memberof'], 1);
            $tab_cn = array();
            foreach($tab as $dn)
            {
                $tab_cn[] = preg_replace("/(cn=)(([a-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
            }
            $user->setMemberof($tab_cn); 
        
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
        $tab = array_splice($arData[0]['memberof'], 1);
        $tab_cn = array();
        foreach($tab as $dn)
        {
            $tab_cn[] = preg_replace("/(cn=)(([a-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
        }
        $user->setMemberof($tab_cn);  
        
        $memberships = new ArrayCollection();
        foreach($tab_cn as $groupname)
        {
            $membership = new Membership();
            $membership->setGroupname($groupname);
            $membership->setMemberof(TRUE);
            $membership->setAdminof(FALSE);
            $memberships[] = $membership;
        }
        $user->setMemberships($memberships);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Tab groupes</B>=><FONT color =green><PRE>" . print_r($user, true) . "</PRE></FONT></FONT>";
                                
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $uid)),
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
                    //$this->getLdap()->addMemberGroup($memb->getGroupname(), array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $dn_group = "cn=" . $memb->getGroupname() . ", ou=groups, dc=univ-amu, dc=fr";
                    $this->getLdap()->delMemberGroup($dn_group, array($user->getUid()));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
            }
            $this->get('session')->getFlashBag()->add('flash-notice', 'Les modifications ont bien été enregistrées');
            
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
     * @Route("/add/{uid}/{cn}", name="user_add")
     * @Template("AmuCliGrouperBundle:User:add.html.twig")
     * // AMU Modif's 06/2014
     */
    public function addAction(Request $request, $uid, $cn)
    {
        $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";
        $b = $this->getLdap()->addMemberGroup($dn_group, array($uid));
                
        return $this->render('AmuCliGrouperBundle:User:useradd.html.twig',array('cn' => $cn, 'uid' => $uid, 'success' => $b));
        
    }
    
     
    /**
    * Recherche de personnes
    *
    * @Route("/search/{opt}/{cn}",name="user_search")
    * @Template()
    */
    public function searchAction(Request $request, $opt='search', $cn='') {
        $usersearch = new User();
        $users = array();
        
        $form = $this->createForm(new UserSearchType(), new User());
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
            // Récupération du cn des groupes (memberof)
            $tab = array_splice($arData[0]['memberof'], 1);
            $tab_cn = array();
            foreach($tab as $dn)
            {
                $tab_cn[] = preg_replace("/(cn=)(([a-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
            }
            $user->setMemberof($tab_cn); 
                        
            $users[] = $user; 
            //$this->getRequest()->getSession()->set('_saved',1);
            //return array('users' => $users);
            return $this->render('AmuCliGrouperBundle:User:rechercheuser.html.twig',array('users' => $users, 'opt' => $opt, 'cn' => $cn));
                       
        }
        //$this->getRequest()->getSession()->set('_saved',0);
        //return array('form' => $form->createView());
        
        return $this->render('AmuCliGrouperBundle:User:usersearch.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'cn' => $cn));
        
    }

  
}
