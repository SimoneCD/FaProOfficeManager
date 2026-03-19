<?php

namespace App\Form;

use App\Entity\IndexStates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndexStatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('state')
            ->add('actionFirst')
            ->add('actionLast')
            ->add('help')
            ->add('autoMoveByTime')
            ->add('document')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IndexStates::class,
        ]);
    }
}
