<?php
/**
 * Ce fichier appartient au bundle LdapBundle
 *
 * @author Arnaud Salvucci <arnaud.salvucci@univ-amu.fr>
 */
namespace Amu\LdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('amu_ldap');
        
        $rootNode
            ->children()
                ->scalarNode('default_profil')
                    ->defaultValue('default')
                ->end()
                ->arrayNode('profils')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('servers')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('host')->isRequired()->end()
                                        ->integerNode('port')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('relative_dn')->isRequired()->end()
                            ->scalarNode('password')->isRequired()->end()
                            ->scalarNode('base_dn')->defaultValue('ou=people,dc=univ-amu,dc=fr')->end()
                            ->scalarNode('objectclass_group')->defaultValue('groupOfNames,AMUGroup,top')->end()
                            ->integerNode('network_timeout')->isRequired()->end()
                            ->integerNode('protocol_version')->isRequired()->end()
                            ->integerNode('referrals')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
                
        return $treeBuilder;
    }
}
