<?php

namespace Amu\CasBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('amu_cas')
          ->children()
                ->arrayNode('timeout')
                    ->children()
                        ->integerNode('idle')
                            ->defaultValue(3600)
                            ->info("Permet de spécifié le délai maximum accepté (en secondes), de l'inactivité d'une session utilisateur. Si ce temps est atteint le listener TimeoutListener se déclenchera...")
                            ->example("exemple de valeur: '3600' correspond à 1 heure...")
                            ->end()
                    ->end()
                ->end()
                ->arrayNode('force_logout')
                    ->children()
                        ->booleanNode("on_idle_timeout")
                              ->defaultFalse()
                              ->info("Force la deconnexion du CAS lors du dépassement de delai d'inactivité de la session utilisateur...")
                              ->example('(true/false, false par défaut)')
                              ->end()
                        ->booleanNode("on_session_timeout")
                              ->defaultFalse()
                              ->info("Force la deconnexion du CAS lors d'une expiration de session...")
                              ->example('(true/false, false par défaut)')
                              ->end()
                    ->end()
                ->end()
          ->end();

        return $treeBuilder;
    }
}
