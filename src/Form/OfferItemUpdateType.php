<?php

namespace App\Form;

use App\Entity\OfferItem;
use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferItemUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('item', EntityType::class, ['label' => 'w.product',
                    'class' => Product::class,
                    'attr' => ['class' => 'form-control form_done'],
                    'label_attr' => ['class' => 'bmd-label-floating'],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('w')
                            ->where('w.productType != :type')
                            ->andWhere('w.productType != :typeOne')
                            ->andWhere('w.productType != :typeTwo')
                            ->andWhere('w.productType != :typePv')
                            ->setParameter('type', 'wallbox')
                            ->setParameter('typePv', 'pv')
                            ->setParameter('typeOne', 'ladestation')
                            ->setParameter('typeTwo', 'schnell-ladestation');
                    }, ]
            )
            ->add('amount', null, [
                'label' => 'w.count',
            ])
            ->add('price', null, [
                'label' => 'w.price',
            ])
            ->add('description', null, ['required' => false, 'label' => 'w.description'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferItem::class,
        ]);
    }
}
