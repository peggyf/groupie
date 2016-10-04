<?php

namespace Amu\RoleBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AmuRoleExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        foreach (array('roles', 'networks', 'developers') as $needParams) {
            if (!isset($config[$needParams])) {
                throw new \InvalidArgumentException('Les paramètres obligatoires sous "amu_role: ' . $needParams . ': ..." ne sont pas définis.');
            }
        }

        $container->setParameter('amu.roles.attributes', $config['attributes']["list"]);
        $container->setParameter('amu.roles.attributes.into_session', $config['attributes']["into_session"]);
        $container->setParameter('amu.roles.attributes.session_prefix_vars', $config['attributes']["session_prefix_vars"]);

        $container->setParameter('amu.roles.custom', $config['roles']);
        $container->setParameter('amu.roles.networks', $config['networks']);
        $container->setParameter('amu.roles.developers', $config['developers']);
    }
}
