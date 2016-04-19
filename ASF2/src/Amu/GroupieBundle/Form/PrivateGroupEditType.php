<?php

namespace Amu\GroupieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PrivateGroupEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
                
        $builder
                 ->add('members', 'collection', array('type' => new PrivateMemberType())
                 );

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'data_class' => 'Amu\GroupieBundle\Entity\Group',
                                     'attr' => ['id' => 'privategroupedit']
                                     ));
    }
    public function getName()
    {
        return 'privategroupedit';
    }
}