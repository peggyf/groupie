<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Amu\GroupieBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;


class Membership {

  protected $groupname;
  protected $memberof;
  protected $adminof;
  protected $droits;
  

  /**
  * Set groupname
  *
  * @param string $groupname
 */
 public function setGroupname($groupname)
 {
    $this->groupname = $groupname;
 }
 
 /**
  * Set memberof
  *
  * @param bool $memberof
 */
 public function setMemberof($memberof)
 {
    $this->memberof = $memberof;
 }
 
 /**
  * Set adminof
  *
  * @param bool $adminof
 */
 public function setAdminof($adminof)
 {
    $this->adminof = $adminof;
 }

 public function setDroits($droits)
 {
    $this->droits = $droits;
 }
 /**
  * Get group name
  *
 */
 public function getGroupname()
 {
    return($this->groupname);
 }
 
 /**
  * Get memberof
  *
 */
 public function getMemberof()
 {
    return ($this->memberof);
 } 
 
 /**
  * Get adminof
  *
 */
 public function getAdminof()
 {
    return ($this->adminof);
 } 
 
  public function getDroits()
 {
    return ($this->droits);
 } 
}