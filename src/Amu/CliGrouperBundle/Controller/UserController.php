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
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($tab_cn). "</PRE></FONT></FONT>";
        
        // Récupération des groupes dont l'utilisateur est admin
        $arDataAdmin=$this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$uid.",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        $flagMember = array();
        for($i=0;$i<$arDataAdmin["count"];$i++)
        {
            $flagMember[$i] = FALSE;
        }
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arDataAdmin). "</PRE></FONT></FONT>";
        
        $groups = new ArrayCollection();
                
        $memberships = new ArrayCollection();
        // Gestion des groupes dont l'utilisateur est membre
        for($i=0; $i<$arData[0]['memberof']['count'];$i++)
        {
            $membership = new Membership();
            $membership->setGroupname($tab_cn[$i]);
            $membership->setMemberof(TRUE);
            // on teste si l'utilisateur est aussi admin du groupe
            for ($j=0; $j<$arDataAdmin["count"];$j++)
            {
                if ($arDataAdmin[$j]["cn"][0] == $tab_cn[$i])
                {
                    $membership->setAdminof(TRUE);
                    $flagMember[$j] = TRUE;
                    break;
                }
                else
                {
                    $membership->setAdminof(FALSE);
                }
            }
            $memberships[$i] = $membership;
        }
        
        // Gestion des groupes dont l'utilisateur est seulement admin
        for($i=0;$i<$arDataAdmin["count"];$i++)
        {
            if ($flagMember[$i]==FALSE)
            {
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Admin=</B>=><FONT color =green><PRE>" . print_r($arData[$i]). "</PRE></FONT></FONT>";
                // on ajoute le groupe pour l'utilisateur
                $membership = new Membership();
                $membership->setGroupname($arDataAdmin[$i]["cn"][0]);
                $membership->setMemberof(FALSE);
                $membership->setAdminof(TRUE);
                $memberships[] = $membership;
            }
            
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
                $dn_group = "cn=" . $memb->getGroupname() . ", ou=groups, dc=univ-amu, dc=fr";
                if ($memb->getMemberof())
                {
                    $this->getLdap()->addMemberGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delMemberGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                // Traitement des admins
                if ($memb->getAdminof())
                {
                    $this->getLdap()->addAdminGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delAdminGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
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
     * Ajoute les droits d'un utilisateur à un groupe.
     *
     * @Route("/add/{uid}/{cn}",name="user_add")
     * @Template("AmuCliGrouperBundle:User:rechercheuseradd.html.twig")
     */
    public function addAction(Request $request, $uid='', $cn='') {
        // Récupération utilisateur
        $user = new User();
        $user->setUid($uid);
        $arDataUser=$this->getLdap()->arDatasFilter("uid=".$uid, array('displayname', 'memberof'));
        $user->setDisplayname($arDataUser[0]['displayname'][0]);
        $tab = array_splice($arDataUser[0]['memberof'], 1);
        // Tableau des groupes de l'utilisateur
        $tab_cn = array();
        foreach($tab as $dn)
        {
            $tab_cn[] = preg_replace("/(cn=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
        }
        // Recherche des admins du groupe dans le LDAP
        $arAdmins = $this->getLdap()->getAdminsGroup($cn);
                                   
        // on remplit l'objet user avec les droits courants sur le groupe
        $memberships = new ArrayCollection();
        $membership = new Membership();
        $membership->setGroupname($cn);
        // Droits "membre"
        foreach($tab_cn as $cn_g)
        {
            if ($cn==$cn_g)
            {
                $membership->setMemberof(TRUE);
                break;
            }
            else 
            {
                $membership->setMemberof(FALSE);
            }
        }
        // Droits "admin"
        for ($j=0; $j<$arAdmins[0]["amugroupadmin"]["count"]; $j++) 
        {       
            // récupération des uid des admin du groupe
            $uid_admins = preg_replace("/(uid=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
            if ($uid == $uid_admins)
            {
                $membership->setAdminof(TRUE);
                break;
            }
            else 
            {
                $membership->setAdminof(FALSE);
            }
        }
        $memberships[0] = $membership;
        $user->setMemberships($memberships);       
        
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('user_add', array('uid'=> $uid, 'cn' => $cn)),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $userupdate = new User();
            $userupdate = $editForm->getData();
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $userupdate->getMemberships();
            
            //foreach($m_update as $memb)
            for ($i=0; $i<sizeof($m_update); $i++)
            {
                $memb = $m_update[$i];
                
                $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid</B>=><FONT color =green><PRE>" . print_r($m_update, true) . "</PRE></FONT></FONT>";
                // Traitement des membres
                if ($memb->getMemberof())
                {
                    $this->getLdap()->addMemberGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    
                    $this->getLdap()->delMemberGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                
                // Traitement des admins
                if ($memb->getAdminof())
                {
                    $this->getLdap()->addAdminGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delAdminGroup($dn_group, array($uid));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
               
            }
            $this->get('session')->getFlashBag()->add('flash-notice', 'Les droits ont bien été ajoutés');
            $this->getRequest()->getSession()->set('_saved',1);
        }
        else { 
            $this->getRequest()->getSession()->set('_saved',0);
            
        }
        
        return array(
            'user'      => $user,
            'cn' => $cn,
            'form'   => $editForm->createView(),
        );
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
            
            if ($opt=='add')
            {
                return $this->redirect($this->generateUrl('user_add', array('uid'=>$usersearch->getUid(), 'cn'=>$cn)));
            }
            else
            {
            
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
                       
        }
        //$this->getRequest()->getSession()->set('_saved',0);
        //return array('form' => $form->createView());
        
        return $this->render('AmuCliGrouperBundle:User:usersearch.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'cn' => $cn));
        
    }

  
}
