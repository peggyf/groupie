<?php

namespace Amu\GroupieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('cn', 'text', array('label' => 'cn ',
                                         'required' => true
                                         ))
            ->add('flag', 'hidden', array(
                  'data' => '0'))
            ->getForm();

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'Amu\GroupieBundle\Entity\Group')
                               );
    }
    public function getName()
    {
        return 'groupsearch';
    }
}