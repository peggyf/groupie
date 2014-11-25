<?php
namespace Amu\CliGrouperBundle\Form\Type;

use Symfony\Component\Form\ButtonTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

class CreateButtonType extends ButtonType implements ButtonTypeInterface
{
    public function getParent()
    {
        return 'button';
    }

    public function getName()
    {
        return 'createButton';
    }
}