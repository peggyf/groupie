<?php

namespace Amu\CasBundle\Security\Authentication\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
//use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Role\Role;

class PreUserProvider implements UserProviderInterface
{
    /**
   * Charge les Roles en fonction des 'rules' definis dans le parametre multivalues 'roles_manager'
   * @param string $login le login de l'user en cours
   * @return User
   */
  public function loadUserByUsername($login)
  {
      return new User($login, "", array("ROLES_CAS_AUTHENTIFIED", "ROLE_USER"));
  }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $user; //$this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }

  /**
   * Adds a new User to the provider.
   *
   * @param UserInterface $user A UserInterface instance
   *
   * @throws \LogicException
   */
  public function createUser(UserInterface $user)
  {
  }
}
