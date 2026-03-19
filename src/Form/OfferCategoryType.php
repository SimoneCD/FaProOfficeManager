<?php

namespace App\Form;

use App\Entity\OfferCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'oc.new',
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
            ])
            ->add('icon', null, [
                'label' => 'w.symbol',
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
            ])
            ->add('productCategory', null, [
                'label_html' => true,
                'label' => 'osc.productCategoryAssign',
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'attr' => ['class' => 'ml-4'],
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferCategory::class,
        ]);
    }
}
