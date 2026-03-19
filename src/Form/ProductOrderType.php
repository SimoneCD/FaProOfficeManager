<?php

namespace App\Form;

use App\Entity\ProductOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'p.orderInfo',
                'required' => false,
                'label_attr' => ['class' => 'form-label col-4'],
            ])
            ->add('amount', null, [
                'label' => 'w.amount',
                'label_attr' => ['class' => 'form-label col-4'],
                'attr' => ['class' => 'text-right pl-3'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'w.singlePrice',
                'label_attr' => ['class' => 'form-label col-1 pl-0'],
                'attr' => ['class' => 'text-right pl-3'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOrder::class,
            'allow_extra_fields' => true,
        ]);
    }
}
