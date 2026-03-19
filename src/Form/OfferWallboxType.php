<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferWallboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Offer $offer */
        $offer = $options['data'];
        $builder
            ->add('wallboxProduct', EntityType::class, ['label' => 'product.offer.wallboxProduct',
                'class' => Product::class,
                'attr' => ['class' => 'form-control form_done'],
                'label_attr' => ['class' => 'bmd-label-floating'],
                'required' => false,
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
                'attr' => ['class' => 'form-control form_done_d text-right', 'style' => 'max-width:60px;'],
                'label_attr' => ['class' => 'bmd-label-floating no_auto'],
                'empty_data' => 0,
            ])
            ->add('wallboxPrice', MoneyType::class, [
                'label' => 'Provision',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'bmd-label-floating no_auto'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
