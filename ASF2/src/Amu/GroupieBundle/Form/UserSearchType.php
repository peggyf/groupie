<?php

namespace Amu\GroupieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('uid', 'text', array('label' => 'Identifiant (uid)', 'required' => false))
             ->add('sn', 'text', array('label' => 'Nom', 'required' => false)) 
             ->add('exacte', 'checkbox', array('label' => 'Recherche exacte', 'required'  => false)
                     )   
             ->getForm(); 

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'data_class' =>  'Amu\GroupieBundle\Entity\User'
                                     ));
    }
    public function getName()
    {
        return 'usersearch';
    }
}