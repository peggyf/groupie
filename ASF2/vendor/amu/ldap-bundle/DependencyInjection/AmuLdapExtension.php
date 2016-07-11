<?php
/**
 * Ce fichier appartient au bundle LdapBundle
 *
 * @author Arnaud Salvucci <arnaud.salvucci@univ-amu.fr>
 */
namespace Amu\LdapBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AmuLdapExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        // Set parameters
        $defProfils = $config['default_profil'];
        if (!isset($config['profils'][$defProfils])) {
            throw new \InvalidArgumentException('Le profil par défaut n\'est pas défini.');
        }
        if (count($config['profils'][$defProfils]['servers']) < 1) {
            throw new \InvalidArgumentException('Au moins un profil de connection doit être défini.');
        }
        $container->setParameter('amu.ldap.default_profil', $config['default_profil']);
        $container->setParameter('amu.ldap.profils', $config['profils']);
    }
}
