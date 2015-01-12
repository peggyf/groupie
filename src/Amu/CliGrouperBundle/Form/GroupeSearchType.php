<?php

namespace Amu\CliGrouperBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('cn', 'text', array(
                                         'required' => true
                                         ))
            ->getForm();

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'Amu\CliGrouperBundle\Entity\Groupe')
                               );
    }
    public function getName()
    {
        return 'groupesearch';
    }
}