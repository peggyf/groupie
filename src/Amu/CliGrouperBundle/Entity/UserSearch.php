<?php

namespace Amu\CliGrouperBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;


class UserSearch {

  protected $uid;

 /**
  * Set sn
  *
  * @param string $sn
 */
 public function setUid($uid)
 {
    $this->uid = $uid;
 }
 
 /**
  * Get sn
 */
 public function getUid()
 {
    return($this->uid);
 }
  
}