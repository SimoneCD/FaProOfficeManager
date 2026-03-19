<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\ProductSubCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferContextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Offer $offer */
        $offer = $options['data'];
        $builder
            ->add('wallboxProduct', EntityType::class, [
                'label' => 'product.offer.wallboxProduct',
                'class' => Product::class,
                'required' => false,
                'attr' => ['class' => 'form-control form_alert form_done'],
                'label_html' => true,
                'label_attr' => ['class' => 'bmd-label-floating'],
                'choice_label' => function (Product $psc) {
                    $title = $psc->getName().' | '.$psc->getDescription().'';

                    return $title;
                },
                'query_builder' => function (EntityRepository $er) use ($offer) {
                    $cat = !empty($offer->getSubCategory()) ? $offer->getSubCategory()->getProductSubCategory() : null;

                    $qb = $er->createQueryBuilder('p')
                        ->leftJoin('p.productSubCategory', 'sc')
                        ->where('sc.mainProduct = :main')
                        ->andWhere('p.productSubCategory = :category')
                        ->setParameter('category', $cat)
                        ->setParameter('main', true)
                    ;

                    return $qb;
                },
                ])
            ->add('amount', null, [
                'label' => 'w.amount',
//                'label_attr' => ['class' => 'col-sm-7 col-form-label'],
                'required' => false,
            ])
            ->add('kw', null, ['required' => false])
            ->add('installAmount', null, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'csrf_protection' => true,
            'allow_extra_fields' => true,
        ]);
    }
}
