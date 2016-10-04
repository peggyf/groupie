<?php

namespace Amu\CasBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AmuCasExtension extends Extension
{
    /**
   * {@inheritDoc}
   */
  public function load(array $configs, ContainerBuilder $container)
  {
      $configuration = new Configuration();
      $config = $this->processConfiguration($configuration, $configs);
        
      $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
      $loader->load('services.yml');

      if (!isset($config['timeout'])) {
          throw new \InvalidArgumentException('Les paramètres sous "amu_cas: timeout: ..." ne sont pas définis.');
      }
      if (!isset($config['force_logout'])) {
          throw new \InvalidArgumentException('Les paramètres sous "amu_cas: force_logout: ..." ne sont pas définis.');
      }

      $container->setParameter('amu.cas.timeout.idle', $config['timeout']['idle']);
      $container->setParameter('amu.cas.force_logout.on_session_timeout', $config['force_logout']['on_session_timeout']);
      $container->setParameter('amu.cas.force_logout.on_idle_timeout', $config['force_logout']['on_idle_timeout']);
  }
}
