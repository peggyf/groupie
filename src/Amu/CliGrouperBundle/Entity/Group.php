<?php

namespace Amu\CliGrouperBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;


class Group {

  protected $cn;
  protected $etages;
  protected $nbetages;
  protected $lastopen;
  protected $lastnbetages;
  protected $description;
  protected $members;
  protected $amugroupadmin;
  protected $amugroupfilter;
  protected $droits;
  
public function __construct()
  {
      $this->members = new ArrayCollection();
  }  

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
  * Set members
  *
 */
 public function setMembers(ArrayCollection $members)
 {
    $this->members = $members;
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

 public function setDroits($droits)
 {
    $this->droits = $droits;
 } 
 
 public function setEtages($etages)
 {
    $this->etages = $etages;
 } 
 
 public function setNbetages($nbetages)
 {
    $this->nbetages = $nbetages;
 }
 
 public function setLastopen($lastopen)
 {
    $this->lastopen = $lastopen;
 }
 
 public function setLastnbetages($lastnbetages)
 {
    $this->lastnbetages = $lastnbetages;
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

 public function getMembers()
 {
    return($this->members);
 }
 
 public function getDroits()
 {
    return($this->droits);
 }
 
 public function getEtages()
 {
    return($this->etages);
 }
 
 public function getNbetages()
 {
    return($this->nbetages);
 }
 
 public function getLastopen()
 {
    return($this->lastopen);
 }
 
 public function getLastnbetages()
 {
    return($this->lastnbetages);
 }
 
  /**

 * @return  \Amu\AppBundle\Service\Ldap

 */

 public function infosGroupeLdap() 
 {
   $infogroupe = array();
   $addgroup = array();

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

  
};