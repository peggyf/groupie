<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace Amu\CliGrouperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AjaxController extends Controller
{
    private function getLdap() {
        return $this->get('CliGrouper.ldap');
    }
  
    /**
     * Retourne la liste des groupes du LDAP (autocomplétion)
     *
     * @Route("/ajax/groupcompletlist", name="ajax_groupcompletlist")
     *
     * @return string la liste des groupes au format json
     */
    public function GroupCompletListAction()
    {
    
        $request = $this->get('request');
        
        $term = $request->request->get('motcle');
                        
        if (strlen($term)<3) 
        {
            $json[] = array('label' => 'au moins 3 caractères ('.$term.')', 'value' => '');
            $response = new Response (json_encode($json));
            $response->headers->set('Content-Type','application/json');
            return $response;
        }
                                        
        $arData = array();                          
        $arData=$this->getLdap()->arDatasFilter(     
            "(&(objectClass=groupofNames)(cn=*".$term."*))",
            array("cn"));    
       
        $NbEnreg = $arData['count'];
        // on limite l'affichage à 20 groupes
        ($NbEnreg>20) ? $NbEnreg=20 : $NbEnreg;
            
        if ($NbEnreg == 0) {
            $arrayGroups[] = array('label' => '...');

        } else {
            for($Cpt=0;$Cpt < $NbEnreg ;$Cpt++)             
            {                
                $arrayGroups[$Cpt]['label']  = $arData[$Cpt]['cn'][0];
            }
        }

        $response = new Response (json_encode($arrayGroups));
        $response->headers->set('Content-Type','application/json');
        return $response;
        
    }
    
    /**
     * Retourne la liste des utilisateurs du LDAP (autocomplétion)
     *
     * @Route("/ajax/uidcompletlist", name="ajax_uidcompletlist")
     *
     * @return string la liste des uid des utilisateurs au format json
     */
    public function UidCompletListAction()
    {
    
        $request = $this->get('request');
        
        $term = $request->request->get('motcle');
                        
        if (strlen($term)<3) 
        {
            $json[] = array('label' => 'au moins 3 caractères ('.$term.')', 'value' => '');
            $response = new Response (json_encode($json));
            $response->headers->set('Content-Type','application/json');
            return $response;
        }
                                        
        $arData = array();                          
        $arData=$this->getLdap()->arDatasFilter(     
            "(uid=".$term."*)",
            array("uid"));    
       
        $NbEnreg = $arData['count'];
        // on limite l'affichage à 20 groupes
        ($NbEnreg>20) ? $NbEnreg=20 : $NbEnreg;
            
        if ($NbEnreg == 0) {
            $arrayGroups[] = array('label' => '...');

        } else {
            for($Cpt=0;$Cpt < $NbEnreg ;$Cpt++)             
            {                
                $arrayGroups[$Cpt]['label']  = $arData[$Cpt]['uid'][0];
            }
        }

        $response = new Response (json_encode($arrayGroups));
        $response->headers->set('Content-Type','application/json');
        return $response;
        
    }    
    
    /**
     * Retourne la liste des utilisateurs du LDAP (autocomplétion)
     *
     * @Route("/ajax/sncompletlist", name="ajax_sncompletlist")
     *
     * @return string la liste des sn des utilisateurs au format json
     */
    public function SnCompletListAction()
    {
    
        $request = $this->get('request');
        
        $term = $request->request->get('motcle');
                        
        if (strlen($term)<3) 
        {
            $json[] = array('label' => 'au moins 3 caractères ('.$term.')', 'value' => '');
            $response = new Response (json_encode($json));
            $response->headers->set('Content-Type','application/json');
            return $response;
        }
                                        
        $arData = array();                          
        $arData=$this->getLdap()->arDatasFilter(     
            "(sn=".$term."*)",
            array("sn", "givenname", "uid"));    
       
        $NbEnreg = $arData['count'];
        // on limite l'affichage à 20 groupes
        ($NbEnreg>20) ? $NbEnreg=20 : $NbEnreg;
            
        if ($NbEnreg == 0) {
            $arrayUsers[] = array('label' => '...');

        } else {
            for($Cpt=0;$Cpt < $NbEnreg ;$Cpt++)             
            {                
                $arrayUsers[$Cpt]['label']  = $arData[$Cpt]['sn'][0] ." ". $arData[$Cpt]['givenname'][0];
                $arrayUsers[$Cpt]['value']  = $arData[$Cpt]['sn'][0];
                $arrayUsers[$Cpt]['uid'] = $arData[$Cpt]['uid'][0];
            }
        }

        $response = new Response (json_encode($arrayUsers));
        $response->headers->set('Content-Type','application/json');
        return $response;
        
    }    
}