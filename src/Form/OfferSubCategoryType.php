<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class OfferSubCategoryType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OfferSubCategory $subCategory */
        $subCategory = $options['data'];
        $builder
            ->add('serviceText', TextType::class, [
                'label' => 'o.headerLine.right',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('serviceTitle', TextType::class, [
                'label' => 'o.headerLine.left',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('keyValueSubCategoryData', CollectionType::class, [
                'label_attr' => ['class' => 'col-sm-4 col-form-label'],
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
                    if ($cc instanceof ProductSubCategory and $cc->getCategory() instanceof ProductCategory) {
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
            ])
            ->add('category', null, [
                'label' => 'o.c.change',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('teamCategories', null, [
                'label' => 'o.c.teamCategories',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('requests', CollectionType::class, [
                'label' => 'Orginal Anträge die gestellt werden müssen',
                'entry_type' => RequestsType::class,
                // these options are passed to each "email" type
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'o.questionnatre',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('specifications', TextareaType::class, [
                'label' => 'o.settings.bau',
                'label_html' => true,
                'label_attr' => ['class' => 'col-sm-4 col-form-label'],
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'height:200px;resize: vertical;',
                ],
                'required' => false,
            ])->add('type', ChoiceType::class, [
                'label' => 'Typ',
                'attr' => ['class' => 'form-control'],
                'choices' => array_flip(OfferSubCategory::FUNNEL_TYPES),
                'required' => true,
            ])

            ->add('estimateMailText', TextareaType::class, [
                'label' => 'Kostenvoranschlag',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('estimateText', TextareaType::class, [
                'label' => 'Kostenvoranschlag',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'resize: vertical;'],
                'required' => false,
            ])
            ->add('mailText', TextareaType::class, [
                'label' => 'w.offer',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('offerText', TextareaType::class, [
                'label' => 'w.offer',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'resize: vertical;'],
                'required' => true,
            ])
            ->add('invoiceMailText', TextareaType::class, [
                'label' => 'f.label.invoiceMailText',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('partMailText', TextareaType::class, [
                'label' => 'f.label.partInvoiceMailText',
                'label_attr' => ['class' => 'col-form-label'],
                'empty_data' => $this->translator->trans('o.t.email.partInvoice'),
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('partInvoiceText', TextareaType::class, [
                'label' => 'f.label.partInvoiceText',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'height:200px;resize: vertical;'],
                'required' => true,
            ])
            ->add('invoiceText', TextareaType::class, [
                'label' => 'f.label.invoiceText',
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'height:200px;resize: vertical;'],
                'required' => true,
            ])
            ->add('productSubCategory', EntityType::class, [
                'class' => ProductSubCategory::class,
                'label' => 'osc.productCategoryAssign',
                'expanded' => false,
                'label_html' => true,
                'label_attr' => ['class' => 'col-form-label'],
                'attr' => ['class' => 'pl-sm-4 form-control'],
                'group_by' => function (ProductSubCategory $subCategory) {
                    $group = '';
                    if ($subCategory->isMainProduct()) {
                        $group = $subCategory->getCategory()->getName().' | '.$this->translator->trans('o.products').'';
                    }
                    if ($subCategory->isGlobal()) {
                        $group = $group.$this->translator->trans('p.subCat.available').' *';
                    }

                    return $group == '' ? $subCategory->getCategory()->getName() : $group;
                },
                'query_builder' => function (EntityRepository $entityRepository) use ($subCategory) {
                    $cat = $subCategory->getCategory()->getProductCategory();

                    $qb = $entityRepository->createQueryBuilder('p');
                    if ($cat !== null) {
                        $qb->join('p.category', 'c')
                            ->where('c.id = :id')->setParameter('id', $cat->getId())
                            ->andWhere('p.mainProduct = :main')->setParameter('main', true)
                            ->orderBy('p.name');
                    }

                    return $qb;
                },
                'required' => true,
            ])
            ->add('offerImage', FileType::class, [
                'label_attr' => ['class' => 'col-sm-5 col-form-label'],
                'label' => 'o.image',

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
                        'maxSizeMessage' => 'f.error.imageSize',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'f.error.format',
                    ]),
                ],
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var OfferSubCategory $object */
            $object = $event->getData();
            // dd($event);
            if (empty($object->getOfferText())) {
                $object->setOfferText(str_replace('%', '##', $this->translator->trans('o.t.offer.offer')));
            }
            if (empty($object->getMailText())) {
                $object->setMailText(str_replace('%', '##', $this->translator->trans('o.t.email.offer')));
            }
            if (empty($object->getPartInvoiceText())) {
                $object->setPartInvoiceText(str_replace('%', '##', $this->translator->trans('o.t.offer.partInvoice')));
            }
            if (empty($object->getPartMailText())) {
                $object->setPartMailText(str_replace('%', '##', $this->translator->trans('o.t.email.partInvoice')));
            }
            if (empty($object->getInvoiceText())) {
                $object->setInvoiceText(str_replace('%', '##', $this->translator->trans('o.t.offer.finalInvoice')));
            }
            if (empty($object->getInvoiceMailText())) {
                $object->setInvoiceMailText(str_replace('%', '##', $this->translator->trans('o.t.email.finalInvoice')));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferSubCategory::class,
            'allow_extra_fields' => true,
        ]);
    }
}
