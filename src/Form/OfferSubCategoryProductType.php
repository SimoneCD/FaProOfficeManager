<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferSubCategoryProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $products = $options['data'];
        /** @var OfferSubCategory $subCategory */
        $subCategory = $options['data'];
        $builder
            ->add('serviceText', TextType::class, [
                'label' => 'o.headerLine.right',
                'label_attr' => ['class' => 'col-sm-5 col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('serviceTitle', TextType::class, [
                'label' => 'o.headerLine.left',
                'label_attr' => ['class' => 'col-sm-5 col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('keyValueSubCategoryData', CollectionType::class, [
                'label_attr' => ['class' => 'col-sm-5 col-form-label'],
                'label' => 'o.data',
                'entry_type' => KeyValueType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('products', null, [
                'label' => 'w.products',
                'label_attr' => ['class' => 'col-sm-5 col-form-label'],
                'choice_label' => function (Product $product) {
                    return $product->getName().' ('.$product->getId().' | '.$product->getProductNumber().') '.$product->getPrice().' €';
                },
                'group_by' => function (Product $product, $key, $value) {
                    return $product->getProductSubCategory()->getCategory()->getName().' | '.$product->getProductSubCategory()->getName();
                },
                'attr' => ['class' => 'form-control', 'style' => 'height:400px'],
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($subCategory) {
                    $cat = $subCategory;
                    $cc = $cat->getProductSubCategory();
                    $qb = $er->createQueryBuilder('p');
                    if ($cc->getCategory() instanceof ProductCategory) {
                        $qb->join(ProductSubCategory::class, 'c', Join::WITH, 'p.productSubCategory = c.id')
                            ->where($qb->expr()->orX(
                                $qb->expr()->eq('p.productCategory', $cc->getCategory()->getId()),
                                $qb->expr()->eq('c.global', 1)
                            ))
                            ->andWhere('c.mainProduct = :main or (c.mainProduct != :main  and p.productCategory = :cat)')
                            ->setParameter(':main', false)
                            ->setParameter(':cat', $cc->getCategory()->getId())
                            ->orderBy('p.name');

                        return $qb;
                    }
                    return $qb->orderBy('p.name');
                },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferSubCategory::class,
        ]);
    }
}
