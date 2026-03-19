<?php

namespace App\Form;

use App\Entity\ActionLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => array_combine(array_keys(ActionLog::TYPE_CHOICES), array_column(ActionLog::TYPE_CHOICES, 'label')),
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'radio-as-buttons'],
                'choice_attr' => function ($choice, $key, $value) {
                    // dd($choice, $key, $value); // This will output the values for debugging
                    return ['data-icon' => ActionLog::TYPE_CHOICES[$key]['icon']];
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'choices' => array_combine(array_keys(ActionLog::TYPE_CHOICES), array_column(ActionLog::TYPE_CHOICES, 'label')),
            'data_class' => ActionLog::class,
        ]);
    }
}
