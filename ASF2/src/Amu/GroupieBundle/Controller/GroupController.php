<?php

namespace Amu\GroupieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Amu\GroupieBundle\Entity\Group;
use Amu\GroupieBundle\Form\GroupCreateType;
use Amu\GroupieBundle\Form\GroupModifType;
use Amu\GroupieBundle\Form\GroupSearchType;
use Amu\GroupieBundle\Entity\User;
use Amu\GroupieBundle\Entity\Member;
use Amu\GroupieBundle\Form\MemberType;
use Amu\GroupieBundle\Form\GroupEditType;
use Amu\GroupieBundle\Form\UserEditType;
use Amu\GroupieBundle\Entity\Membership;
use Amu\GroupieBundle\Form\PrivateGroupCreateType;
use Amu\GroupieBundle\Form\PrivateGroupEditType;


use Doctrine\Common\Collections\ArrayCollection;

/**
 * group controller
 * @Route("/group")
 * 
 */
class GroupController extends Controller {

    protected $config_logs;
    protected $config_users;
    protected $config_groups;
    protected $config_private;
    protected $base;
    protected $ou;
    protected $private_ou;
    protected $objectclasses;

    protected function init_config()
    {
        if (!isset($this->config_logs))
            $this->config_logs = $this->container->getParameter('amu.groupie.logs');
        if (!isset($this->config_users))
            $this->config_users = $this->container->getParameter('amu.groupie.users');
        if (!isset($this->config_groups))
            $this->config_groups = $this->container->getParameter('amu.groupie.groups');
        if (!isset($this->config_private))
            $this->config_private = $this->container->getParameter('amu.groupie.private');
        if (!isset($this->base)) {
            $profil_name = $this->container->getParameter('amu.ldap.default_profil');
            $profils = $this->container->getParameter('amu.ldap.profils');
            $profil = $profils[$profil_name];
            $this->base = $profil['base_dn'];
        }

        if (isset($this->ou) === false) {
            // OU des groupes institutionnels.
            $this->ou = sprintf('%s,%s', $this->config_groups['group_branch'], $this->base);
        }

        if (isset($this->private_ou) === false) {
            // OU des groupes privés.
            $this->private_ou = sprintf('%s,%s', $this->config_private['private_branch'], $this->base);
        }

        if (isset($this->objectclasses) === false) {
            // ObjectClasses LDAP nécessaires pour créer une nouvelle entrée Group.
            $this->objectclasses = explode(',', $profil['objectclass_group']);
        }
    }

/**********************************************************************************************************************************************************************************************************************************/
/* METHODES PUBLIQUES DU CONTROLLER                                                                                                                                                                                               */
/**********************************************************************************************************************************************************************************************************************************/

