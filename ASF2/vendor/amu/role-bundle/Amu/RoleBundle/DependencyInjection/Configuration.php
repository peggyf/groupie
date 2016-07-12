<?php

namespace Amu\RoleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 * To learn more see:
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('amu_role')
        ->children()
            ->arrayNode('roles')
                ->info("Tableaux de définition des ROLES personnalisés (session,ldap)...")
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode("name")->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode("type")->isRequired()->cannotBeEmpty()
                            ->validate()
                               ->ifNotInArray(array("session","ldap","ldap2","bdd","ip"))
                               ->thenInvalid('%s is not a valid type ["session","ldap","ldap2","bdd","ip"].')
                            ->end()
                        ->end()
                        ->scalarNode("link")->isRequired()->cannotBeEmpty()->end()
                        ->variableNode("values")->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode("filter")->end()
                        ->scalarNode("dn")->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('attributes')
                ->isRequired()
                ->children()
                    ->booleanNode('into_session')
                        ->defaultFalse()
                        ->info("Enregistre les Attributes dans la SESSION...")
                        ->example('(true/false, false par défaut)')
                        ->isRequired()
                        ->end()
                    ->variableNode('session_prefix_vars')
                        ->defaultValue("_attributes_ldap.")
                        ->info("Prefixe des varaibles de session ;".
                                "Utilisé uniquement si 'into_session' est positionnée à 'true'...")
                        ->example('Exemple de valeurs: "_ldap." ("" par défaut)')
                        ->isRequired()
                        ->end()
                    ->arrayNode('list')
                        ->treatNullLike(array())
                        ->prototype('scalar')->end()
                        ->defaultValue(array('uid','supannCivilite','cn','sn','givenName','displayName','mail'))
                        ->info("Tableau qui décrit la liste des attributs ldap ".
                                "qui seront liés aux Attributes de l'user symfony autentifié...")
                        ->example("Exemple de valeurs (par défauts) :  ".
                                "list: ['uid','supannCivilite','cn','sn','givenName','displayName','mail']")
                        ->isRequired()
                        ->end()
                ->end()
            ->end()
                
            ->arrayNode('networks')
                ->info("Tableaux de définition des Réseaux...")
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')
                            ->info("Nom interne du réseau défini.")
                            ->isRequired()->cannotBeEmpty()->end()
                        ->variableNode('plage')
                            ->info("Tableau des valeurs : plages/adresses IP constituant le réseau.")
                            ->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('developers')
                ->info("Tableaux de définition des Développeurs...")
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('ip')->isRequired()->end()
                        ->scalarNode('uid')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
            
        ->end();

        return $treeBuilder;
    }
}
