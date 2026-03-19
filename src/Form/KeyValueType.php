<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\KeyValueSubCategoryData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeyValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keyName', null, [
                'label' => false,
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'w.name'],
            ])
            ->add('keyValue', null, [
                'label' => false,
                'label_attr' => ['class' => 'form-label'],
//                'row_attr' => ['class' => 'row'],
                // 'help' => 'help',
                'attr' => ['class' => 'form-control', 'placeholder' => 'w.value'],
                'required' => false,
            ])
            ->add('keySort', NumberType::class, [
                'label' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'w.sort'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KeyValueSubCategoryData::class,
        ]);
    }
}
