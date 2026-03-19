<?php

namespace App\Form;

use App\Entity\ProductSubCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSubCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'col-3 col-form-label'],
            ])
            ->add('mainProduct', null, [
                'label' => 'p.offer',
                'label_attr' => ['class' => 'col-3 col-form-label'],
            ])
            ->add('global', null, [
                'label' => 'p.help.allwaisIn',
                'label_attr' => ['class' => 'col-3 col-form-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSubCategory::class,
        ]);
    }
}
