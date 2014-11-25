<?php

namespace Amu\CliGrouperBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;


class Groupe {

  protected $cn;
  protected $description;
  protected $member;
  protected $amugroupadmin;
  protected $amugroupfilter;

 /**
  * Set cn
  *
  * @param string $cn
 */
 public function setCn($cn)
 {
    $this->cn = $cn;
 }
 /**
  * Set description
  *
  * @param string $description
 */
 public function setDescription($desc)
 {
    $this->description = $desc;
 }
 /**
  * Set amugroupfilter
  *
  * @param string $amugroupfilter
 */
 public function setAmugroupfilter($amugroupfilter)
 {
    $this->amugroupfilter = $amugroupfilter;
 } 
 /**
  * Set member
  *
  * @param string $member
 */
 public function setMember($member)
 {
    $this->member = $member;
 } 
 /**
  * Set amugroupadmin
  *
  * @param string $amugroupadmin
 */
 public function setAmugroupadmin($amugroupadmin)
 {
    $this->amugroupadmin = $amugroupadmin;
 } 

 /**
  * Set cn
  *
  * @param string $cn
 */
 public function getCn()
 {
    return($this->cn);
 }
 /**
  * Set description
  *
  * @param string $description
 */
 public function getDescription()
 {
    return ($this->description);
 }
 /**
  * Set amugroupfilter
  *
  * @param string $amugroupfilter
 */
 public function getAmugroupfilter()
 {
    return ($this->amugroupfilter);
 } 

 /**
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function infosGroupeLdap() 
 {
   $infogroupe = array();
    
   $addgroup['cn'] = $this->cn;
   $addgroup['description'] = $this->description;
   if ($this->amugroupfilter!="")
   {
       $addgroup['amugroupfilter'] = $this->amugroupfilter;
   }
      
   $addgroup['objectClass'][0] = "top";
   $addgroup['objectClass'][1] = "groupOfNames";
   $addgroup['objectClass'][2] = "AMUGroup";
   
   $infogroupe['dn'] = "cn=".$this->cn.", ou=groups, dc=univ-amu, dc=fr";
   $infogroupe['infos'] = $addgroup;
   
    return($infogroupe);
    
 }
  
}