    /**
     * Affiche tous les groupes
     *
     * @Route("/all",name="all_groups")
     * @Template()
    */
    public function allgroupsAction() {
        $this->init_config();

        // Accès autorisé pour la DOSI
        $flag= "nok";
        if (true === $this->get('security.context')->isGranted('ROLE_DOSI'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        // Récupération des groupes dont l'utilisateur courant est administrateur (on ne récupère que les groupes publics)
        $arData = $ldapfonctions->recherche("(objectClass=".$this->config_groups['object_class'].")", array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
         
        $groups = new ArrayCollection();
        for ($i=0; $i<$arData["count"]; $i++) {
            // on ne garde que les groupes publics
            if (!strstr($arData[$i]["dn"], $this->config_private['private_branch'])) {
                $groups[$i] = new Group();
                $groups[$i]->setCn($arData[$i][$this->config_groups['cn']][0]);
                $groups[$i]->setDescription($arData[$i][$this->config_groups['desc']][0]);
                if (isset($arData[$i][$this->config_groups['groupfilter']])) {
                    $groups[$i]->setAmugroupfilter($arData[$i][$this->config_groups['groupfilter']][0]);
                }
                else {
                    $groups[$i]->setAmugroupfilter("");
                }
                $groups[$i]->setAmugroupadmin("");

                // Mise en forme pour la présentation "dossier" avec javascript
                $arEtages = preg_split('/[:]+/', $arData[$i][$this->config_groups['cn']][0]);
                $NbEtages = count($arEtages);
                $groups[$i]->setEtages($arEtages);
                $groups[$i]->setNbetages($NbEtages);
                $groups[$i]->setLastnbetages($NbEtagesPrec);

                // on marque la différence entre les dossiers d'affichage des groupes N et N-1
                $lastopen = 0;
                for ($j=0;$j<$NbEtagesPrec;$j++) {
                    if ($arEtages[$j]!=$arEtagesPrec[$j]) {
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
        }
        
        return array('groups' => $groups);
    }

    /**
     * Affiche tous les groupes privés
     *
     * @Route("/all_private",name="all_private_groups")
     * @Template()
     */
    public function allprivateAction() {
        $this->init_config();

        // Accès autorisé pour la DOSI
        $flag= "nok";
        if (true === $this->get('security.context')->isGranted('ROLE_DOSI'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Récupération tous les groupes du LDAP
        $arData = $ldapfonctions->recherche("(objectClass=".$this->config_groups['object_class'].")", array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
         
        // Initialisation tableau des entités Group
        $groups = new ArrayCollection();
        for ($i=0; $i<$arData["count"]; $i++) {
            // on ne garde que les groupes privés
            if (strstr($arData[$i]["dn"], $this->config_private['private_branch'])) {
                $groups[$i] = new Group();
                $groups[$i]->setCn($arData[$i][$this->config_groups['cn']][0]);
                $groups[$i]->setDescription($arData[$i][$this->config_groups['desc']][0]);
            }
        }

        return array('groups' => $groups);
    }
 
    /**
     * Affiche tous les groupes dont l'utilisateur est administrateur
     *
     * @Route("/my_groups",name="my_groups")
     * @Template()
     */
    public function mygroupsAction(Request $request) {
        $this->init_config();
        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;

        // Accès autorisé pour les gestionnaires
        $flag= "nok";
        if ((true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE'))||(true === $this->get('security.context')->isGranted('ROLE_ADMIN')))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        // Récupération des groupes dont l'utilisateur courant est administrateur (on ne récupère que les groupes publics)
        $arData = $ldapfonctions->recherche($this->config_groups['groupadmin']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base, array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);

        // Initialisation tableau des entités Group
        $groups = new ArrayCollection();
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i][$this->config_groups['cn']][0]);
            $groups[$i]->setDescription($arData[$i][$this->config_groups['desc']][0]);
            if (isset($arData[$i][$this->config_groups['groupfilter']])) {
                $groups[$i]->setAmugroupfilter($arData[$i][$this->config_groups['groupfilter']][0]);
            }
            else {
                $groups[$i]->setAmugroupfilter("");    
            }
            
            // Mise en forme pour la présentation "dossier" avec javascript
            $arEtages = preg_split('/[:]+/', $arData[$i][$this->config_groups['cn']][0]);
            $NbEtages = count($arEtages);
            $groups[$i]->setEtages($arEtages);
            $groups[$i]->setNbetages($NbEtages);
            $groups[$i]->setLastnbetages($NbEtagesPrec);
                        
            // on marque la différence entre les dossiers d'affichage des groupes N et N-1
            $lastopen = 0;
            for ($j=0;$j<$NbEtagesPrec;$j++) {
                if ($arEtages[$j]!=$arEtagesPrec[$j]) {
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
     * @Route("/memberships",name="memberships")
     * @Template()
     */
    public function membershipsAction(Request $request) {
        $this->init_config();
        // Variables pour l'affichage "dossier" avec javascript 
        $arEtages = array();
        $NbEtages = 0;
        $arEtagesPrec = array();
        $NbEtagesPrec = 0;

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        // Récupération des groupes dont l'utilisateur courant est administrateur (on ne récupère que les groupes publics)
        $result = $ldapfonctions->recherche($this->config_groups['member']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base, array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
        
        // Initialisation du tableau d'entités Group
        $groups = new ArrayCollection();
        for ($i=0; $i<$result["count"]; $i++) {
            // on ne garde que les groupes publics
            if (!strstr($result[$i]["dn"], $this->config_private['private_branch'])) {
                $groups[$i] = new Group();
                $groups[$i]->setCn($result[$i][$this->config_groups['cn']][0]);
                $groups[$i]->setDescription($result[$i][$this->config_groups['desc']][0]);
                if (isset($result[$i][$this->config_groups['groupfilter']]))
                    $groups[$i]->setAmugroupfilter($result[$i][$this->config_groups['groupfilter']][0]);

                // Mise en forme pour la présentation "dossier" avec javascript
                $arEtages = preg_split('/[:]+/', $result[$i][$this->config_groups['cn']][0]);
                $NbEtages = count($arEtages);
                $groups[$i]->setEtages($arEtages);
                $groups[$i]->setNbetages($NbEtages);
                $groups[$i]->setLastnbetages($NbEtagesPrec);

                // on marque la différence entre les dossiers d'affichage des groupes N et N-1
                $lastopen = 0;
                for ($j=0;$j<$NbEtagesPrec;$j++) {
                    if ($arEtages[$j]!=$arEtagesPrec[$j]) {
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
        }
        
        return array('groups' => $groups);
    }
    
    /**
     * Affiche tous les groupes privés dont l'utilisateur est membre
     *
     * @Route("/private_memberships",name="private_memberships")
     * @Template()
     */
    public function privatemembershipsAction(Request $request) {
        $this->init_config();
        // Récupération des groupes privés dont l'utilisateur courant est membre
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Recherche des groupes privés de l'utilisateur
        $result = $ldapfonctions->recherche($this->config_groups['member']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base, array($this->config_groups['cn'], $this->config_groups['desc']), $this->config_groups['cn']);
        
        // Initialisation du tableau d'entités Group
        $groups = new ArrayCollection();
        $nb_groups=0;
        for ($i=0; $i<$result["count"]; $i++) {
            // on ne garde que les groupes privés
            if (strstr($result[$i]["dn"], $this->config_private['private_branch'])) {
                $groups[$i] = new Group();
                $groups[$i]->setCn($result[$i][$this->config_groups['cn']][0]);
                $groups[$i]->setDescription($result[$i][$this->config_groups['desc']][0]);
                $nb_groups++;
            }
        }
        
        return array('groups' => $groups, 'nb_groups' => $nb_groups);
    }
        
    /**
     * Recherche de groupes
     *
     * @Route("/search/{opt}/{uid}",name="group_search")
     * @Template()
     */
    public function searchAction(Request $request, $opt='search', $uid='') {
        $this->init_config();
        // Déclaration variables
        $groupsearch = new Group();
        $groups = array();

        // Création du formulaire de recherche de groupe
        $form = $this->createForm(new GroupSearchType(),
            new Group(),
            array('action' => $this->generateUrl('group_search', array('opt'=>$opt, 'uid'=>$uid)),
                  'method' => 'GET'));
        $form->handleRequest($request);

        if ($form->isValid()) {
            // Récupération des données du formulaire
            $groupsearch = $form->getData();

            // On récupère le service ldapfonctions
            $ldapfonctions = $this->container->get('groupie.ldapfonctions');
            $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

            // Suivant l'option d'où on vient
            if (($opt=='search')||($opt=='mod')||($opt=='del')){
                // si on a sélectionné un proposition de la liste d'autocomplétion
                if ($groupsearch->getFlag() == '1') {
                    // On teste si on est sur le message "... Résultat partiel ..."
                    if ($groupsearch->getCn() == "... Résultat partiel ...") {
                        $this->get('session')->getFlashBag()->add('flash-notice', 'Le nom du groupe est invalide');
                        return $this->redirect($this->generateUrl('group_search', array('opt'=>$opt, 'uid'=>$uid)));
                    }
                    // Recherche exacte des groupes dans le LDAP
                    $arData=$ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=" . $groupsearch->getCn() . "))", array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
                }
                else {
                    // Recherche avec * des groupes dans le LDAP directement 
                    $arData=$ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=*" . $groupsearch->getCn() . "*))",array($this->config_groups['cn'],$this->config_groups['desc'],$this->config_groups['groupfilter']), $this->config_groups['cn']);
                }
                
                // si c'est un gestionnaire, on ne renvoie que les groupes dont il est admin
                $tab_cn_admin = array();
                if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                    // Recup des groupes dont l'utilisateur est admin
                    $arDataAdmin = $ldapfonctions->recherche($this->config_groups['groupadmin']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base,array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
                    for($i=0;$i<$arDataAdmin["count"];$i++)
                        $tab_cn_admin[$i] = $arDataAdmin[$i][$this->config_groups['cn']][0];
                }

                // Compteur du nombre de résultats donnés par la recherche
                $nb = 0;
                for ($i=0; $i<$arData["count"]; $i++) {
                    // on ne garde que les groupes publics
                    if (!strstr($arData[$i]["dn"], $this->config_private['private_branch'])) {
                        $groups[$i] = new Group();
                        $groups[$i]->setCn($arData[$i][$this->config_groups['cn']][0]);
                        $groups[$i]->setDescription($arData[$i][$this->config_groups['desc']][0]);
                        if (isset($arData[$i][$this->config_groups['groupfilter']]))
                            $groups[$i]->setAmugroupfilter($arData[$i][$this->config_groups['groupfilter']][0]);
                        else
                            $groups[$i]->setAmugroupfilter("");
                        $groups[$i]->setDroits('Aucun');

                        // Droits DOSI seulement en visu
                        if (true === $this->get('security.context')->isGranted('ROLE_DOSI')) {
                            $groups[$i]->setDroits('Voir');
                        }

                        // Droits gestionnaire seulement sur les groupes dont il est admin
                        if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                            foreach ($tab_cn_admin as $cn_admin) {    
                                if ($cn_admin==$arData[$i][$this->config_groups['cn']][0]) {
                                    $groups[$i]->setDroits('Modifier');
                                    break;
                                }
                            }
                        }

                        // Droits Admin
                        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
                            $groups[$i]->setDroits('Modifier');
                        }
                        $nb++;
                    }
                }
            
                // Mise en session des résultats de la recherche
                $this->container->get('request')->getSession()->set('groups', $groups);

                // Si on a un seul résultat de recherche, affichage direct du groupe concerné en fonction des droits
                if ($opt=='search') {
                    if ($nb==1) {
                        if ($groups[0]->getDroits()=='Modifier') {
                            return $this->redirect($this->generateUrl('group_update', array('cn'=>$groups[0]->getCn(), 'liste' => 'recherchegroupe')));
                        }

                        if ($groups[0]->getDroits()=='Voir') {
                           return $this->redirect($this->generateUrl('see_group', array('cn'=>$groups[0]->getCn(), 'mail' => true, 'liste' => 'recherchegroupe')));
                        }
                    }
                }
  
                return $this->render('AmuGroupieBundle:Group:searchres.html.twig',array('groups' => $groups, 'opt' => $opt, 'uid' => $uid));
            }
            else {
                if ($opt=='add') {
                    // Renvoi vers le fonction group_add
                    return $this->redirect($this->generateUrl('group_add', array('cn_search'=>$groupsearch->getCn(), 'uid'=>$uid, 'flag_cn'=> $groupsearch->getFlag())));
                }
            }
        }
        
        return $this->render('AmuGroupieBundle:Group:search.html.twig', array('form' => $form->createView(), 'opt' => $opt, 'uid' => $uid));
        
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
     * @Route("/add/{cn_search}/{uid}/{flag_cn}",name="group_add")
     * @Template("AmuGroupieBundle:Group:searchadd.html.twig")
     */
    public function addAction(Request $request, $cn_search='', $uid='', $flag_cn=0) {
        $this->init_config();

        // Accès autorisé pour les gestionnaires et les admins
        $flag= "nok";
        if ((true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) || (true === $this->get('security.context')->isGranted('ROLE_ADMIN')))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Dans le cas d'un gestionnaire
        if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
            // Recup des groupes dont l'utilisateur courant (logué) est admin
            $arDataAdminLogin = $ldapfonctions->recherche($this->config_groups['groupadmin']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base,array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
            for($i=0;$i<$arDataAdminLogin["count"];$i++) 
                $tab_cn_admin_login[$i] = $arDataAdminLogin[$i][$this->config_groups['cn']][0];
        }

        // Récupération utilisateur et début d'initialisation de l'objet
        $user = new User();
        $user->setUid($uid);
        // Récup infos utilisateur dans le LDAP
        $arDataUser=$ldapfonctions->recherche($this->config_users['uid']."=".$uid, array($this->config_users['displayname'], $this->config_groups['memberof']), $this->config_users['uid']);
        $user->setDisplayname($arDataUser[0][$this->config_users['displayname']][0]);
        
        // Utilisateur initial pour détecter les modifications
        $userini = new User();
        $userini->setUid($uid);
        $userini->setDisplayname($arDataUser[0][$this->config_users['displayname']][0]);
        
        // Mise en forme du tableau contenant les cn des groupes dont l'utilisateur recherché est membre
        $tab = array_splice($arDataUser[0][$this->config_groups['memberof']], 1);
        $tab_cn = array();
        foreach($tab as $dn) {
            $tab_cn[] = preg_replace("/(".$this->config_groups['cn']."=)(([A-Za-z0-9:._-]{1,}))(,".$this->config_groups['group_branch'].".*)/", "$3", $dn);
        }

        // Récupération des groupes dont l'utilisateur recherché est admin
        $arDataAdmin=$ldapfonctions->recherche($this->config_groups['groupadmin']."=".$this->config_users['uid']."=".$uid.",".$this->config_users['people_branch'].",".$this->base,array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
        $tab_cn_admin = array();
        for($i=0;$i<$arDataAdmin["count"];$i++) {
            $tab_cn_admin[$i] = $arDataAdmin[$i][$this->config_groups['cn']][0];
        }

        // Si on a sélectionné une proposition dans la liste d'autocomplétion
        if ($flag_cn=='1') {
            // On teste si on est sur le message "... Résultat partiel ..."
            if ($cn_search == "... Résultat partiel ...") {
                $this->get('session')->getFlashBag()->add('flash-notice', 'Le nom du groupe est invalide');                        
                return $this->redirect($this->generateUrl('group_search', array('opt'=>'add', 'uid'=>$uid, 'cn'=> $cn_search)));
            }
            
            // Recherche exacte sur le cn sélectionné dans le LDAP
            $arData=$ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=" . $cn_search . "))",array($this->config_groups['cn'],$this->config_groups['desc'],$this->config_groups['groupfilter']), $this->config_groups['cn']);
        }
        else {
            // Recherche avec * dans le LDAP
            $arData=$ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=*" . $cn_search . "*))",array($this->config_groups['cn'],$this->config_groups['desc'],$this->config_groups['groupfilter']), $this->config_groups['cn']);
        }

        // Récupération des groupes publics issus de la recherche
        $cpt=0;
        for ($i=0; $i<$arData["count"]; $i++) {
            // on ne garde que les groupes publics
            if (!strstr($arData[$i]["dn"], $this->config_private['private_branch'])) {
                $tab_cn_search[$cpt] = $arData[$i][$this->config_groups['cn']][0];
                $cpt++;
            }
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
            foreach($tab_cn as $cn) {
                if ($cn==$groupname) {
                    $membership->setMemberof(TRUE);
                    $membershipini->setMemberof(TRUE);
                    break;
                }
                else {
                    $membership->setMemberof(FALSE);
                    $membershipini->setMemberof(FALSE);
                 }
            }
            
            //Remplissage des droits admin
            foreach($tab_cn_admin as $cn) {
                if ($cn==$groupname) {
                    $membership->setAdminof(TRUE);
                    $membershipini->setAdminof(TRUE);
                    break;
                }
                else {
                    $membership->setAdminof(FALSE);
                    $membershipini->setAdminof(FALSE);
                 }
            }
                        
            // Gestion droits pour un gestionnaire
            if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
                foreach($tab_cn_admin_login as $cn) {
                    if ($cn==$groupname) {
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
            'action' => $this->generateUrl('group_add', array('cn_search'=> $cn_search, 'uid' => $uid, 'flag_cn' => $flag_cn)),
            'method' => 'POST',
        ));
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            // Initialisation des entités
            $userupdate = new User();
            $m_update = new ArrayCollection();      
            
            // Récupération des données du formulaire
            $userupdate = $editForm->getData();
            $m_update = $userupdate->getMemberships();
            
            // Log Mise à jour des membres du groupe
            openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
            $adm = $request->getSession()->get('phpCAS_user');
            
            // Pour chaque appartenance
            for ($i=0; $i<sizeof($m_update); $i++) {
                $memb = $m_update[$i];
                $dn_group = $this->config_groups['cn']."=" . $memb->getGroupname() . ", ".$this->config_groups['group_branch'].", ".$this->base;
                $gr = $memb->getGroupname();
                
                // Traitement des membres  
                // Si il y a changement pour le membre, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getMemberof() != $membershipsini[$i]->getMemberof()) {
                    if ($memb->getMemberof()) {
                        // Ajout utilisateur dans groupe
                        $r = $ldapfonctions->addMemberGroup($dn_group, array($uid));
                        // Log des modifications
                        if ($r==true) 
                            syslog(LOG_INFO, "add_member by $adm : group : $gr, user : $uid ");
                        else 
                            syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $gr, user : $uid");
                    }
                    else {
                        // Suppression utilisateur du groupe
                        $r = $ldapfonctions->delMemberGroup($dn_group, array($uid));
                        if ($r)
                            syslog(LOG_INFO, "del_member by $adm : group : $gr, user : $uid ");
                        else 
                            syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $gr, user : $uid");
                    }
                }
                // Traitement des admins
                // Si il y a changement pour admin, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getAdminof() != $membershipsini[$i]->getAdminof()) {
                    if ($memb->getAdminof()) {
                        // Ajout admin dans le groupe
                        $r = $ldapfonctions->addAdminGroup($dn_group, array($uid));
                        if ($r)
                            syslog(LOG_INFO, "add_admin by $adm : group : $gr, user : $uid ");
                        else 
                            syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $gr, user : $uid ");
                    }
                    else {
                        // Suppression admin du groupe
                        $r = $ldapfonctions->delAdminGroup($dn_group, array($uid));
                        if ($r)
                            syslog(LOG_INFO, "del_admin by $adm : group : $gr, user : $uid ");
                        else
                            syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $gr, user : $uid ");
                    }
                }
            }
            // Ferme fichier de log
            closelog();
            // Notification message flash
            $this->get('session')->getFlashBag()->add('flash-notice', 'Les modifications ont bien été enregistrées');
            
            // Retour à la page update d'un utilisateur
            return $this->redirect($this->generateUrl('user_update', array('uid'=>$uid)));
        }
         
        // Affichage via le fichier twig
        return array(
            'user'      => $user,
            'cn_search' => $cn_search,
            'flag_cn' => $flag_cn,
            'form'   => $editForm->createView(),
        );
    }
    
    /**
     * Voir les membres et administrateurs d'un groupe.
     *
     * @Route("/see/{cn}/{mail}/{liste}", name="see_group")
     * @Template()
     */
    public function seeAction(Request $request, $cn, $mail, $liste)
    {
        $this->init_config();
        // Initialisation des tableaux d'entités
        $users = array();
        $admins = array();

        // Vérification des droits
        $flag = "nok";
        // Dans le cas d'un membre
        if ((true === $this->get('security.context')->isGranted('ROLE_MEMBRE'))||(true === $this->get('security.context')->isGranted('ROLE_DOSI')))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        // Récupération du groupe recherché
        $result = $ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=" . $cn . "))", array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
        if (isset($result[0][$this->config_groups['groupfilter']]))
            $amugroupfilter = $result[0][$this->config_groups['groupfilter']][0];
        else
            $amugroupfilter = "";
        
        // Recherche des membres dans le LDAP
        //$arUsers = $this->getLdap()->getMembersGroup($cn);
        $arUsers = $ldapfonctions->getMembersGroup($cn);
        
        // on remplit le tableau d'entités
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $users[$i] = new User();
            $users[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $users[$i]->setSn($arUsers[$i][$this->config_users['name']][0]);
            $users[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            if ($mail=='true')
                $users[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            if (isset($arUsers[$i][$this->config_users['tel']][0]))
                $users[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
            else 
                $users[$i]->setTel("");
            if (isset($arUsers[$i][$this->config_users['primaff']][0]))
                $users[$i]->setPrimAff($arUsers[$i][$this->config_users['primaff']][0]);
            else
                $users[$i]->setPrimAff("");
            if (isset($arUsers[$i][$this->config_users['aff']][0]))
                $users[$i]->setAff($arUsers[$i][$this->config_users['aff']][0]);
            else
                $users[$i]->setAff("");

        }
        
        // Recherche des administrateurs du groupe
        $arAdmins = $ldapfonctions->getAdminsGroup($cn);
        
        if (isset($arAdmins[0][$this->config_groups['groupadmin']]["count"])) {
        // on remplit le tableau d'entités        
        for ($i=0; $i<$arAdmins[0][$this->config_groups['groupadmin']]["count"]; $i++) {
            $uid = preg_replace("/(".$this->config_users['uid']."=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0][$this->config_groups['groupadmin']][$i]);
            $result = $ldapfonctions->getInfosUser($uid);
            $admins[$i] = new User();
            $admins[$i]->setUid($result[0][$this->config_users['uid']][0]);
            $admins[$i]->setSn($result[0][$this->config_users['name']][0]);
            $admins[$i]->setDisplayname($result[0][$this->config_users['displayname']][0]);
            if (isset($result[0][$this->config_users['mail']][0]))
                $admins[$i]->setMail($result[0][$this->config_users['mail']][0]);
            else
                $admins[$i]->setMail("");
            if (isset($result[0][$this->config_users['tel']][0]))
                $admins[$i]->setTel($result[0][$this->config_users['tel']][0]);
            else 
                $admins[$i]->setTel("");
            if (isset($result[0][$this->config_users['aff']][0]))
                $admins[$i]->setAff($result[0][$this->config_users['aff']][0]);
            else
                $admins[$i]->setAff("");
            if (isset($result[0][$this->config_users['primaff']][0]))
                $admins[$i]->setPrimAff($result[0][$this->config_users['primaff']][0]);
            else
                $admins[$i]->setPrimAff("");
            
        }
        }
        else {
            $arAdmins[0][$this->config_groups['groupadmin']]["count"]=0;
        }

        if (true === $this->get('security.context')->isGranted('ROLE_DOSI'))
            $dosi=1;
        else
            $dosi=0;
        
        // Affichage via le fichier twig
        return array('cn' => $cn,
                    'amugroupfilter' => $amugroupfilter,
                    'nb_membres' => $arUsers["count"], 
                    'users' => $users,
                    'nb_admins' => $arAdmins[0][$this->config_groups['groupadmin']]["count"],
                    'admins' => $admins,
                    'dosi' => $dosi,
                    'mail' => $mail,
                    'liste' => $liste);
    }
    
     /**
     * Voir les membres et administrateurs d'un groupe privé.
     *
     * @Route("/see_private/{cn}/{opt}", name="see_private_group")
     * @Template()
     */
    public function seeprivateAction(Request $request, $cn, $opt)
    {
        $this->init_config();
        $users = array();
        // Vérification des droits
        $flag = "nok";
        // Dans le cas d'un membre
        if (true === $this->get('security.context')->isGranted('ROLE_MEMBRE'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Récupération du propriétaire du groupe
        $cn_perso = substr($cn, strlen($this->config_private['prefix'])+1); // on retire le préfixe amu:perso:
        $uid_prop = strstr($cn_perso, ":", TRUE);
        $result = $ldapfonctions->getInfosUser($uid_prop);
        $proprietaire = new User();
        $proprietaire->setUid($result[0]["uid"][0]);
        $proprietaire->setSn($result[0][$this->config_users['name']][0]);
        $proprietaire->setDisplayname($result[0][$this->config_users['displayname']][0]);
        $proprietaire->setMail($result[0][$this->config_users['mail']][0]);
        $proprietaire->setTel($result[0][$this->config_users['tel']][0]);
        
        // Recherche des membres dans le LDAP
        $arUsers = $ldapfonctions->getMembersGroup($cn.",".$this->config_private['private_branch']);
                 
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $users[$i] = new User();
            $users[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $users[$i]->setSn($arUsers[$i][$this->config_users['name']][0]);
            $users[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            $users[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            $users[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
        }
        
        // Affichage via twig
        return array('cn' => $cn,
                    'nb_membres' => $arUsers["count"], 
                    'proprietaire' => $proprietaire,
                    'users' => $users,
                    'opt' => $opt);
    }
        
    /**
     * Création d'un groupe
     *
     * @Route("/create",name="group_create")
     * @Template("AmuGroupieBundle:Group:group.html.twig")
     */
    public function createAction(Request $request) {
        $this->init_config();
        // Initialisation des entités
        $group = new Group();
        $groups = array();

        // Vérification des droits
        $flag = "nok";
        // Droits seulement pour les admins de l'appli
        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }
        
        // Création du formulaire de création de groupe
        $form = $this->createForm(new GroupCreateType(),
            new Group(),
            array('action' => $this->generateUrl('group_create'),
                'method' => 'GET'));
        $form->handleRequest($request);
        if ($form->isValid()) {
            // Récupération des données
            $group = $form->getData();
            
            // Log création de groupe
            openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
            $adm = $request->getSession()->get('phpCAS_user');
                
            // Création du groupe dans le LDAP
            $parameters = array();
            $parameters['objectclasses'] = $this->objectclasses;
            $parameters['ou'] = $this->ou;
            $parameters['groupfilter'] = $this->config_groups['groupfilter'];
            $infogroup = $group->infosGroupeLdap($parameters);

            // On récupère le service ldapfonctions
            $ldapfonctions = $this->container->get('groupie.ldapfonctions');
            $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
            $b =$ldapfonctions->createGroupeLdap($infogroup['dn'], $infogroup['infos']);
            if ($b==true) {          
                // affichage groupe créé
                $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été créé');
                $groups[0] = $group;
                $cn = $group->getCn();
                
                // Log création OK
                syslog(LOG_INFO, "create_group by $adm : group : $cn");
               
                // Affichage via fichier twig
                return $this->render('AmuGroupieBundle:Group:create.html.twig',array('groups' => $groups));
            }
            else {
                // affichage erreur
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la création du groupe');
                $groups[0] = $group;
                $cn = $group->getCn();
                
                // Log erreur
                syslog(LOG_ERR, "LDAP ERREUR : create_group by $adm : group : $cn");
                
                // Retour à la page contenant le formulaire de création de groupe
                return $this->render('AmuGroupieBundle:Group:group.html.twig', array('form' => $form->createView()));
            }
            
            // Ferme le fichier de log
            closelog();
        }
        
        // Affichage formulaire de création de groupe
        return $this->render('AmuGroupieBundle:Group:group.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * Création d'un groupe privé
     *
     * @Route("/private/create/{nb_groups}",name="private_group_create")
     * @Template("AmuGroupieBundle:Group:createprivate.html.twig")
     */
    public function createPrivateAction(Request $request, $nb_groups) {
        $this->init_config();

        // Vérification des droits
        $flag = "nok";
        // Dans le cas d'un membre
        if (true === $this->get('security.context')->isGranted('ROLE_MEMBRE'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // Limite sur le nombre de groupes privés qu'il est possible de créer
        if ($nb_groups>20){
            return $this->render('AmuCliGrouperBundle:Group:limite.html.twig');
        }
        
        // Initialisation des entités
        $group = new Group();
        $groups = array();
                
        // Création du formulaire
        $form = $this->createForm(new PrivateGroupCreateType(),
            new Group(),
            array('action' => $this->generateUrl('private_group_create', array('nb_groups'=>$nb_groups)),
                'method' => 'GET'));

        $form->handleRequest($request);
        if ($form->isValid()) {
            // Récupération de l'entrée utilisateur
            $group = $form->getData();
            
            // Vérification de la validité du champ cn : pas d'espaces, accents, caractères spéciaux
            $test = preg_match("#^[A-Za-z0-9-_]+$#i", $group->getCn());
            if ($test>0) {
                // le nom du groupe est valide, on peut le créer
                // Log création de groupe
                openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
                $adm = $request->getSession()->get('phpCAS_user');

                // On récupère le service ldapfonctions/create
                $ldapfonctions = $this->container->get('groupie.ldapfonctions');
                $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

                // Création du groupe dans le LDAP
                $parameters = array();
                $parameters['objectclasses'] = $this->objectclasses;
                $parameters['ou'] = $this->ou;
                $parameters['prefix'] = $this->config_private['prefix'].$adm;
                $infogroup = $group->infosGroupePriveLdap($parameters);

                $b = $ldapfonctions->createGroupeLdap($infogroup['dn'], $infogroup['infos']);
                if ($b==true) { 
                    //Le groupe a bien été créé
                    $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été créé');
                    $groups[0] = $group;
                    $cn = $this->config_private['prefix'].":".$adm.":".$group->getCn();
                    $group->setCn($cn);

                    // Log création OK
                    syslog(LOG_INFO, "create_private_group by $adm : group : $cn");

                    // Ajout du propriétaire dans le groupe
                    $r = $ldapfonctions->addMemberGroup($infogroup['dn'], array($adm));
                    if ($r) {
                        // Log modif
                        syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $adm");
                    }
                    else {
                        syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $adm");
                    }
                    // Ferme fichier log
                    closelog();

                    // Retour à la page update d'un groupe
                    return $this->redirect($this->generateUrl('private_group_update', array('cn'=>$cn)));
                    // Affichage création OK
                    return $this->render('AmuGroupieBundle:Group:privatecreation.html.twig',array('groups' => $groups));
                }
                else {
                    // affichage erreur
                    $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la création du groupe');
                    $groups[0] = $group;
                    $cn = $this->config_private['prefix'].":".$adm.":".$group->getCn();

                    // Log erreur
                    syslog(LOG_ERR, "LDAP ERREUR : create_private_group by $adm : group : $cn");

                    // Affichage page 
                    return $this->render('AmuGroupieBundle:Group:createprivate.html.twig', array('form' => $form->createView(), 'nb_groups' => $nb_groups));
                }

                // Ferme le fichier de log
                closelog();
            }
            else {
                // le nom du groupe n'est pas valide, notification à l'utilisateur
                // affichage erreur
                $this->get('session')->getFlashBag()->add('flash-error', 'Le nom du groupe est invalide. Merci de supprimer les accents et caractères spéciaux.');
                    
                // Affichage page du formulaire
                return $this->render('AmuGroupieBundle:Group:createprivate.html.twig', array('form' => $form->createView(), 'nb_groups' => $nb_groups));
            }
        }
        return $this->render('AmuGroupieBundle:Group:createprivate.html.twig', array('form' => $form->createView(), 'nb_groups' => $nb_groups));
    }
    
     /**
     * Supprimer un groupe.
     *
     * @Route("/delete/{cn}", name="group_delete")
     * @Template()
     */
    public function deleteAction(Request $request, $cn)
    {
        $this->init_config();
        // Log suppression de groupe
        openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
        $adm = $request->getSession()->get('phpCAS_user');
        
        //Suppression du groupe dans le LDAP
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Vérification des droits
        $flag = "nok";
        // Suppression autorisée pour les admin de l'appli seulement
        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        $b = $ldapfonctions->deleteGroupeLdap($cn);
        if ($b==true) {
            //Le groupe a bien été supprimé
            $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été supprimé');
            
            // Log
            syslog(LOG_INFO, "delete_group by $adm : group : $cn");
            
            return $this->render('AmuGroupieBundle:Group:delete.html.twig',array('cn' => $cn));
        }
        else {
            // Log erreur
            syslog(LOG_ERR, "LDAP ERROR : delete_group by $adm : group : $cn");
            // affichage erreur
            $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la suppression du groupe');
            // Retour page de recherche
            return  $this->redirect($this->generateUrl('group_search_del'));
        }
        
        // Ferme fichier de log
        closelog();
    }
    
    /**
     * Choisir un groupe privé à supprimer
     *
     * @Route("/private/delete",name="private_group_delete")
     * @Template("AmuGroupieBundle:Group:deleteprivate.html.twig")
     */
    public function deletePrivateAction(Request $request) {
        $this->init_config();
        $uid = $request->getSession()->get('phpCAS_user');
        // Recherche des groupes dans le LDAP
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        $arData = $ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=".$this->config_private['prefix'].":".$uid.":*))",array($this->config_groups['cn'],$this->config_groups['desc']), $this->config_groups['cn']);
    
        $groups = new ArrayCollection();
        for ($i=0; $i<$arData["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($arData[$i][$this->config_groups['cn']][0]);
            $groups[$i]->setDescription($arData[$i][$this->config_groups['desc']][0]);
            
        }
        
        return array('groups' => $groups);
    }
    
    /**
     * Supprimer un groupe privé.
     *
     * @Route("/private/del_1/{cn}", name="private_group_del_1")
     * @Template()
     */
    public function del1PrivateAction(Request $request, $cn) {
        $this->init_config();
        // Log suppression de groupe
        openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
        $adm = $request->getSession()->get('phpCAS_user');
        
        // Suppression du groupe dans le LDAP
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        // Vérification des droits
        $flag = "nok";
        // Dans le cas d'un gestionnaire
        if (true === $this->get('security.context')->isGranted('ROLE_MEMBRE')) {
            // Recup des groupes privés de l'utilisateur
            $result = $ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=".$this->config_private['prefix'].":".$adm.":*))", array($this->config_groups['cn'], $this->config_groups['desc']), $this->config_groups['cn']);
            for($i=0;$i<$result["count"];$i++) {
                if ($cn==$result[$i][$this->config_groups['cn']][0]) {
                    $flag = "ok";
                    break;
                }
            }
        }
        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))
            $flag = "ok";
        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        $b = $ldapfonctions->deleteGroupeLdap($cn.",".$this->config_private['private_branch']);
        if ($b==true) {
            //Le groupe a bien été supprimé
            $this->get('session')->getFlashBag()->add('flash-notice', 'Le groupe a bien été supprimé');
            
            // Log
            syslog(LOG_INFO, "delete_private_group by $adm : group : $cn");                        
        }
        else {
            // Log erreur
            syslog(LOG_ERR, "LDAP ERROR : delete_private_group by $adm : group : $cn");
            // affichage erreur
            $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la suppression du groupe');
        }
        
        // Ferme fichier de log
        closelog();

        // Retour page de gestion des groupes privés
        return  $this->redirect($this->generateUrl('private_group'));

    }
    
    /**
     * Modifier un groupe.
     *
     * @Route("/modify/{cn}/{desc}/{filt}", name="group_modify")
     * @Template()
     */
    public function modifyAction(Request $request, $cn, $desc, $filt)
    {
        $this->init_config();
        $group = new Group();
        $groups = array();
        
        $dn = $this->config_groups['cn']."=".$cn.", ".$this->config_groups['group_branch'].", ".$this->base;

        // Pré-remplir le formulaire avec les valeurs actuelles du groupe
        $group->setCn($cn);
        $group->setDescription($desc);
        if ($filt=="no")
            $group->setAmugroupfilter("");
        else
            $group->setAmugroupfilter($filt);
        
        $form = $this->createForm(new GroupModifType(), $group);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $groupmod = new Group();
            $groupmod = $form->getData();
            
            // Log modif de groupe
            openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
            $adm = $request->getSession()->get('phpCAS_user');
            
            // Cas particulier de la suppression amugroupfilter
            if (($filt != "no") && ($groupmod->getAmugroupfilter() == "")) {
                // Suppression de l'attribut
                $b = $this->getLdap()->delAmuGroupFilter($dn, $filt);
                // Log Erreur LDAP
                syslog(LOG_ERR, "LDAP ERROR : modif_group by $adm : group : $cn, delAmuGroupFilter");
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la modification du groupe');
                return $this->render('AmuGroupieBundle:Group:modifyform.html.twig', array('form' => $form->createView(), 'group' => $group));
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
                return $this->render('AmuGroupieBundle:Group:modifygroupe.html.twig',array('groups' => $groups));
            }
            else 
            {
                // Log Erreur LDAP
                syslog(LOG_ERR, "LDAP ERROR : modif_group by $adm : group : $cn");
                $this->get('session')->getFlashBag()->add('flash-error', 'Erreur LDAP lors de la modification du groupe');
                return $this->render('AmuGroupieBundle:Group:modifyform.html.twig', array('form' => $form->createView(), 'group' => $group));
            }
            
            // Ferme fichier log
            closelog();
        }
        return $this->render('AmuGroupieBundle:Group:modifyform.html.twig', array('form' => $form->createView(), 'group' => $group));
    }

    /**
    * Affichage d'une liste de groupe en session
    *
    * @Route("/afficheliste/{opt}/{uid}",name="group_display")
    */
    public function displayAction(Request $request, $opt='search', $uid='') {
        $this->init_config();
        // Récupération des groupes mis en session
        $groups = $this->container->get('request')->getSession()->get('groups');

        return $this->render('AmuGroupieBundle:Group:search.html.twig',array('groups' => $groups, 'opt' => $opt, 'uid' => $uid));
    }
    
    /**
    * Gestion des groupes privés de l'utilisateur
    *
    * @Route("/private",name="private_group")
    * @Template() 
    */
    public function privateAction(Request $request) {
        $this->init_config();
        // Récupération uid de l'utilisateur logué
        $uid = $request->getSession()->get('phpCAS_user');
        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);
        // Recherche des groupes dans le LDAP
        $result = $ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=".$this->config_private['prefix'].":".$uid.":*))", array($this->config_groups['cn'], $this->config_groups['desc']), $this->config_groups['cn']);
    
        $groups = new ArrayCollection();
        for ($i=0; $i<$result["count"]; $i++) {
            $groups[$i] = new Group();
            $groups[$i]->setCn($result[$i][$this->config_groups['cn']][0]);
            $groups[$i]->setDescription($result[$i][$this->config_groups['desc']][0]);
        }
        // Affichage du tableau de groupes privés via fichier twig
        return array('groups' => $groups, 'nb_groups' => $result["count"]);
    }
    
    /**
     * Mettre à jour les membres d'un groupe privé.
     *
     * @Route("/private/update/{cn}", name="private_group_update")
     * @Template("AmuGroupieBundle:Group:privateupdate.html.twig")
     */
    public function privateupdateAction(Request $request, $cn)
    {
        $this->init_config();
        // Initialisation des entités
        $group = new Group();
        $group->setCn($cn);
        $members = new ArrayCollection();
        
        // Groupe initial pour détecter les modifications
        $groupini = new Group();
        $groupini->setCn($cn);
        $membersini = new ArrayCollection();

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        $flag = "nok";
        // Dans le cas d'un utilisateur
        if (true === $this->get('security.context')->isGranted('ROLE_MEMBRE')) {
            // Recup des groupes dont l'utilisateur est admin
            $arDataAdminLogin = $ldapfonctions->recherche("(&(objectClass=".$this->config_groups['object_class'].")(".$this->config_groups['cn']."=".$this->config_private['prefix'].":".$request->getSession()->get('phpCAS_user').":*))", array($this->config_groups['cn'], $this->config_groups['desc']) , $this->config_groups['cn']);
            for($i=0;$i<$arDataAdminLogin["count"];$i++) {
                if ($cn==$arDataAdminLogin[$i][$this->config_groups['cn']][0]) {
                    $flag = "ok";
                    break;
                }
            }
        }
        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))
            $flag = "ok";

        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // Recherche des membres dans le LDAP
        $arUsers = $ldapfonctions->getMembersGroup($cn.",".$this->config_private['private_branch']);
        
        // Affichage des membres  
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $members[$i] = new Member();
            $members[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $members[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            $members[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            $members[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
            $members[$i]->setMember(TRUE);
            $members[$i]->setAdmin(FALSE);
           
            // Idem pour groupini
            $membersini[$i] = new Member();
            $membersini[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $membersini[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            $membersini[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            $membersini[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
            $membersini[$i]->setMember(TRUE);
            $membersini[$i]->setAdmin(FALSE);
        }
        // on remplit les groupes
        $group ->setMembers($members);
        $groupini ->setMembers($membersini);
                      
        // Création du formulaire de mise à jour
        $editForm = $this->createForm(new PrivateGroupEditType(), $group, array(
            'action' => $this->generateUrl('private_group_update', array('cn'=> $cn)),
            'method' => 'POST',));

        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            // Récupération des données du formulaire
            $groupupdate = new Group();
            $groupupdate = $editForm->getData();
            
            // Log Mise à jour des membres du groupe
            openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
            $adm = $request->getSession()->get('phpCAS_user');
            
            // Récup des appartenances
            $m_update = new ArrayCollection();      
            $m_update = $groupupdate->getMembers();
            
            // Nombre de membres
            $nb_memb = sizeof($m_update);
            
            // Mise à jour des membres et admins
            for ($i=0; $i<sizeof($m_update); $i++){
                $memb = $m_update[$i];
                $membi = $membersini[$i];
                $dn_group = $this->config_groups['cn']."=" . $cn . ", ".$this->config_private['private_branch'].", ".$this->config_groups['group_branch'].", ".$this->base;
                $u = $memb->getUid();
                
                // Traitement des membres
                // Si il y a changement pour le membre, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getMember() != $membi->getMember()) {
                    if ($memb->getMember()) {
                        $r = $ldapfonctions->addMemberGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $u ");
                            $nb_memb++;
                        }
                        else {
                            // Message de notification
                            $this->get('session')->getFlashBag()->add('flash-error', 'Erreur  lors de l\'ajout uid='.$u);
                            syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $u ");
                        }
                    }
                    else {
                        $r = $ldapfonctions->delMemberGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "del_member by $adm : group : $cn, user : $u ");
                            $nb_memb--;
                        }
                        else {
                            // Message de notification
                            $this->get('session')->getFlashBag()->add('flash-error', 'Erreur  lors de la suppression uid='.$u);
                            syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $cn, user : $u ");
                        }
                    }
                }
            }
            // Ferme fichier de log
            closelog();

            // Message de notification
            $this->get('session')->getFlashBag()->add('flash-notice', 'Les modifications ont bien été enregistrées');

            // Retour à l'affichage group_update
            return $this->redirect($this->generateUrl('private_group_update', array('cn'=>$cn)));
        }

        return array(
            'group'      => $group,
            'nb_membres' => $arUsers["count"],
            'form'   => $editForm->createView()
            ); 

    }
    
    /**
     * Mettre à jour les membres d'un groupe 
     *
     * @Route("/update/{cn}/{liste}", name="group_update")
     * @Template("AmuGroupieBundle:Group:update.html.twig")     */
    public function updateAction(Request $request, $cn, $liste="")
    {
        $this->init_config();
        $group = new Group();
        $group->setCn($cn);
        $members = new ArrayCollection();
        
        // Groupe initial pour détecter les modifications
        $groupini = new Group();
        $groupini->setCn($cn);
        $membersini = new ArrayCollection();

        // On récupère le service ldapfonctions
        $ldapfonctions = $this->container->get('groupie.ldapfonctions');
        $ldapfonctions->SetLdap($this->get('amu.ldap'), $this->config_users, $this->config_groups, $this->config_private);

        $flag = "nok";
        // Dans le cas d'un gestionnaire
        if (true === $this->get('security.context')->isGranted('ROLE_GESTIONNAIRE')) {
            // Recup des groupes dont l'utilisateur est admin
            $arDataAdminLogin = $ldapfonctions->recherche($this->config_groups['groupadmin']."=".$this->config_users['uid']."=".$request->getSession()->get('phpCAS_user').",".$this->config_users['people_branch'].",".$this->base,array($this->config_groups['cn'], $this->config_groups['desc'], $this->config_groups['groupfilter']), $this->config_groups['cn']);
            for($i=0;$i<$arDataAdminLogin["count"];$i++)
            {
                if ($cn==$arDataAdminLogin[$i][$this->config_groups['cn']][0]) {
                    $flag = "ok";
                    break;
                }
            }
        }
        if (true === $this->get('security.context')->isGranted('ROLE_ADMIN'))
            $flag = "ok";

        if ($flag=="nok") {
            // Retour à l'accueil
            $this->get('session')->getFlashBag()->add('flash-error', 'Vous n\'avez pas les droits pour effectuer cette opération');
            return $this->redirect($this->generateUrl('homepage'));
        }

        // Récup du filtre amugroupfilter pour affichage
        $amugroupfilter = $ldapfonctions->getAmuGroupFilter($cn);
        $group->setAmugroupfilter($amugroupfilter);
               
        // Recherche des membres dans le LDAP
        $arUsers = $ldapfonctions->getMembersGroup($cn);
        $nb_members = $arUsers["count"];
        
        // Recherche des admins dans le LDAP
        $nb_admins = 0;
        $arAdmins = $ldapfonctions->getAdminsGroup($cn);
        $flagMembers = array();
        if (isset($arAdmins[0][$this->config_groups['groupadmin']]["count"])) {
            $nb_admins = $arAdmins[0][$this->config_groups['groupadmin']]["count"];
            for ($i = 0; $i < $arAdmins[0][$this->config_groups['groupadmin']]["count"]; $i++) {
                $flagMembers[$i] = FALSE;
            }
        }
        
        // Affichage des membres  
        for ($i=0; $i<$arUsers["count"]; $i++) {                     
            $members[$i] = new Member();
            $members[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $members[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            if (isset($arUsers[$i][$this->config_users['mail']][0]))
                $members[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            else
                $members[$i]->setMail("");
            if (isset($arUsers[$i][$this->config_users['tel']][0]))
                $members[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
            else
                $members[$i]->setTel("");
            if (isset($arUsers[$i][$this->config_users['aff']][0]))
                $members[$i]->setAff($arUsers[$i][$this->config_users['aff']][0]);
            else
                $members[$i]->setAff("");
            if (isset($arUsers[$i][$this->config_users['primaff']][0]))
                $members[$i]->setPrimAff($arUsers[$i][$this->config_users['primaff']][0]);
            else
                $members[$i]->setPrimAff("");
            $members[$i]->setMember(TRUE);
            $members[$i]->setAdmin(FALSE);
           
            // Idem pour groupini
            $membersini[$i] = new Member();
            $membersini[$i]->setUid($arUsers[$i][$this->config_users['uid']][0]);
            $membersini[$i]->setDisplayname($arUsers[$i][$this->config_users['displayname']][0]);
            if (isset($arUsers[$i][$this->config_users['mail']][0]))
                $membersini[$i]->setMail($arUsers[$i][$this->config_users['mail']][0]);
            else
                $membersini[$i]->setMail("");
            if (isset($arUsers[$i][$this->config_users['tel']][0]))
                $membersini[$i]->setTel($arUsers[$i][$this->config_users['tel']][0]);
            else
                $membersini[$i]->setTel("");
            if (isset($arUsers[$i][$this->config_users['aff']][0]))
                $membersini[$i]->setAff($arUsers[$i][$this->config_users['aff']][0]);
            else
                $membersini[$i]->setAff("");
            if (isset($arUsers[$i][$this->config_users['primaff']][0]))
                $membersini[$i]->setPrimAff($arUsers[$i][$this->config_users['primaff']][0]);
            else
                $membersini[$i]->setPrimAff("");
            $membersini[$i]->setMember(TRUE);
            $membersini[$i]->setAdmin(FALSE);
            
            // on teste si le membre est aussi admin
            if (isset($arAdmins[0][$this->config_groups['groupadmin']]["count"])) {
                for ($j = 0; $j < $arAdmins[0][$this->config_groups['groupadmin']]["count"]; $j++) {
                    $uid = preg_replace("/(".$this->config_users['uid']."=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0][$this->config_groups['groupadmin']][$j]);
                    if ($uid == $arUsers[$i][$this->config_users['uid']][0]) {
                        $members[$i]->setAdmin(TRUE);
                        $membersini[$i]->setAdmin(TRUE);
                        $flagMembers[$j] = TRUE;
                        break;
                    }
                }
            }
        }
                
        // Affichage des admins qui ne sont pas membres
        if (isset($arAdmins[0][$this->config_groups['groupadmin']]["count"])) {
            for ($j = 0; $j < $arAdmins[0][$this->config_groups['groupadmin']]["count"]; $j++) {
                if ($flagMembers[$j] == FALSE) {
                    // si l'admin n'est pas membre du groupe, il faut aller récupérer ses infos dans le LDAP
                    $uid = preg_replace("/(".$this->config_users['uid']."=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $arAdmins[0][$this->config_groups['groupadmin']][$j]);
                    $result = $ldapfonctions->getInfosUser($uid);
                    $memb = new Member();
                    $memb->setUid($result[0][$this->config_users['uid']][0]);
                    $memb->setDisplayname($result[0][$this->config_users['displayname']][0]);
                    if (isset($result[0][$this->config_users['mail']][0]))
                        $memb->setMail($result[0][$this->config_users['mail']][0]);
                    else
                        $memb->setMail("");
                    if (isset($result[0][$this->config_users['tel']][0]))
                        $memb->setTel($result[0][$this->config_users['tel']][0]);
                    else
                        $memb->setTel("");
                    if (isset($result[0][$this->config_users['aff']][0]))
                        $memb->setAff($result[0][$this->config_users['aff']][0]);
                    else
                        $memb->setAff("");
                    if (isset($result[0][$this->config_users['primaff']][0]))
                        $memb->setPrimAff($result[0][$this->config_users['primaff']][0]);
                    else
                        $memb->setPrimAff("");
                    $memb->setMember(FALSE);
                    $memb->setAdmin(TRUE);
                    $members[] = $memb;

                    // Idem pour groupini
                    $membini = new Member();
                    $membini->setUid($result[0][$this->config_users['uid']][0]);
                    $membini->setDisplayname($result[0][$this->config_users['displayname']][0]);
                    if (isset($result[0][$this->config_users['mail']][0]))
                        $membini->setMail($result[0][$this->config_users['mail']][0]);
                    else
                        $membini->setMail("");
                    if (isset($result[0][$this->config_users['tel']][0]))
                        $membini->setTel($result[0][$this->config_users['tel']][0]);
                    else
                        $membini->setTel("");
                    if (isset($result[0][$this->config_users['aff']][0]))
                        $membini->setAff($result[0][$this->config_users['aff']][0]);
                    else
                        $membini->setAff("");
                    if (isset($result[0][$this->config_users['primaff']][0]))
                        $membini->setPrimAff($result[0][$this->config_users['primaff']][0]);
                    else
                        $membini->setPrimAff("");
                    $membini->setMember(FALSE);
                    $membini->setAdmin(TRUE);
                    $membersini[] = $membini;
                }
            }
        }
        
        $group ->setMembers($members);
        $groupini ->setMembers($membersini);
                      
        // Création du formulaire de mise à jour du groupe
        $editForm = $this->createForm(new GroupEditType(), $group,array(
            'action' => $this->generateUrl('group_update', array('cn'=> $cn)),
            'method' => 'POST',));

        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            echo "Formulaire validé";
            $groupupdate = new Group();
            $groupupdate = $editForm->getData();
            
            // Log Mise à jour des membres du groupe
            openlog("groupie-v2", LOG_PID | LOG_PERROR, constant($this->config_logs['facility']));
            $adm = $request->getSession()->get('phpCAS_user');
            
            $m_update = new ArrayCollection();      
            $m_update = $groupupdate->getMembers();
            
            $nb_memb = sizeof($m_update);
            
            // on parcourt tous les membres
            for ($i=0; $i<sizeof($m_update); $i++)
            {
                $memb = $m_update[$i];
                $membi = $membersini[$i];
                $dn_group = $this->config_groups['cn']."=" . $cn . ", ".$this->config_groups['group_branch'].", ".$this->base;
                $u = $memb->getUid();

                // Traitement des membres
                // Si il y a changement pour le membre, on modifie dans le ldap, sinon, on ne fait rien
                if ($memb->getMember() != $membi->getMember()) {
                    if ($memb->getMember()) {
                        $r = $ldapfonctions->addMemberGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "add_member by $adm : group : $cn, user : $u ");
                            $nb_memb++;
                        }
                        else {
                            syslog(LOG_ERR, "LDAP ERROR : add_member by $adm : group : $cn, user : $u ");
                        }
                    }
                    else {
                        $r = $ldapfonctions->delMemberGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "del_member by $adm : group : $cn, user : $u ");
                            $nb_memb--;
                        }
                        else {
                            syslog(LOG_ERR, "LDAP ERROR : del_member by $adm : group : $cn, user : $u ");
                        }
                    }
                }
                // Traitement des admins
                // Idem : si changement, on répercute dans le ldap
                if ($memb->getAdmin() != $membi->getAdmin()) {
                    if ($memb->getAdmin()) {
                        $r = $ldapfonctions->addAdminGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "add_admin by $adm : group : $cn, user : $u ");
                        }
                        else {
                            syslog(LOG_ERR, "LDAP ERROR : add_admin by $adm : group : $cn, user : $u ");
                        }
                    }
                    else {
                        $r = $ldapfonctions->delAdminGroup($dn_group, array($u));
                        if ($r) {
                            // Log modif
                            syslog(LOG_INFO, "del_admin by $adm : group : $cn, user : $u ");
                        }
                        else {
                            syslog(LOG_ERR, "LDAP ERROR : del_admin by $adm : group : $cn, user : $u ");
                        }
                    }
                }
            }
            // Ferme fichier de log
            closelog();
            
            $this->get('session')->getFlashBag()->add('flash-notice', 'Les modifications ont bien été enregistrées');       

            // Retour à l'affichage group_update
            return $this->redirect($this->generateUrl('group_update', array('cn'=>$cn, 'liste'=>$liste)));
        }

        return array(
            'group' => $group,
            'nb_membres' => $arUsers["count"],
            'nb_admins' => $nb_admins,
            'form' => $editForm->createView(),
            'liste' => $liste
            );

    }
    
    /** 
    * Affichage du document d'aide
    *
    * @Route("/help",name="help")
    */
    public function helpAction() {
        return $this->render('AmuGroupieBundle:Group:help.html.twig');
    }
    
    /** 
    * Affichage du document d'aide concernant les groupes privés
    *
    * @Route("/private_help",name="private_help")
    */
    public function privatehelpAction() {
        return $this->render('AmuGroupieBundle:Group:privatehelp.html.twig');
    }
    
}