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
use Amu\CliGrouperBundle\Form\UserType;
use Amu\CliGrouperBundle\Entity\UserSearch;
use Amu\CliGrouperBundle\Form\UserSearchType;

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
        //return array('form' => $form->createView());
        
        return $this->render('AmuCliGrouperBundle:User:usersearch.html.twig', array('form' => $form->createView()));
        
    }

  /**
  * Affichage d'un utilisateur
  *
  * @Route("/user_edit/{uid}", name="user_edit")
  * @Template()
  */
  public function usereditAction($uid)
  {
      $user = new User();

      $form = $this->createForm(new UserType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $uid)),
            'method' => 'POST'));

      return array(
            'user'      => $user,
            'form'   => $form->createView(),
      );
    }

    /**
     * Edite un utilisateur
     *
     * @Route("/user_update/{uid}", name="user_update")
     * @Template("AmuCliGrouperBundle:User:edit.html.twig")
     */
    public function userupdateAction(Request $request, $uid)
    {
        $user = new User();
        $arUserInfos=$this->getLdap()->arUserInfos($uid, array('uid', 'displayname', 'mail', 'telephonenumber', 'memberof'));

        $user->setUid($uid);
        $user->setDisplayname($arUserInfos[0]['displayname'][0]);
        $user->setMail($arUserInfos[0]['mail'][0]);
        $user->setTel($arUserInfos[0]['telephonenumber'][0]);
        $user->setMemberof($arUserInfos[0]['memberof'][0]);

        $form = $this->createForm(new UserType(), $user, array(
            'action' => $this->generateUrl('user_update', array('uid' => $uid)),
            'method' => 'POST'));
        
        $form->handleRequest($request);

        if ($form->isValid()) {

        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $form->createView());
    }
}
