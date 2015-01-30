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
use Amu\CliGrouperBundle\Form\UserEditType;
use Amu\CliGrouperBundle\Entity\Membership;

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
        
        //$arData=$this->getLdap()->arDatasFilter("member=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        $arData=$this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arData). "</PRE></FONT></FONT>";
        $groups = new ArrayCollection();
        
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $groups[$i]->setAmugroupadmin($uid);
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
            
            if (($opt=='search')||($opt=='del'))
            {
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
            else {
                if ($opt=='add')
                {
                    return $this->redirect($this->generateUrl('group_add', array('cn_search'=>$groupsearch->getCn(), 'uid'=>$uid)));
                }
            }
                       
        }
        return $this->render('AmuCliGrouperBundle:Group:groupesearch.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'uid' => $uid));
        
    }
    
    /**
     * Recherche de groupes pour la suppression
     *
     * @Route("/searchdel",name="group_search_del")
     * @Template()
     */
    public function searchdelAction(Request $request) {
        return $this->redirect($this->generateUrl('group_search', array('opt' => 'del', 'uid'=>'')));
    }
    
    /**
     * Recherche de groupes
     *
     * @Route("/add/{cn_search}/{uid}",name="group_add")
     * @Template("AmuCliGrouperBundle:Group:recherchegroupeadd.html.twig")
     */
    public function addAction(Request $request, $cn_search='', $uid='') {
        // Récupération utilisateur
        $user = new User();
        $user->setUid($uid);
        $arDataUser=$this->getLdap()->arDatasFilter("uid=".$uid, array('displayname', 'memberof'));
        $user->setDisplayname($arDataUser[0]['displayname'][0]);
        $tab = array_splice($arDataUser[0]['memberof'], 1);
        $tab_cn = array();
        foreach($tab as $dn)
        {
            $tab_cn[] = preg_replace("/(cn=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
        }
        // Récupération des groupes dont l'utilisateur est admin
        $arDataAdmin=$this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$uid.",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        $tab_cn_admin = array();
        for($i=0;$i<$arDataAdmin["count"];$i++)
        {
            $tab_cn_admin[$i] = $arDataAdmin[$i]["cn"][0];
        }
        
        // Recherche des groupes dans le LDAP
        $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=*" . $cn_search . "*))",array("cn","description","amuGroupFilter"));             
        for ($i=0; $i<$arData["count"]; $i++) {
            $tab_cn_search[$i] = $arData[$i]["cn"][0];
        }
                           
        // on remplit l'objet user avec les groupes retournés par la recherche LDAP
        $memberships = new ArrayCollection();
        foreach($tab_cn_search as $groupname)
        {
            $membership = new Membership();
            $membership->setGroupname($groupname);
            foreach($tab_cn as $cn)
            {
                if ($cn==$groupname)
                {
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tab_cn_search=</B>=><FONT color =green><PRE>" . $groupname . "</PRE></FONT></FONT>"; 
                    $membership->setMemberof(TRUE);
                    break;
                }
                else 
                {
                    $membership->setMemberof(FALSE);
                 }
            }
            foreach($tab_cn_admin as $cn)
            {
                if ($cn==$groupname)
                {
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tab_cn_search=</B>=><FONT color =green><PRE>" . $groupname . "</PRE></FONT></FONT>"; 
                    $membership->setAdminof(TRUE);
                    break;
                }
                else 
                {
                    $membership->setAdminof(FALSE);
                 }
            }
            
            $memberships[] = $membership;
            
        }
        $user->setMemberships($memberships);       
        
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('group_add', array('cn_search'=> $cn_search, 'uid' => $uid)),
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
                $dn_group = "cn=" . $memb->getGroupname() . ", ou=groups, dc=univ-amu, dc=fr";
                if ($memb->getMemberof())
                {
                    // Ajout utilisateur dans groupe
                    $this->getLdap()->addMemberGroup($dn_group, array($uid));
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    // Suppression utilisateur du groupe
                    $this->getLdap()->delMemberGroup($dn_group, array($uid));
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
            'cn_search' => $cn_search,
            'form'   => $editForm->createView(),
        );
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
        $admins = array();
        
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
        
        // Recherche des administrateurs du groupe
        $arAdmins = $this->getLdap()->getAdminsGroup($cn);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos admins</B>=><FONT color =green><PRE>" . print_r($arAdmins, true) . "</PRE></FONT></FONT>";
        
        for ($i=0; $i<$arAdmins[0]["amugroupadmin"]["count"]; $i++) {  
            $uid = preg_replace("/(uid=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$i]);
            $result = $this->getLdap()->arUserInfos($uid, array("uid", "sn", "displayname", "mail", "telephonenumber"));
            $admins[$i] = new User();
            $admins[$i]->setUid($result["uid"]);
            $admins[$i]->setSn($result["sn"]);
            $admins[$i]->setDisplayname($result["displayname"]);
            $admins[$i]->setMail($result["mail"]);
            $admins[$i]->setTel($result["telephonenumber"]);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Admins</B>=><FONT color =green><PRE>" . print_r($admins[$i], true) . "</PRE></FONT></FONT>";
        }
        
        return array('cn' => $cn,
                    'nb_membres' => $arUsers["count"], 
                    'users' => $users,
                    'nb_admins' => $arAdmins[0]["amugroupadmin"]["count"],
                    'admins' => $admins);
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
        // Recherche des admins dans le LDAP
        $arAdmins = $this->getLdap()->getAdminsGroup($cn);
        $flagMembers = array();
        for($i=0;$i<$arAdmins[0]["amugroupadmin"]["count"];$i++)
        {
            $flagMembers[] = FALSE;
        }
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos users</B>=><FONT color =green><PRE>" . print_r($arUsers, true) . "</PRE></FONT></FONT>";
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos admins</B>=><FONT color =green><PRE>" . print_r($arAdmins, true) . "</PRE></FONT></FONT>";
        // Affichage des membres  
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $members[$i] = new Member();
            $members[$i]->setUid($arUsers[$i]["uid"][0]);
            $members[$i]->setDisplayname($arUsers[$i]["displayname"][0]);
            $members[$i]->setMail($arUsers[$i]["mail"][0]);
            $members[$i]->setTel($arUsers[$i]["telephonenumber"][0]);
            $members[$i]->setMember(TRUE);
            $members[$i]->setAdmin(FALSE);
           
            // on teste si le membre est aussi admin
            for ($j=0; $j<$arAdmins[0]["amugroupadmin"]["count"]; $j++)
            {
                $uid = preg_replace("/(uid=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
                if ($uid==$arUsers[$i]["uid"][0])
                {
                    $members[$i]->setAdmin(TRUE);
                    $flagMembers[$j] = TRUE;
                    break;
                }
            }
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos membres</B>=><FONT color =green><PRE>" . print_r($members, true) . "</PRE></FONT></FONT>";
        
        // Affichage des admins qui ne sont pas membres
        for ($j=0; $j<$arAdmins[0]["amugroupadmin"]["count"]; $j++) {       
            if ($flagMembers[$j]==FALSE)
            {
                // si l'admin n'est pas membre du groupe, il faut aller récupérer ses infos dans le LDAP
                $uid = preg_replace("/(uid=)(([a-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
                $result = $this->getLdap()->arUserInfos($uid, array("uid", "sn", "displayname", "mail", "telephonenumber"));
                
                $memb = new Member();
                $memb->setUid($result["uid"]);
                $memb->setDisplayname($result["displayname"]);
                $memb->setMail($result["mail"]);
                $memb->setTel($result["telephonenumber"]);
                $memb->setMember(FALSE);
                $memb->setAdmin(TRUE);
                $members[] = $memb;
            }
        }
        
        $group ->setMembers($members);
        
        $editForm = $this->createForm(new GroupEditType(), $group);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $groupupdate = new Group();
            $groupupdate = $editForm->getData();
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid</B>=><FONT color =green><PRE>" . print_r($groupupdate, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $groupupdate->getMembers();
            foreach($m_update as $memb)
            {
                $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid</B>=><FONT color =green><PRE>" . print_r($m_update, true) . "</PRE></FONT></FONT>";
                // Traitement des membres
                if ($memb->getMember())
                {
                    $this->getLdap()->addMemberGroup($dn_group, array($memb->getUid()));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    
                    $this->getLdap()->delMemberGroup($dn_group, array($memb->getUid()));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                
                // Traitement des admins
                if ($memb->getAdmin())
                {
                    $this->getLdap()->addAdminGroup($dn_group, array($memb->getUid()));
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                }
                else
                {
                    $this->getLdap()->delAdminGroup($dn_group, array($memb->getUid()));
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
            'group'      => $group,
            'nb_membres' => $arUsers["count"],
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
                $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été créé');
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
            $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été supprimé');
            return $this->render('AmuCliGrouperBundle:Group:suppressiongroupe.html.twig',array('cn' => $cn));
        }
        else 
            return $this->render('AmuCliGrouperBundle:Group:groupesearch.html.twig', array('form' => $form->createView()));
    }
    
   
      
}