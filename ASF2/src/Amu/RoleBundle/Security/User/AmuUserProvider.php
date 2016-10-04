<?php

namespace Amu\RoleBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\User\User;

class AmuUserProvider implements UserProviderInterface
{

    private $container = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Amu\RoleBundle\Service\RolesManager;
     */
    private function getRoleManager()
    {
        return $this->container->get('amu.roles');
    }

    /**
     * Charge les Roles en fonction des 'rules' definis dans le parametre multivalues 'roles_manager'
     * @param string $login le login de l'user en cours
     * @return User
     */
    public function loadUserByUsername($login)
    {

        return new User($login, "", $this->getRoleManager()->getRoles($login));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}
