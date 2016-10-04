<?php

/* * *************************************************************************
 * Copyright (C) 1999-2011 Gadz.org                                        *
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

namespace Amu\CasBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

/**
 * Create the factory for build security listner and provider Cas Authentication
 *
 * @category Authentication
 * @package  GorgCasBundle
 * @author   Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @license  GNU General Public License
 */
class CasFactory extends AbstractFactory
{
    /**
    * {@inheritdoc}
    */
    public function getPosition()
    {
          return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
          return 'cas';
    }

  /**
   * {@inheritdoc}
   */
    protected function isRememberMeAware($config)
    {
          return false;
    }

  /**
   * {@inheritdoc}
   */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
          $this->createLogoutSuccessHandler($container, $config);
          return parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);
    }

    public function __construct()
    {
        $this->addOption('cas_allow_anonymous', false);
        $this->addOption('cas_server', '_cas_serveur');
        $this->addOption('cas_port', 443);
        $this->addOption('cas_path', '/cas/');
        $this->addOption('ca_cert_path', '');
        $this->addOption('cas_protocol', 'S1');
        $this->addOption('cas_mapping_attribute', '###CAS_USER_NAME###');
        $this->addOption('cas_logout', '');
    }

  /**
   * {@inheritdoc}
   */
    public function addConfiguration(NodeDefinition $node)
    {
          parent::addConfiguration($node);

          /* Load the configuration */
          $node
                  ->children()
                  ->booleanNode("cas_allow_anonymous")->defaultFalse()->end()
                  ->scalarNode('cas_server')->end()
                  ->variableNode('cas_port')->end()
                  ->scalarNode('cas_path')->end()
                  ->scalarNode('ca_cert_path')->end()
                  ->scalarNode('cas_protocol')->defaultValue('S1')->end() /* S1 for SAML_VERSION_1, 1.0 for CAS 1, 2.0 for CAS 2.0, See CAS.php for more information */
                  ->scalarNode('cas_mapping_attribute')->defaultValue("###CAS_USER_NAME###")->end() /* default value reprensent the username returned by cas (not an attribute) */
                  ->scalarNode('check_path')->end()
                  ->scalarNode('cas_logout')->end()
                  ->end()
          ;
    }

    protected function getListenerId()
    {
        return 'cas.security.authentication.listener';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication.provider.pre_authenticated.' . $id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('security.authentication.provider.pre_authenticated'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->addArgument($id)
        ;
        return $provider;
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        $listener = $container->getDefinition($listenerId);
        $listener->addArgument(new Reference("service_container")); // 11
    
        foreach ($config as $key => $value) {
            $container->setParameter('amu.cas.config.' . $key, $value);
        }

        return $listenerId;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'cas.security.authentication.listener.entry_point.' . $id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('cas.security.authentication.cas_entry_point'))
            ->addArgument(new Reference('security.http_utils'))
            ->addArgument($config['check_path'])
            ->addArgument(false)
        ;

        return $entryPointId;
    }

    protected function createLogoutSuccessHandler(ContainerBuilder $container, $config)
    {
        $templateHandler = 'cas.security.handler.logout';
        $realHandler = 'security.logout.success_handler';

    // dont know if this is the right way, but it works
        $container
            ->setDefinition($realHandler, new DefinitionDecorator($templateHandler))
            ->replaceArgument(0, $config)
        ;
    }
}
