<?php

namespace Amu\CasBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Amu\CasBundle\DependencyInjection\Security\Factory\CasFactory;

class AmuCasBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new CasFactory());
    }
}
