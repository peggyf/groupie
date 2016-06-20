<?php

namespace Amu\GroupieBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('amu_groupie');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->arrayNode('logs')
                    ->isRequired()
                    ->children()
                        ->scalarNode('facility')->defaultValue('LOG_LOCAL0')->end()
                    ->end()
                ->end()

                ->arrayNode('users')
                    ->isRequired()
                    ->children()
                        ->scalarNode('people_branch')->defaultValue('ou=people')->end()
                        ->scalarNode('uid')->defaultValue('uid')->end()
                        ->scalarNode('name')->defaultValue('sn')->end()
                        ->scalarNode('givenname')->defaultValue('givenname')->end()
                        ->scalarNode('displayname')->defaultValue('displayname')->end()
                        ->scalarNode('mail')->defaultValue('mail')->end()
                        ->scalarNode('tel')->defaultValue('telephonenumber')->end()
                        ->scalarNode('comp')->isRequired()->end()
                        ->scalarNode('aff')->isRequired()->end()
                        ->scalarNode('primaff')->isRequired()->end()
                        ->scalarNode('campus')->isRequired()->end()
                        ->scalarNode('site')->isRequired()->end()
                        ->scalarNode('filter')->defaultValue('(!(edupersonprimaryaffiliation=student))')->end()
                    ->end()
                ->end()

                ->arrayNode('groups')
                    ->isRequired()
                    ->children()
                        ->scalarNode('object_class')->defaultValue('groupofnames')->end()
                        ->scalarNode('group_branch')->defaultValue('ou=groups')->end()
                        ->scalarNode('cn')->defaultValue('cn')->end()
                        ->scalarNode('desc')->defaultValue('description')->end()
                        ->scalarNode('member')->defaultValue('member')->end()
                        ->scalarNode('memberof')->defaultValue('memberof')->end()
                        ->scalarNode('groupfilter')->isRequired()->end()
                        ->scalarNode('groupadmin')->isRequired()->end()
                    ->end()
                ->end()

                ->arrayNode('private')
                    ->isRequired()
                    ->children()
                        ->scalarNode('private_branch')->isRequired()->end()
                        ->scalarNode('prefix')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
