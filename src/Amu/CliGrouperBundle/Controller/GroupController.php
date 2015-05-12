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
    return $this->get('CliGrouper.ldap');
  }


    /**
     * Affiche tous les groupes
     *
     * @Route("/tous_les_groupes",name="tous_les_groupes")
     * @Template()
     */
    public function touslesgroupesAction() {
        
        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;
          
        $arData=$this->getLdap()->arDatasFilter("(objectClass=groupofNames)",array("cn","description","amuGroupFilter"));
        // echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Tous les groupes : </B>=><FONT color =green><PRE>" . $arData["count"]. "</PRE></FONT></FONT>";
         
        $groups = new ArrayCollection();
        for ($i=0; $i<$arData["count"]; $i++) {
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>cn=</B>=><FONT color =green><PRE>" . $arData[$i]["cn"][0] . "</PRE></FONT></FONT>";
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            $groups[$i]->setAmugroupadmin("");
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
            
            // Mise en forme pour la présentation "dossier" avec javascript
            $arEtages = preg_split('/[:]+/', $arData[$i]["cn"][0]);
            $NbEtages = count($arEtages);
            $groups[$i]->setEtages($arEtages);
            $groups[$i]->setNbetages($NbEtages);
            $groups[$i]->setLastnbetages($NbEtagesPrec);
                        
            // on marque la différence entre les dossiers d'affichage des groupes N et N-1
            $lastopen = 0;
            for ($j=0;$j<$NbEtagesPrec;$j++)
            {
                if ($arEtages[$j]!=$arEtagesPrec[$j])
                {
                    
                    $lastopen = $j ;
                    $groups[$i]->setLastopen($lastopen);
                    break;
                }
            }
            
            if (($NbEtagesPrec>=1) && ($lastopen == 0))
                $groups[$i]->setLastopen($NbEtagesPrec-1);
            
            // on garde le nom du groupe précédent dans la liste
            $arEtagesPrec = $groups[$i]->getEtages();
            $NbEtagesPrec = $groups[$i]->getNbetages();
            
        }

        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos brut</B>=><FONT color =green><PRE>" . print_r($groups, true) . "</PRE></FONT></FONT>";
        
        return array('groups' => $groups);
    }

 
    /**
     * Affiche tous les groupes dont l'utilisateur est administrateur
     *
     * @Route("/mes_groupes",name="mes_groupes")
     * @Template()
     */
    public function mesgroupesAction(Request $request) {
        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;
        
        //$arData=$this->getLdap()->arDatasFilter("member=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        $arData=$this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arData). "</PRE></FONT></FONT>";
        $groups = new ArrayCollection();
        // echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>nb groupes=</B>=><FONT color =green><PRE>" . $arData["count"] . "</PRE></FONT></FONT>";
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
            // Mise en forme pour la présentation "dossier" avec javascript
            $arEtages = preg_split('/[:]+/', $arData[$i]["cn"][0]);
            $NbEtages = count($arEtages);
            $groups[$i]->setEtages($arEtages);
            $groups[$i]->setNbetages($NbEtages);
            $groups[$i]->setLastnbetages($NbEtagesPrec);
                        
            // on marque la différence entre les dossiers d'affichage des groupes N et N-1
            $lastopen = 0;
            for ($j=0;$j<$NbEtagesPrec;$j++)
            {
                if ($arEtages[$j]!=$arEtagesPrec[$j])
                {
                    
                    $lastopen = $j ;
                    $groups[$i]->setLastopen($lastopen);
                    break;
                }
            }
            
            if (($NbEtagesPrec>=1) && ($lastopen == 0))
                $groups[$i]->setLastopen($NbEtagesPrec-1);
            
            // on garde le nom du groupe précédent dans la liste
            $arEtagesPrec = $groups[$i]->getEtages();
            $NbEtagesPrec = $groups[$i]->getNbetages();
        }
        
        return array('groups' => $groups);
    }
    
    /**
     * Affiche tous les groupes dont l'utilisateur est membre
     *
     * @Route("/mes_appartenances",name="mes_appartenances")
     * @Template()
     */
    public function mesappartenancesAction(Request $request) {
        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;
        
        $arData=$this->getLdap()->arDatasFilter("member=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
        
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>memberof=</B>=><FONT color =green><PRE>" . print_r($arData). "</PRE></FONT></FONT>";
        $groups = new ArrayCollection();
        
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i]["cn"][0]);
            $groups[$i]->setDescription($arData[$i]["description"][0]);
            $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
            
            // Mise en forme pour la présentation "dossier" avec javascript
            $arEtages = preg_split('/[:]+/', $arData[$i]["cn"][0]);
            $NbEtages = count($arEtages);
            $groups[$i]->setEtages($arEtages);
            $groups[$i]->setNbetages($NbEtages);
            $groups[$i]->setLastnbetages($NbEtagesPrec);
                        
            // on marque la différence entre les dossiers d'affichage des groupes N et N-1
            $lastopen = 0;
            for ($j=0;$j<$NbEtagesPrec;$j++)
            {
                if ($arEtages[$j]!=$arEtagesPrec[$j])
                {
                    
                    $lastopen = $j ;
                    $groups[$i]->setLastopen($lastopen);
                    break;
                }
            }
            
            if (($NbEtagesPrec>=1) && ($lastopen == 0))
                $groups[$i]->setLastopen($NbEtagesPrec-1);
            
            // on garde le nom du groupe précédent dans la liste
            $arEtagesPrec = $groups[$i]->getEtages();
            $NbEtagesPrec = $groups[$i]->getNbetages();
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
        
        $form = $this->createForm(new GroupSearchType(), new Group(), array('attr' => array('novalidate' => 'novalidate')));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupsearch = $form->getData(); 
            
            if (($opt=='search')||($opt=='mod')||($opt=='del'))
            {
                // Recherche des groupes dans le LDAP
                $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=*" . $groupsearch->getCn() . "*))",array("cn","description","amuGroupFilter"));
                
                // si c'est un gestionnaire, on ne renvoie que les groupes dont il est admin
                $tab_cn_admin = array();
                if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                    // Recup des groupes dont l'utilisateur est admin
                    $arDataAdmin = $this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
                    for($i=0;$i<$arDataAdmin["count"];$i++)
                    {
                        $tab_cn_admin[$i] = $arDataAdmin[$i]["cn"][0];
                    }
                }
               
                for ($i=0; $i<$arData["count"]; $i++) {
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>cn=</B>=><FONT color =green><PRE>" . $arData[$i]["cn"][0] . "</PRE></FONT></FONT>";
                    $groups[$i] = new Group();
                    $groups[$i]->setCn($arData[$i]["cn"][0]);
                    $groups[$i]->setDescription($arData[$i]["description"][0]);
                    $groups[$i]->setAmugroupfilter($arData[$i]["amugroupfilter"][0]);
                    $groups[$i]->setDroits('Aucun');
                    
                    // Droits DOSI seulement en visu
                    if (true === $this->get('security.context')->isGranted('ROLE_DOSI')) {
                        $groups[$i]->setDroits('Voir');
                    }
                    
                    // Droits gestionnaire seulement sur les groupes dont il est admin
                    if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                        foreach ($tab_cn_admin as $cn_admin)
                        {    
                            if ($cn_admin==$arData[$i]["cn"][0])
                            {
                                $groups[$i]->setDroits('Modifier');
                                break;
                            }
                        }
                    }
                    
                    // Droits Admin
                    if (true === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
                        $groups[$i]->setDroits('Modifier');
                    }
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
     * Recherche de groupes pour la modification
     *
     * @Route("/searchmod",name="group_search_modify")
     * @Template()
     */
    public function searchmodAction(Request $request) {
        return $this->redirect($this->generateUrl('group_search', array('opt' => 'mod', 'uid'=>'')));
    }
    
    /**
     * Ajout de personnes dans un groupe
     *
     * @Route("/add/{cn_search}/{uid}",name="group_add")
     * @Template("AmuCliGrouperBundle:Group:recherchegroupeadd.html.twig")
     */
    public function addAction(Request $request, $cn_search='', $uid='') {
        // Dans le cas d'un gestionnaire
        if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
            // Recup des groupes dont l'utilisateur courant (logué) est admin
            $arDataAdminLogin = $this->getLdap()->arDatasFilter("amuGroupAdmin=uid=".$request->getSession()->get('login').",ou=people,dc=univ-amu,dc=fr",array("cn", "description", "amugroupfilter"));
            for($i=0;$i<$arDataAdminLogin["count"];$i++)
            {
                $tab_cn_admin_login[$i] = $arDataAdminLogin[$i]["cn"][0];
            }
        }
        
        // Récupération utilisateur et début d'initialisation de l'objet
        $user = new User();
        $user->setUid($uid);
        $arDataUser=$this->getLdap()->arDatasFilter("uid=".$uid, array('displayname', 'memberof'));
        $user->setDisplayname($arDataUser[0]['displayname'][0]);
        
        // Utilisateur initial pour détecter les modifications
        $userini = new User();
        $userini->setUid($uid);
        $userini->setDisplayname($arDataUser[0]['displayname'][0]);
        
        // Mise en forme du tableau contenant les cn des groupes dont l'utilisateur recherché est membre
        $tab = array_splice($arDataUser[0]['memberof'], 1);
        $tab_cn = array();
        foreach($tab as $dn)
        {
            $tab_cn[] = preg_replace("/(cn=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
        }
        // Récupération des groupes dont l'utilisateur recherché est admin
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
        // Idem pour l'objet userini
        $membershipsini = new ArrayCollection();
        foreach($tab_cn_search as $groupname)
        {
            $membership = new Membership();
            $membership->setGroupname($groupname);
            $membership->setDroits('Aucun');
            $membershipini = new Membership();
            $membershipini->setGroupname($groupname);
            $membershipini->setDroits('Aucun');
            // Remplissage des droits "membre"
            foreach($tab_cn as $cn)
            {
                if ($cn==$groupname)
                {
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tab_cn_search=</B>=><FONT color =green><PRE>" . $groupname . "</PRE></FONT></FONT>"; 
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
            //Remplissage des droits admin
            foreach($tab_cn_admin as $cn)
            {
                if ($cn==$groupname)
                {
                    //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>tab_cn_search=</B>=><FONT color =green><PRE>" . $groupname . "</PRE></FONT></FONT>"; 
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
                        
            // Gestion droits pour un gestionnaire
            if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                foreach($tab_cn_admin_login as $cn)
                {
                    if ($cn==$groupname)
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
            
            $memberships[] = $membership;
            $membershipsini[] = $membershipini;
        }
        $user->setMemberships($memberships);      
        $userini->setMemberships($membershipsini);
        
        // Formulaire
        $editForm = $this->createForm(new UserEditType(), $user, array(
            'action' => $this->generateUrl('group_add', array('cn_search'=> $cn_search, 'uid' => $uid)),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            // Récupération des données du formulaire
            $userupdate = new User();
            $userupdate = $editForm->getData();
            
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userupdate, true) . "</PRE></FONT></FONT>";
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos user</B>=><FONT color =green><PRE>" . print_r($userini, true) . "</PRE></FONT></FONT>";
            
            $m_update = new ArrayCollection();      
            $m_update = $userupdate->getMemberships();
            
            // Log Mise à jour des membres du groupe
            openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
            $adm = $request->getSession()->get('login');
            
            // Pour chaque appartenance
            //foreach($m_update as $memb)
            for ($i=0; $i<sizeof($m_update); $i++)
            {
                $memb = $m_update[$i];
                $dn_group = "cn=" . $memb->getGroupname() . ", ou=groups, dc=univ-amu, dc=fr";
                $gr = $memb->getGroupname();
                
                // Traitement des membres  
                // Si il y a changement pour le membre, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getMemberof() != $membershipsini[$i]->getMemberof())
                {
                    if ($memb->getMemberof())
                    {
                        // Ajout utilisateur dans groupe
                        $r = $this->getLdap()->addMemberGroup($dn_group, array($uid));
                        if ($r==true)
                        {
                            // Log modif
                            syslog(LOG_INFO, "add_member by $adm : group : $gr, user : $uid ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $gr, user : $uid");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                    else
                    {
                        // Suppression utilisateur du groupe
                        $r = $this->getLdap()->delMemberGroup($dn_group, array($uid));
                        if ($r)
                            syslog(LOG_INFO, "del_member by $adm : group : $gr, user : $uid ");
                        else 
                            syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $gr, user : $uid");
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupes</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                }
                // Traitement des admins
                // Si il y a changement pour admin, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getAdminof() != $membershipsini[$i]->getAdminof())
                {
                    if ($memb->getAdminof())
                    {
                        $r = $this->getLdap()->addAdminGroup($dn_group, array($uid));
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "add_admin by $adm : group : $gr, user : $uid ");
                        }
                        else 
                        {
                            syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $gr, user : $uid ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                    else
                    {
                        $r = $this->getLdap()->delAdminGroup($dn_group, array($uid)); 
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "del_admin by $adm : group : $gr, user : $uid ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $gr, user : $uid ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                }
            }
            // Ferme fichier de log
            closelog();
            
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
     * @Route("/voir/{cn}/{mail}", name="voir_groupe")
     * @Template()
     * // AMU Modif's
     */
    public function voirAction(Request $request, $cn, $mail)
    {
        $users = array();
        $admins = array(); 
        
        // Récup du amugroupfilter 
        $arData=$this->getLdap()->arDatasFilter("(&(objectClass=groupofNames)(cn=" . $cn . "))",array("cn","description","amuGroupFilter"));
        $amugroupfilter = $arData[0]["amugroupfilter"][0];
        
        // Recherche des membres dans le LDAP
        $arUsers = $this->getLdap()->getMembersGroup($cn);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos users</B>=><FONT color =green><PRE>" . print_r($arUsers, true) . "</PRE></FONT></FONT>";
                 
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $users[$i] = new User();
            $users[$i]->setUid($arUsers[$i]["uid"][0]);
            $users[$i]->setSn($arUsers[$i]["sn"][0]);
            $users[$i]->setDisplayname($arUsers[$i]["displayname"][0]);
            if ($mail=='true')
                $users[$i]->setMail($arUsers[$i]["mail"][0]);
            
            $users[$i]->setTel($arUsers[$i]["telephonenumber"][0]);
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($groups[$i], true) . "</PRE></FONT></FONT>";
        }
        
        // Recherche des administrateurs du groupe
        $arAdmins = $this->getLdap()->getAdminsGroup($cn);
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos admins</B>=><FONT color =green><PRE>" . print_r($arAdmins, true) . "</PRE></FONT></FONT>";
        
        for ($i=0; $i<$arAdmins[0]["amugroupadmin"]["count"]; $i++) {  
            $uid = preg_replace("/(uid=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$i]);
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
                    'amugroupfilter' => $amugroupfilter,
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
        
        // Groupe initial pour détecter les modifications
        $groupini = new Group();
        $groupini->setCn($cn);
        $membersini = new ArrayCollection();
        
               
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
           
            // Idem pour groupini
            $membersini[$i] = new Member();
            $membersini[$i]->setUid($arUsers[$i]["uid"][0]);
            $membersini[$i]->setDisplayname($arUsers[$i]["displayname"][0]);
            $membersini[$i]->setMail($arUsers[$i]["mail"][0]);
            $membersini[$i]->setTel($arUsers[$i]["telephonenumber"][0]);
            $membersini[$i]->setMember(TRUE);
            $membersini[$i]->setAdmin(FALSE);
            
            // on teste si le membre est aussi admin
            for ($j=0; $j<$arAdmins[0]["amugroupadmin"]["count"]; $j++)
            {
                $uid = preg_replace("/(uid=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
                if ($uid==$arUsers[$i]["uid"][0])
                {
                    $members[$i]->setAdmin(TRUE);
                    $membersini[$i]->setAdmin(TRUE);
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
                $uid = preg_replace("/(uid=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0]["amugroupadmin"][$j]);
                $result = $this->getLdap()->arUserInfos($uid, array("uid", "sn", "displayname", "mail", "telephonenumber"));
                
                $memb = new Member();
                $memb->setUid($result["uid"]);
                $memb->setDisplayname($result["displayname"]);
                $memb->setMail($result["mail"]);
                $memb->setTel($result["telephonenumber"]);
                $memb->setMember(FALSE);
                $memb->setAdmin(TRUE);
                $members[] = $memb;
                
                // Idem pour groupini
                $membini = new Member();
                $membini->setUid($result["uid"]);
                $membini->setDisplayname($result["displayname"]);
                $membini->setMail($result["mail"]);
                $membini->setTel($result["telephonenumber"]);
                $membini->setMember(FALSE);
                $membini->setAdmin(TRUE);
                $membersini[] = $membini;
            }
        }
        
        $group ->setMembers($members);
        $groupini ->setMembers($membersini);
                      
        $editForm = $this->createForm(new GroupEditType(), $group);
        
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $groupupdate = new Group();
            $groupupdate = $editForm->getData();
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid : groupupdate</B>=><FONT color =green><PRE>" . print_r($groupupdate, true) . "</PRE></FONT></FONT>";
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid : groupini</B>=><FONT color =green><PRE>" . print_r($groupini, true) . "</PRE></FONT></FONT>";
            
            // Log Mise à jour des membres du groupe
            openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
            $adm = $request->getSession()->get('login');
            
            $m_update = new ArrayCollection();      
            $m_update = $groupupdate->getMembers();
            //foreach($m_update as $memb)
            for ($i=0; $i<sizeof($m_update); $i++)
            {
                $memb = $m_update[$i];
                $membi = $membersini[$i];
                $dn_group = "cn=" . $cn . ", ou=groups, dc=univ-amu, dc=fr";
                
                $u = $memb->getUid();
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Form valid</B>=><FONT color =green><PRE>" . print_r($m_update, true) . "</PRE></FONT></FONT>";
                // Traitement des membres
                // Si il y a changement pour le membre, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getMember() != $membi->getMember())
                {
                    if ($memb->getMember())
                    {
                        $r = $this->getLdap()->addMemberGroup($dn_group, array($u));
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $u ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $u ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                    else
                    {
                        $r = $this->getLdap()->delMemberGroup($dn_group, array($u));
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "del_member by $adm : group : $cn, user : $u ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $cn, user : $u ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression membre</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                }
                
                // Traitement des admins
                // Idem : si changement, on répercute dans le ldap
                if ($memb->getAdmin() != $membi->getAdmin())
                {
                    if ($memb->getAdmin())
                    {
                        $r = $this->getLdap()->addAdminGroup($dn_group, array($u));
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "add_admin by $adm : group : $cn, user : $u ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $cn, user : $u ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Ajout admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                    else
                    {
                        $r = $this->getLdap()->delAdminGroup($dn_group, array($u));
                        if ($r)
                        {
                            // Log modif
                            syslog(LOG_INFO, "del_admin by $adm : group : $cn, user : $u ");
                        }
                        else
                        {
                            syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $cn, user : $u ");
                        }
                        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Suppression admin</B>=><FONT color =green><PRE>" . print_r($memb, true) . "</PRE></FONT></FONT>";
                    }
                }
            }
            // Ferme fichier de log
            closelog();
            
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
            
            // Log création de groupe
            openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
            $adm = $request->getSession()->get('login');
                
            // Création du groupe dans le LDAP
            $infogroup = $group->infosGroupeLdap();
            $b = $this->getLdap()->createGroupeLdap($infogroup['dn'], $infogroup['infos']);
            if ($b==true)
            {
                //Le groupe a bien été créé
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
                // affichage groupe créé
                $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été créé');
                $groups[0] = $group;
                $cn = $group->getCn();
                
                // Log création OK
                syslog(LOG_INFO, "create_group by $adm : group : $cn");
               
                return $this->render('AmuCliGrouperBundle:Group:creationgroupe.html.twig',array('groups' => $groups));
            }
            else 
            {
                // affichage erreur
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la création du groupe');
                $groups[0] = $group;
                $cn = $group->getCn();
                
                // Log erreur
                syslog(LOG_ERR, "LDAP ERREUR : create_group by $adm : group : $cn");
                
                // Affichage page 
                return $this->render('AmuCliGrouperBundle:Group:groupe.html.twig', array('form' => $form->createView()));
            }
            
            // Ferme le fichier de log
            closelog();
             
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
        // Log suppression de groupe
        openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
        $adm = $request->getSession()->get('login');
        
        //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>delete groupe ldap </B>=><FONT color =green><PRE>" . $cn . "</PRE></FONT></FONT>";
        $b = $this->getLdap()->deleteGroupeLdap($cn);
        if ($b==true)
        {
            //Le groupe a bien été supprimé
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
            
            // affichage groupe supprimé
            $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été supprimé');
            
            // Log
            syslog(LOG_INFO, "delete_group by $adm : group : $cn");
            
            return $this->render('AmuCliGrouperBundle:Group:suppressiongroupe.html.twig',array('cn' => $cn));
        }
        else 
        {
            // Log erreur
            syslog(LOG_ERR, "LDAP ERROR : delete_group by $adm : group : $cn");
            // affichage erreur
            $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la suppression du groupe');
            // Retour page
            return $this->render('AmuCliGrouperBundle:Group:groupesearch.html.twig', array('form' => $form->createView()));
        }
        
        // Ferme fichier de log
        closelog();
    }
    
    /**
     * Modifier un groupe.
     *
     * @Route("/modify/{cn}/{desc}/{filt}", name="group_modify")
     * @Template()
     * // AMU Modif's
     */
    public function modifyAction(Request $request, $cn, $desc, $filt)
    {
        $group = new Group();
        $groups = array();
        
        $dn = "cn=".$cn.", ou=groups, dc=univ-amu, dc=fr";
        
        // Pré-remplir le formulaire avec les valeurs actuelles du groupe
        $group->setCn($cn);
        $group->setDescription($desc);
        if ($filt=="no")
            $group->setAmugroupfilter("");
        else
            $group->setAmugroupfilter($filt);
        
        $form = $this->createForm(new GroupCreateType(), $group);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupmod = new Group();
            $groupmod = $form->getData();
            
            // Log modif de groupe
            openlog("groupie", LOG_PID | LOG_PERROR, LOG_LOCAL0);
            $adm = $request->getSession()->get('login');
            
            // Cas particulier de la suppression amugroupfilter
            if (($filt != "no") && ($groupmod->getAmugroupfilter() == "")) {
                // Suppression de l'attribut
                $b = $this->getLdap()->delAmuGroupFilter($dn, $filt);
                // Log Erreur LDAP
                syslog(LOG_ERR, "LDAP ERROR : modif_group by $adm : group : $cn, delAmuGroupFilter");
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la modification du groupe');
                return $this->render('AmuCliGrouperBundle:Group:groupem.html.twig', array('form' => $form->createView(), 'group' => $group));
            }
                
            // Modification du groupe dans le LDAP
            $infogroup = $groupmod->infosGroupeLdap();
            //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>Infos groupe</B>=><FONT color =green><PRE>" . print_r($infogroup, true) . "</PRE></FONT></FONT>";
            $b = $this->getLdap()->modifyGroupeLdap($dn, $infogroup['infos']);
            // echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>filt</B>=><FONT color =green><PRE>" . print_r($groupmod) . "</PRE></FONT></FONT>";
            if ($b==true)
            {
                //Le groupe a bien été modifié
                //echo "<b> DEBUT DEBUG INFOS <br>" . "<br><B>retour create groupe ldap</B>=><FONT color =green><PRE>" . $b . "</PRE></FONT></FONT>";
                // Log modif de groupe OK
                syslog(LOG_INFO, "modif_group by $adm : group : $cn");
                
                 // affichage groupe créé
                $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été modifié');
                $groups[0] = $group;
                return $this->render('AmuCliGrouperBundle:Group:modifgroupe.html.twig',array('groups' => $groups));
            }
            else 
            {
                // Log Erreur LDAP
                syslog(LOG_ERR, "LDAP ERROR : modif_group by $adm : group : $cn");
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la modification du groupe');
                return $this->render('AmuCliGrouperBundle:Group:groupem.html.twig', array('form' => $form->createView(), 'group' => $group));
            }
            
            // Ferme fichier log
            closelog();
        }
        return $this->render('AmuCliGrouperBundle:Group:groupem.html.twig', array('form' => $form->createView(), 'group' => $group));
    }
      
}
