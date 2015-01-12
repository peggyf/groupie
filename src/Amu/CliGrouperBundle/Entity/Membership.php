<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Amu\CliGrouperBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;


class Membership {

  protected $groupname;
  protected $memberof;
  protected $adminof;
  

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
  
}