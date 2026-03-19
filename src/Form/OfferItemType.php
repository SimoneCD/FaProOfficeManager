<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferItem;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', null, ['label' => 'w.count'])
            ->add('item', EntityType::class, ['label' => 'o.productAdd',
                'class' => Product::class,
                'attr' => ['class' => 'form-control form_done'],
                'label_attr' => ['class' => 'bmd-label-floating'],
                    ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferItem::class,
        ]);
    }
}
