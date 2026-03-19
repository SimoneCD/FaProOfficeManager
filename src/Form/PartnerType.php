<?php

namespace App\Form;

use App\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'partner.description',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('link', TextType::class, [
                'label' => 'partner.link',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('address', null, [
                'label' => 'partner.address',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'w.phone',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'partner.type',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => Partner::PARTNER_TYPES,
                'required' => true,
            ])
            ->add('email', null, [
                'label' => 'w.email',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
        ]);
    }
}
