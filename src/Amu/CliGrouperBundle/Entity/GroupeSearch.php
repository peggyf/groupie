<?php

namespace Amu\CliGrouperBundle\Entity;


use Symfony\Component\Validator\Constraints as Assert;


class GroupeSearch {

  protected $cn;

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
  * Get cn
 */
 public function getCn()
 {
    return($this->cn);
 }
  
}