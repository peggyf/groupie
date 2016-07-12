<?php

/* * *************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 * ************************************************************************* */

namespace Amu\CasBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

/**
 * Class for wait the authentication event and call the CAS Api to throw the authentication process
 *
 * @category Authentication
 * @package  GorgCasBundle
 * @author   Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @license  GNU General Public License
 *
 * @see Patch MU => Version maj migration CAS du 20/10/2015
 *
 */
class CasListener extends AbstractAuthenticationListener
{
    private $container=null;

    /**
     * {@inheritdoc}
     */
    //public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    public function __construct(TokenStorage $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, $container=null)
    {
        parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, $options, $logger, $dispatcher);
        $this->container=$container;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        /* Call CAS API to do authentication */
        \phpCAS::client($this->options['cas_protocol'], $this->options['cas_server'], $this->options['cas_port'], $this->options['cas_path'], false);

        if ($this->options['ca_cert_path']) {
            \phpCAS::setCasServerCACert($this->options['ca_cert_path']);
        } else {
            \phpCAS::setNoCasServerValidation();
        }
        \phpCAS::forceAuthentication();

        $casAttributes = \phpCAS::getAttributes();
        $credentials = array();

//      if (count($casAttributes)) {
//          if ($this->options['cas_mapping_attribute']) {
//              if (!$casAttributes[$this->options['cas_mapping_attribute']]) {
//                  return;
//              }
//              $user = $casAttributes[$this->options['cas_mapping_attribute']];
//          } else {
//              $user = $casAttributes[\phpCAS::getUser()];
//              $credentials = array('ROLE_USER');
//          }
//      }

        $user = \phpCAS::getUser();
        $request->getSession()->set('phpCAS_user', $user);
        if($user){
            $credentials = array('ROLE_USER','ROLE_CAS_AUTHENTIFIED');
        }

        $newToken=new PreAuthenticatedToken(new User($user,"",$credentials), $credentials, $this->providerKey);
        //$newToken = new UsernamePasswordToken($user, null, $this->providerKey, $credentials);

        $Attributes=array();
        if (null !== $this->container) {
            try {
                $rolesService=$this->container->get("amu.roles");
                $Attributes=$rolesService->getAttributes($user);
            } catch (Exception $ex) {
                $Attributes["error"]="Le service 'amu.roles' n'est pas disponible !";
                if (null !== $this->logger) {
                    $this->logger->warm(sprintf("Le service 'amu.roles' n'étant pas disponible, les attributes de l'user %s ne seront pas positionner...", $user));
                }
            }
        } else {
            $Attributes["error"]="Le container n'est pas initialisé !";
            if (null !== $this->logger) {
                $this->logger->warm(sprintf("Le container n'est pas initialisé : les attributes de l'user %s ne seront pas positionner...", $user));
            }
        }

        // merge des $Attributes (ROLES) + $casFormatedAttributes => $casFormatedAttributes
        $casFormatedAttributes=array();
        if (count($casAttributes)) {
            foreach($casAttributes as $key=>$value){
                $casFormatedAttributes["cas_".$key]=$value;
            }
        }
        $fullAttributes=array_merge($Attributes,$casFormatedAttributes);
        $newToken->setAttributes($fullAttributes);

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication success: %s', $user));
            //$this->logger->info(sprintf('Attributes : %s', print_r($fullAttributes,true)));
        }

        $authToken=$newToken;
        try {
            $authToken=$this->authenticationManager->authenticate($newToken);
        } catch (Exception $exc) {
        }

        return $authToken;
    }
}