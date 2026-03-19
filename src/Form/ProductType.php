<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductSubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['data'];
        $kw = 0;
        if ($product instanceof Product) {
            $kw = $product->getKw();
        }
        $builder
            ->add('name', TextType::class, [
                'label' => 'w.productName',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('productSubCategory', EntityType::class, [
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-form-label'],
                'label' => 'p.subCategory',
                'class' => ProductSubCategory::class,
                'group_by' => function ($choice, $key, $value) {
                    /* @var ProductSubCategory $choice */
                    return $choice->getCategory();
                },
            ])
            ->add('workerProduct', ChoiceType::class, [
                'label' => 'Leistungsprodukt(Anfahrt...)',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'w.no' => 0,
                    'w.yes' => 1,
                ],
            ])
            ->add('productNumber', TextType::class, [
                'label' => 'w.posShort',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'w.description',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('einheit', TextType::class, [
                'label' => 'w.measure',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('kw', NumberType::class, [
                'label' => 'f.label.kwName',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-form-label'],
                'required' => false,
                'data' => $kw,
                'empty_data' => 0,
            ])
            ->add('valueName', TextType::class, [
                'label' => 'f.label.kwNameShort',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('shopLink', TextType::class, [
                'label' => 'f.label.shop',
                'label_attr' => ['class' => 'col-12 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('productImage', FileType::class, [
                'label' => 'p.image',
                'label_attr' => ['class' => 'col-12 col-form-label'],
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the file
                // every time you edit the Product details
                'required' => false,
                'attr' => ['class' => 'form-control form-file-upload'],
                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Bitte das Bild im Format jpg oder png hochladen!',
                    ]),
                ],
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'p.color',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-form-label'],
                'choices' => array_flip(Product::COLORS),
                'empty_data' => '#ffffff',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'w.price',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('ean', TextType::class, [
                'label' => 'w.ean',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('ekPrice', MoneyType::class, [
                'label' => 'w.ekprice',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('stock', NumberType::class, [
                'rounding_mode' => 2,
                'label' => 'w.stock',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('inStock', NumberType::class, [
                'rounding_mode' => 2,
                'label' => 'm.products.inStock',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('salesInfo', TextareaType::class, [
                'label' => 'Beschreibung für den Funnel',
                'label_attr' => ['class' => 'col-12 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('manufacturerName', TextType::class, [
                'label' => 'p.manufacturer.name',
                'label_attr' => ['class' => 'col-12 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('manufacturerInfo', TextareaType::class, [
                'label' => 'p.manufacturer.title',
                'label_attr' => ['class' => 'col-12 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('manufacturerWarranty', TextType::class, [
                'label' => 'p.manufacturer.garanty',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('deliveryTime', TextType::class, [
                'label' => 'p.delivery',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('products', null, [
                'label' => 'p.products.order',
                'multiple' => true,
                'label_attr' => ['class' => 'col-12 col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'min-height:300px'],
                'required' => false,
            ])
            ->add('certificats', null, [
                'label' => 'w.certificats',
                'label_attr' => ['class' => 'col-form-label'],
                'allow_file_upload' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => false,
        ]);
    }
}
