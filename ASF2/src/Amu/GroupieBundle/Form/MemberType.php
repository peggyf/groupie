<?php

namespace Amu\GroupieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
  
class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('member', 'checkbox', array(
                      'required'  => false)
                     )
                ->add('admin', 'checkbox', array(
                      'required'  => false)
                     );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Amu\Groupie\Entity\Member',
        ));
    }

    public function getName()
    {
        return 'member';
    }
}