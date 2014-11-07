<?php

namespace Amu\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="APP_USERS")
 */
class AmuUser implements UserInterface {

  /**
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;
  private $username;
  private $password;
  private $roles;

  function __construct($id, $username, $password, $roles) {
    $this->id = $id;
    $this->username = $username;
    $this->password = $password;
    $this->roles = $roles;
  }

//  function __construct() {
//
//  }

  public function equals(UserInterface $user) {
    
  }

  public function eraseCredentials() {
    
  }

  public function getSalt() {
    
  }

  public function getId() {
    return $this->id;
  }

  public function getRoles() {
    return $this->roles;
  }

  public function setRole($role) {
    $this->role = $role;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getPassword() {
    return $this->password;
  }

}