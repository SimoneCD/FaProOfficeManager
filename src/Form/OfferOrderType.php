<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        new \DateTime();
        $builder
            ->add('stationAddress', null, [
                'label' => 'o.address',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'bmd-label-floating textblack'],
                'required' => false,
            ])
            ->add('stationZip', null, [
                'label' => 'o.city',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'bmd-label-floating textblack'],
                'required' => false,
            ])
            ->add('notice', TextareaType::class, ['label' => 'p.offer.notice',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'bmd-label-floating textblack'],
                'required' => false,
            ])
//            ->add('images', CollectionType::class, [
//                'label' => 'm.images',
//                'entry_type' => ImageType::class,
//                'entry_options' => ['label' => false],
//                'allow_add' => true,
//                'allow_delete' => true,
//                'required' => false,
//            ])
//            ->add('documents', CollectionType::class, [
//                'label' => 'm.documents',
//                'entry_type' => DocumentType::class,
//                'entry_options' => ['label' => false],
//                'allow_add' => true,
//                'allow_delete' => true,
//                'required' => false,
//            ])
            ->add('serviceDateFrom', DateType::class, [
                'label' => 'o.productData.from',
                'format' => 'dd.MM.yyyy',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'form-control termin'],
            ])
            ->add('serviceDateTo', DateType::class, [
                'label' => 'o.productData.until',
                'format' => 'dd.MM.yyyy',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'form-control termin'],
            ])
        ;
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
