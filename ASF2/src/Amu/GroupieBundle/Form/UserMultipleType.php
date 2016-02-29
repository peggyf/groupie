<?php

namespace Amu\CliGrouperBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserMultipleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('multiple', 'textarea', array('label' => 'Liste d\'identifiants ou de mails', 'required' => false))    
             ->getForm(); 

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                        'data_class' =>  null
                                     ));
    }
    public function getName()
    {
        return 'usermultiple';
    }
}