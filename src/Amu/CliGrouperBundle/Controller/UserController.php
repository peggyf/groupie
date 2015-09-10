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
use Amu\CliGrouperBundle\Form\PrivateUserEditType;
use Amu\CliGrouperBundle\Form\UserSearchType;
use Amu\CliGrouperBundle\Form\UserMultipleType;
use Amu\CliGrouperBundle\Entity\Membership;
use Amu\CliGrouperBundle\Entity\Member;
use Amu\CliGrouperBundle\Form\PrivateGroupEditType;

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
    return $this->get('CliGrouper.ldap');
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
            
            // Test de la validité de l'uid
            if ($arData[0]['uid'][0] == '')
            {
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>index</B>=><FONT color =green><PRE>"."</PRE></FONT></FONT>";
                $this->get('session')->getFlashBag()->add('flash-notice', 'L\'uid n\'est pas valide');
                $this->getRequest()->getSession()->set('_saved', 0);
            }
            else
            {
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
                    $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
                }
                $user->setMemberof($tab_cn); 
        
                $users[] = $user;
            
                $this->getRequest()->getSession()->set('_saved',1);
            }
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
        
        // Test de la validité de l'uid
        if ($arData[0]['uid'][0] == '')
        {
            // echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>edit uid</B>=><FONT color =green><PRE>" . $uid . "</PRE></FONT></FONT>";
            $this->get('session')->getFlashBag()->add('flash-notice', 'L\'uid n\'est pas valide');
            $this->getRequest()->getSession()->set('_saved', 0);
        }
        else
        {
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
        }
        
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
        // Dans le cas d'un gestionnaire
        if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
            // Recup des groupes dont l'utilisateur est admin
            $arDataAdminLogin = $this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
            for($i=0;$i<$arDataAdminLogin["count"];$i++)
            {
                $tab_cn_admin_login[$i] = $arDataAdminLogin[$i]["cn"][0];
            }
        }
        
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
        $nb_public=0;
        foreach($tab as $dn)
        {
            // on ne récupère que les groupes publics
            if (!strstr($dn, "ou=private"))
            {
                $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
                $nb_public++;
            }
        }
        $user->setMemberof($tab_cn); 
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($tab_cn,true). "</PRE></FONT></FONT>";
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>nb groupes publics=</B>=><FONT color =green><PRE>" . $nb_public. "</PRE></FONT></FONT>";
        
        // User initial pour détecter les modifications
        $userini = new User();
        $userini->setUid($uid);
        $userini->setDisplayname($arData[0]['displayname'][0]);
        $userini->setMail($arData[0]['mail'][0]);
        $userini->setSn($arData[0]['sn'][0]);
        $userini->setTel($arData[0]['telephonenumber'][0]);
        $userini->setMemberof($tab_cn); 
        
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
        $membershipsini = new ArrayCollection();
        // Gestion des groupes publics dont l'utilisateur est membre
        for($i=0; $i<$nb_public;$i++)
        {
            $membership = new Membership();
            $membership->setGroupname($tab_cn[$i]);
            $membership->setMemberof(TRUE);
            $membership->setDroits('Aucun');
            
            // Idem pour membershipini
            $membershipini = new Membership();
            $membershipini->setGroupname($tab_cn[$i]);
            $membershipini->setMemberof(TRUE);
            $membershipini->setDroits('Aucun');
            // on teste si l'utilisateur est aussi admin du groupe
            for ($j=0; $j<$arDataAdmin["count"];$j++)
            {
                if ($arDataAdmin[$j]["cn"][0] == $tab_cn[$i])
                {
                    $membership->setAdminof(TRUE);
                    $membershipini->setAdminof(TRUE);
                    $flagMember[$j] = TRUE;
                    break;
                }
                else
                {
                    $membership->setAdminof(FALSE);
                    $membershipini->setAdminof(FALSE);
                }
            }
            
            // Gestion droits pour un gestionnaire
            if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                foreach($tab_cn_admin_login as $cn)
                {
                    if ($cn==$tab_cn[$i])
                    {
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tab_cn_search=</B>=><FONT color =green><PRE>" . $groupname . "</PRE></FONT></FONT>"; 
                        $membership->setDroits('Modifier');
                        $membershipini->setDroits('Modifier');
                        break;
                    }
                }
            }
            
            // Gestion droits pour un admin de l'appli
            if (true === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
                $membership->setDroits('Modifier');
                $membershipini->setDroits('Modifier');  
            }
            
            $memberships[$i] = $membership;
            $membershipsini[$i] = $membershipini;
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
                $membership->setDroits('Aucun');
                
                // Idem pour membershipini
                $membershipini = new Membership();
                $membershipini->setGroupname($arDataAdmin[$i]["cn"][0]);
                $membershipini->setMemberof(FALSE);
                $membershipini->setAdminof(TRUE);
                $membershipini->setDroits('Aucun');
                
                // Gestion droits pour un gestionnaire
                if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                    foreach($tab_cn_admin_login as $cn)
                    {
                        if ($cn==$arDataAdmin[$i]["cn"][0])
                        {
                            $membership->setDroits('Modifier');
                            $membershipini->setDroits('Modifier');
                            break;
                        }
                    }
                }
                // Gestion droits pour un admin de l'appli
                if (true === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
                    $membership->setDroits('Modifier');
                    $membershipini->setDroits('Modifier');
                }
            
                $memberships[] = $membership;
                $membershipsini[] = $membershipini;
            }
            
        }
        
        $user->setMemberships($memberships);
        $userini->setMemberships($membershipsini);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Tab groupes</B>=><FONT color =green><PRE>" . print_r($user, true) . "</PRE></FONT></FONT>";
                                
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $uid)),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $userupdate = new User();
            $userupdate = $editForm->getData();
            
            // Log modif de groupe
            openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
            $adm = $request->getSession()->get('login');
                
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $userupdate->getMemberships();
            for ($i=0; $i<sizeof($m_update); $i++)
            //foreach($m_update as $memb)
            {
                $memb = $m_update[$i];
                $dn_group = "cn=" . $memb->getGroupname() . ", ou=groups, dc=univ-amu, dc=fr";
                $c = $memb->getGroupname();
                
                if ($memb->getDroits()=='Modifier') 
                {
                    // Si changement, on modifie dans le ldap
                    if ($memb->getMemberof() != $membershipsini[$i]->getMemberof())
                    {
                        if ($memb->getMemberof())
                        {
                            $r = $this->getLdap()->addMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "add_member by $adm : group : $c, user : $uid");
                            }
                            else
                            {
                                // Log erreur
                                syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $c, user : $uid");
                            }              
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                        else
                        {
                            $r = $this->getLdap()->delMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "del_member by $adm : group : $c, user : $uid");
                            }
                            else 
                            {
                                syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $c, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                    }
                    // Traitement des admins
                    // Idem si changement des droits
                    if ($memb->getAdminof() != $membershipsini[$i]->getAdminof())
                    {
                        if ($memb->getAdminof())
                        {
                            $r = $this->getLdap()->addAdminGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "add_admin by $adm : group : $c, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $c, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                        else
                        {
                            $r = $this->getLdap()->delAdminGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "del_admin by $adm : group : $c, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $c, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                    }
                }
            }
            // Ferme fichier log
            closelog();
            
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
        $arDataUser=$this->getLdap()->arDatasFilter("uid=".$uid, array('displayname', 'memberof', 'uid'));
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>arDataUser</B>=><FONT color =green><PRE>" . print_r($arDataUser) . "</PRE></FONT></FONT>";
        // Test de la validité de l'uid
        if ($arDataUser[0]['uid'][0] == '')
        {
            $this->get('session')->getFlashBag()->add('flash-notice', 'L\'uid n\'est pas valide');
            $this->getRequest()->getSession()->set('_saved', 0);
            return $this->redirect($this->generateUrl('user_search', array('opt' => 'add', 'cn'=>$cn)));
        }
        else
        {
            $user->setDisplayname($arDataUser[0]['displayname'][0]);
            $tab = array_splice($arDataUser[0]['memberof'], 1);
            // Tableau des groupes de l'utilisateur
            $tab_cn = array();
            foreach($tab as $dn)
            {
                $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
            }
            // Recherche des admins du groupe dans le LDAP
            $arAdmins = $this->getLdap()->getAdminsGroup($cn);

            // User initial pour détecter les modifications
            $userini = new User();
            $userini->setUid($uid);
            $userini->setDisplayname($arDataUser[0]['displayname'][0]);

            // on remplit l'objet user avec les droits courants sur le groupe
            $memberships = new ArrayCollection();
            $membership = new Membership();
            $membership->setGroupname($cn);

            // Idem pour userini
            $membershipsini = new ArrayCollection();
            $membershipini = new Membership();
            $membershipini->setGroupname($cn);

            // Droits "membre"
            foreach($tab_cn as $cn_g)
            {
                if ($cn==$cn_g)
                {
                    $membership->setMemberof(TRUE);
                    $membershipini->setMemberof(TRUE);
                    break;
                }
                else 
                {
                    $membership->setMemberof(FALSE);
                    $membershipini->setMemberof(FALSE);
                }
            }
            // Droits "admin"
            for ($j=0; $j<$arAdmins[0]["amugroupadmin"]["count"]; $j++) 
            {       
                // récupération des uid des admin du groupe
                $uid_admins = preg_replace("/(uid=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
                if ($uid == $uid_admins)
                {
                    $membership->setAdminof(TRUE);
                    $membershipini->setAdminof(TRUE);
                    break;
                }
                else 
                {
                    $membership->setAdminof(FALSE);
                    $membershipini->setAdminof(FALSE);
                }
            }
            $memberships[0] = $membership;
            $user->setMemberships($memberships);       

            // Idem userini
            $membershipsini[0] = $membershipini;
            $userini->setMemberships($membershipsini);       

            $editForm = $this->createForm(new UserEditType(), $user, array(
                'action' => $this->generateUrl('user_add', array('uid'=> $uid, 'cn' => $cn)),
                'method' => 'POST',
            ));
            $editForm->handleRequest($request);

            if ($editForm->isValid()) {
                $userupdate = new User();
                $userupdate = $editForm->getData();

                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
                // Log modif de groupe
                openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
                $adm = $request->getSession()->get('login');

                $m_update = new ArrayCollection();      
                $m_update = $userupdate->getMemberships();

                //foreach($m_update as $memb)
                for ($i=0; $i<sizeof($m_update); $i++)
                {
                    $memb = $m_update[$i];

                    $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";

                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid</B>=><FONT color =green><PRE>" . print_r($m_update, true) . "</PRE></FONT></FONT>";
                    // Traitement des membres
                    // Si modification des droits, on modifie dans le ldap
                    if ($memb->getMemberof() != $membershipsini[$i]->getMemberof())
                    {
                        if ($memb->getMemberof())
                        {
                            $r = $this->getLdap()->addMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                        else
                        {
                            $r = $this->getLdap()->delMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "del_member by $adm : group : $cn, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                    }

                    // Traitement des admins
                    // Si modification des droits, on modifie dans le ldap
                    if ($memb->getAdminof() != $membershipsini[$i]->getAdminof())
                    {
                        if ($memb->getAdminof())
                        {
                            $r = $this->getLdap()->addAdminGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "add_admin by $adm : group : $cn, user : $uid");
                            }
                            else 
                            {
                                syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                        else
                        {
                            $r = $this->getLdap()->delAdminGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "del_admin by $adm : group : $cn, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                    }

                }
                // Ferme fichier log
                closelog();

                $this->get('session')->getFlashBag()->add('flash-notice', 'Les droits ont bien été ajoutés');
                $this->getRequest()->getSession()->set('_saved',1);
            }
            else { 
                $this->getRequest()->getSession()->set('_saved',0);
            }
        }
        
        return array(
            'user'      => $user,
            'cn' => $cn,
            'form'   => $editForm->createView(),
        );
    }
     
    /**
     * Ajoute les droits d'un utilisateur à un groupe.
     *
     * @Route("/addprivate/{uid}/{cn}",name="user_add_private")
     * @Template("AmuCliGrouperBundle:User:rechercheuseraddprivate.html.twig")
     */
    public function addprivateAction(Request $request, $uid='', $cn='') {
        // Récupération utilisateur
        $user = new User();
        $user->setUid($uid);
        $arDataUser=$this->getLdap()->arDatasFilter("uid=".$uid, array('displayname', 'memberof', 'uid')); 
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>arDataUser</B>=><FONT color =green><PRE>" . print_r($arDataUser) . "</PRE></FONT></FONT>";
        // Test de la validité de l'uid
        if ($arDataUser[0]['uid'][0] == '')
        {
            $this->get('session')->getFlashBag()->add('flash-notice', 'L\'uid n\'est pas valide');
            $this->getRequest()->getSession()->set('_saved', 0);
            return $this->redirect($this->generateUrl('user_search', array('opt' => 'addprivate', 'cn'=>$cn)));
        }
        else
        {
            $user->setDisplayname($arDataUser[0]['displayname'][0]);
            $tab = array_splice($arDataUser[0]['memberof'], 1);
            // Tableau des groupes de l'utilisateur
            $tab_cn = array();
            foreach($tab as $dn)
            {
                $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
            }
            

            // User initial pour détecter les modifications
            $userini = new User();
            $userini->setUid($uid);
            $userini->setDisplayname($arDataUser[0]['displayname'][0]);

            // on remplit l'objet user avec les droits courants sur le groupe
            $memberships = new ArrayCollection();
            $membership = new Membership();
            $membership->setGroupname($cn);

            // Idem pour userini
            $membershipsini = new ArrayCollection();
            $membershipini = new Membership();
            $membershipini->setGroupname($cn);

            // Droits "membre"
            foreach($tab_cn as $cn_g)
            {
                if ($cn==$cn_g)
                {
                    $membership->setMemberof(TRUE);
                    $membershipini->setMemberof(TRUE);
                    break;
                }
                else 
                {
                    $membership->setMemberof(FALSE);
                    $membershipini->setMemberof(FALSE);
                }
            }
            
            $memberships[0] = $membership;
            $user->setMemberships($memberships);       

            // Idem userini
            $membershipsini[0] = $membershipini;
            $userini->setMemberships($membershipsini);       

            $editForm = $this->createForm(new PrivateUserEditType(), $user, array(
                'action' => $this->generateUrl('user_add_private', array('uid'=> $uid, 'cn' => $cn)),
                'method' => 'POST',
            ));
            $editForm->handleRequest($request);

            if ($editForm->isValid()) {
                $userupdate = new User();
                $userupdate = $editForm->getData();

                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
                // Log modif de groupe
                openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
                $adm = $request->getSession()->get('login');

                $m_update = new ArrayCollection();      
                $m_update = $userupdate->getMemberships();

                //foreach($m_update as $memb)
                for ($i=0; $i<sizeof($m_update); $i++)
                {
                    $memb = $m_update[$i];

                    $dn_group = "cn=" . $cn . ", ou=private, ou=groups, dc=univ-amu, dc=fr";

                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>dn_group</B>=><FONT color =green><PRE>" . print_r($dn_group, true) . "</PRE></FONT></FONT>";
                    // Traitement des membres
                    // Si modification des droits, on modifie dans le ldap
                    if ($memb->getMemberof() != $membershipsini[$i]->getMemberof())
                    {
                        if ($memb->getMemberof())
                        {
                            $r = $this->getLdap()->addMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                        else
                        {
                            $r = $this->getLdap()->delMemberGroup($dn_group, array($uid));
                            if ($r)
                            {
                                // Log modif
                                syslog(LOG_INFO, "del_member by $adm : group : $cn, user : $uid");
                            }
                            else
                            {
                                syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $cn, user : $uid");
                            }
                            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                        }
                    }

                }
                // Ferme fichier log
                closelog();

                $this->get('session')->getFlashBag()->add('flash-notice', 'Les droits ont bien été ajoutés');
                $this->getRequest()->getSession()->set('_saved',1);
                
                // Récupération du nouveau groupe modifié pour affichage
                $newgroup = new Group();
                $newgroup->setCn($cn);
                $newmembers = new ArrayCollection();

                // Recherche des membres dans le LDAP
                $narUsers = $this->getLdap()->getMembersGroup($cn.",ou=private");
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos users</B>=><FONT color =green><PRE>" . print_r($arUsers, true) . "</PRE></FONT></FONT>";

                // Affichage des membres  
                for ($i=0; $i<$narUsers["count"]; $i++) {                     
                    $newmembers[$i] = new Member();
                    $newmembers[$i]->setUid($narUsers[$i]["uid"][0]);
                    $newmembers[$i]->setDisplayname($narUsers[$i]["displayname"][0]);
                    $newmembers[$i]->setMail($narUsers[$i]["mail"][0]);
                    $newmembers[$i]->setTel($narUsers[$i]["telephonenumber"][0]);
                    $newmembers[$i]->setMember(TRUE); 
                    $newmembers[$i]->setAdmin(FALSE);
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";

                }
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos membres</B>=><FONT color =green><PRE>" . print_r($members, true) . "</PRE></FONT></FONT>";

                $newgroup ->setMembers($newmembers);

                $editForm = $this->createForm(new PrivateGroupEditType(), $newgroup);
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($newgroup, true) . "</PRE></FONT></FONT>";

                return $this->render('AmuCliGrouperBundle:Group:privateupdate.html.twig', array('group' => $newgroup, 'nb_membres' => $narUsers["count"], 'form' => $editForm->createView()));
                
            }
            else { 
                $this->getRequest()->getSession()->set('_saved',0);
            }
        }
        
        return array(
            'user'      => $user,
            'cn' => $cn,
            'form'   => $editForm->createView(),
        );
    }
    
    /**
     * Voir les appartenances et droits d'un utilisateur.
     *
     * @Route("/voir/{uid}", name="voir_user")
     * @Template()
     * // AMU Modif's
     */
    public function voirAction(Request $request, $uid)
    {
        $membersof = array();
        $adminsof = array();
        
        // Recherche des groupes dont l'utilisateur est membre 
        $arData=$this->getLdap()->arDatasFilter("member=uid=".$uid.",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        for ($i=0; $i<$arData["count"]; $i++)         
        {
            // on ne récupere que les groupes publics
            if (!strstr($arData[$i]["dn"], "ou=private"))
            {
            $gr = new Group();
            $gr->setCn($arData[$i]["cn"][0]);
            $gr->setDescription($arData[$i]["description"][0]);
            $gr->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $membersof[] = $gr;
            }
        }
                
        // Récupération des groupes dont l'utilisateur est admin
        $arDataAdmin=$this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$uid.",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        for ($i=0; $i<$arDataAdmin["count"]; $i++)         
        {
            $gr_adm = new Group();
            $gr_adm->setCn($arDataAdmin[$i]["cn"][0]);
            $gr_adm->setDescription($arDataAdmin[$i]["description"][0]);
            $gr_adm->setAmugroupfilter($arDataAdmin[$i]["amugroupfilter"][0]);
            $adminsof[] = $gr_adm;
        }
        
        
        return array('uid' => $uid,
                    'nb_grp_membres' => $arData["count"], 
                    'grp_membres' => $membersof,
                    'nb_grp_admins' => $arDataAdmin["count"],
                    'grp_admins' => $adminsof);
    }
    
    /**
    * Recherche de personnes
    *
    * @Route("/search/{opt}/{cn}/{liste}",name="user_search")
    * @Template()
    */
    public function searchAction(Request $request, $opt='search', $cn='', $liste='') {
        $usersearch = new User();
        $users = array();
        $u = new User();
        $u->setExacte(true);
        $form = $this->createForm(new UserSearchType(), $u);               
        $form->handleRequest($request);
                
        if ($form->isValid()) {
            $usersearch = $form->getData();
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>opt</B>=><FONT color =green><PRE>" . $opt . "</PRE></FONT></FONT>";
            if (($opt=='add')||($opt=='addprivate'))
            {
                if ($opt=='add') {
                    return $this->redirect($this->generateUrl('user_add', array('uid'=>$usersearch->getUid(), 'cn'=>$cn)));
                }
                if ($opt=='addprivate') {
                    return $this->redirect($this->generateUrl('user_add_private', array('uid'=>$usersearch->getUid(), 'cn'=>$cn)));
                }
            }
            else
            {
                // On teste si on a qqchose dans le champ uid
               if ($usersearch->getUid()=='')
                {
                    // si on a rien, on teste le nom
                    // On teste si on fait une recherche exacte ou non
                    if ($usersearch->getExacte()){
                        $arData=$this->getLdap()->arDatasFilter("sn=".$usersearch->getSn(), array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
                    }
                    else {
                        $arData=$this->getLdap()->arDatasFilter("sn=*".$usersearch->getSn()."*", array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
                    }
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user arData</B>=><FONT color =green><PRE>" . print_r($arData, true) . "</PRE></FONT></FONT>";
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user count</B>=><FONT color =green><PRE>" . $arData['count'] . "</PRE></FONT></FONT>";
                    for($i=0;$i<$arData['count'];$i++)
                    {
                        $data = $arData[$i];
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user data</B>=><FONT color =green><PRE>" . print_r($data, true) . "</PRE></FONT></FONT>";
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>uid</B>=><FONT color =green><PRE>" . $data['uid'][0] . "</PRE></FONT></FONT>";
                        
                        $user = new User();
                        $user->setUid($data['uid'][0]);
                        $user->setDisplayname($data['displayname'][0]);
                        $user->setMail($data['mail'][0]);
                        $user->setSn($data['sn'][0]);
                        $user->setTel($data['telephonenumber'][0]);
                       
                        $users[] = $user; 
                        
                        //$this->getRequest()->getSession()->set('_saved',1);
                        //return array('users' => $users);
                        
                    }
                    // Gestion des droits
                    $droits = 'Aucun';
                    // Droits DOSI seulement en visu
                    if (true === $this->get('security.context')->isGranted('ROLE_DOSI')) 
                    {
                        $droits = 'Voir';
                    }
                    if ((true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) || (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))) 
                    {
                        $droits = 'Modifier';
                    }
                    
                    // Mise en session des résultats de la recherche
                    $this->container->get('request')->getSession()->set('users', $users);
                
                    return $this->render('AmuCliGrouperBundle:User:rechercheuser.html.twig',array('users' => $users, 'opt' => $opt, 'droits' => $droits, 'cn' => $cn));
                }
                else
                {
                    // Recherche des utilisateurs dans le LDAP
                    $arData=$this->getLdap()->arDatasFilter("uid=".$usersearch->getUid(), array('uid', 'sn','displayname', 'mail', 'telephonenumber', 'memberof'));
                
                    // Test de la validité de l'uid
                    if ($arData[0]['uid'][0] == '')
                    {
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>opt</B>=><FONT color =green><PRE>" . $opt . "</PRE></FONT></FONT>";
                        $this->get('session')->getFlashBag()->add('flash-notice', 'L\'uid n\'est pas valide');
                        $this->getRequest()->getSession()->set('_saved', 0);
                        
                    }
                    else
                    {
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
                            $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
                        }
                        $user->setMemberof($tab_cn); 

                        $users[] = $user; 
                        //$this->getRequest()->getSession()->set('_saved',1);
                        //return array('users' => $users);

                        // Gestion des droits
                        $droits = 'Aucun';
                        // Droits DOSI seulement en visu
                        if (true === $this->get('security.context')->isGranted('ROLE_DOSI')) {
                            $droits = 'Voir';
                        }
                        if ((true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) || (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))) {
                            $droits = 'Modifier';
                        }
                        // Mise en session des résultats de la recherche
                        $this->container->get('request')->getSession()->set('users', $users); 
                    
                        return $this->render('AmuCliGrouperBundle:User:rechercheuser.html.twig',array('users' => $users, 'opt' => $opt, 'droits' => $droits, 'cn' => $cn));
                    }
                }   
            }       
        }
        
        
        //$this->getRequest()->getSession()->set('_saved',0);
        //return array('form' => $form->createView());
        
        return $this->render('AmuCliGrouperBundle:User:usersearch.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'cn' => $cn, 'liste' => $liste));
        
    }

    /**
    * Affichage d'une liste d'utilisateurs en session
    *
    * @Route("/afficheliste/{opt}/{cn}",name="user_display")
    */
    public function displayAction(Request $request, $opt='search', $cn='') {
    
        // Récupération des utilisateurs mis en session
        $users = $this->container->get('request')->getSession()->get('users');

        // Gestion des droits
        $droits = 'Aucun';
        // Droits DOSI seulement en visu
        if (true === $this->get('security.context')->isGranted('ROLE_DOSI')) {
            $droits = 'Voir';
        }
        if ((true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) || (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))) {
            $droits = 'Modifier';
        }
                        
        return $this->render('AmuCliGrouperBundle:User:rechercheuser.html.twig',array('users' => $users, 'opt' => $opt, 'droits' => $droits, 'cn' => $cn));
            
      
        
    }
    
    /**
    * Formulaire pour l'ajout d'utilisateurs en masse
    *
    * @Route("/multiple/{opt}/{cn}/{liste}",name="user_multiple")
    */
    public function multipleAction(Request $request, $opt='search', $cn='', $liste='') {
        
        $formlist = $this->createForm(new UserMultipleType());
        $formlist->handleRequest($request);
        
        // Tableau des utilisateurs
        $users = array();
        
        // Tableau des uids
        $tabUids = array();
        // Tableau des erreurs
        $tabErreurs = array();
        // Tableau des membres appartenant déjà au groupe
        $tabMemb = array();
        
        
        if ($formlist->isValid())
            {
                $tabErreurs = array();
                $tabUids = array();
                $tab = $formlist->getData();
                $liste = $tab['liste'];
                // Récupérer un tableau avec une ligne par uid/mail
                $tabLignes = explode("\n", $liste);
                
                // Log ajout sur le groupe
                openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
                $adm = $request->getSession()->get('login');
                
                // Boucle sur la liste
                foreach($tabLignes as $l)
                {
                    // on élimine les caractères superflus
                    $ligne = trim($l);
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>TabLignes</B>=><FONT color =green><PRE>" . $ligne . "</PRE></FONT></FONT>";
                    // test mail ou login
                    if (stripos($ligne , "@")>0)
                    {
                        // C'est un mail
                        $r = $this->getLdap()->getUidFromMail($ligne);
                        
                        // Si pb connexion ldap
                        if ($r==false)
                        {
                            // Affichage erreur
                            $this->get('session')->getFlashBag()->add('flash-error', "Erreur LDAP lors de la récupération du mail $ligne");
                                            
                            // Log erreur
                            syslog(LOG_ERR, "LDAP ERREUR : get_uid_from_mail by $adm : $ligne");
                        }
                        else
                        { 
                            // Si le mail n'est pas valide, on le note
                            if ($r['count']==0)
                            {
                                $tabErreurs[] = $ligne;
                            }
                            else
                            {
                                // Récupération des appartenances de l'utilisateur à ajouter
                                $arGroups = array_splice($r[0]['memberof'], 1);
                                $stop=0;
                                foreach($arGroups as $dn)
                                {
                                    $c = preg_replace("/(cn=)(([A-Za-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
                                    if ($c==$cn)
                                    {
                                        // l'utilisateur est déjà membre de ce groupe
                                        $stop = 1;
                                        break;
                                    }
                                }
                                
                                // Si l'utilisateur n'est pas membre du groupe
                                if ($stop==0)
                                {
                                    // On ajoute l'uid à la liste des utilisateurs à ajouter
                                    $tabUids[] = $r[0]['uid'][0]; 
                                
                                    // Remplissage "user"
                                    $user = new User();
                                    $user->setUid($r[0]['uid'][0]);
                                    $user->setDisplayname($r[0]['displayname'][0]);
                                    $user->setSn($r[0]['sn'][0]);
                                    $user->setMail($r[0]['mail'][0]);
                                    $user->setTel($r[0]['telephonenumber'][0]);
                                
                                    $users[] = $user;
                                }
                                else
                                {
                                    // L'utilisateur est déjà membre, on le note
                                    $tabMemb[] = $r[0]['uid'][0];
                                
                                }
                            }
                        }
                    }
                    else
                    {
                        // C'est un login
                        $r = $this->getLdap()->TestUid($ligne);
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Retour LDAP</B>=><FONT color =green><PRE>" . print_r($r, true) . "</PRE></FONT></FONT>";
                        
                        // Si pb connexion ldap
                        if ($r==false)
                        {
                            // Affichage erreur
                            $this->get('session')->getFlashBag()->add('flash-error', "Erreur LDAP lors de l'uid $ligne");
                                            
                            // Log erreur
                            syslog(LOG_ERR, "LDAP ERREUR : get_uid_from_mail by $adm : $ligne");
                        }
                        else
                        {
                            // Si l'uid n'est pas valide, on le note
                            if ($r['count']==0)
                            {
                                $tabErreurs[] = $ligne; 
                            }
                            else
                            {
                                // Récupération des appartenances de l'utilisateur à ajouter
                                $arGroups = array_splice($r[0]['memberof'], 1);
                                $stop=0;
                                foreach($arGroups as $dn)
                                {
                                    $c = preg_replace("/(cn=)(([A-Za-z0-9:_-]{1,}))(,ou=.*)/", "$3", $dn);
                                    if ($c==$cn)
                                    {
                                        // l'utilisateur est déjà membre de ce groupe
                                        $stop = 1;
                                        break;
                                    }
                                }
                                
                                // Si l'utilisateur n'est pas membre du groupe
                                if ($stop==0)
                                {
                                    // On ajoute l'uid à la liste des utilisateurs à ajouter
                                    $tabUids[] = $r[0]['uid'][0]; 
                                
                                    // Remplissage "user"
                                    $user = new User();
                                    $user->setUid($r[0]['uid'][0]);
                                    $user->setDisplayname($r[0]['displayname'][0]);
                                    $user->setSn($r[0]['sn'][0]);
                                    $user->setMail($r[0]['mail'][0]);
                                    $user->setTel($r[0]['telephonenumber'][0]);
                                
                                    $users[] = $user; 
                                }
                                else
                                {
                                    // L'utilisateur est déjà membre, on le note
                                    $tabMemb[] = $r[0]['uid'][0];
                                
                                }
                            }
                        }
                    }
                }
/*                echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tabUids</B>=><FONT color =green><PRE>" . print_r($tabUids, true) . "</PRE></FONT></FONT>";
                echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>users</B>=><FONT color =green><PRE>" . print_r($users, true) . "</PRE></FONT></FONT>";
                echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tabErreurs</B>=><FONT color =green><PRE>" . print_r($tabErreurs, true) . "</PRE></FONT></FONT>";
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($tabUids, true) . "</PRE></FONT></FONT>";
*/                
                // Ajout de la liste valide au groupe dans le LDAP
                $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";
                $b = $this->getLdap()->addMemberGroup($dn_group, $tabUids);
                
                if ($b)
                {
                    // Log modif
                    foreach($tabUids as $u)
                    {
                        syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $u");
                    }
                }
                else
                {
                    // Log erreur
                    syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $c, multiple user");
                }     
                
                return $this->render('AmuCliGrouperBundle:User:checkmultiple.html.twig', array('cn' => $cn, 'tab_err' => $tabErreurs, 'users' => $users, 'tab_memb' => $tabMemb, 'nb_users' => sizeof($users), 'nb_err' => sizeof($tabErreurs), 'nb_memb' => sizeof($tabMemb)));
            }
                            
        return $this->render('AmuCliGrouperBundle:User:multipleuser.html.twig', array('form' => $formlist->createView(), 'opt' => $opt, 'cn' => $cn, 'liste' => $liste));
            
      
        
    }
    
    
  
}
