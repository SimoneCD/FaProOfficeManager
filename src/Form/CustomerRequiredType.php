<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerRequiredType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sex', ChoiceType::class, [
                'label' => 'w.salutation',
                'label_attr' => ['class' => 'bmd-label'],
               // 'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control'],
                'choices' => [
                    'w.salutation' => '',
                    'mr' => 'mr',
                    'ms' => 'ms',
                    'ma' => 'ma',
                ],
                'required' => false,
                'placeholder' => 'w.salutation',
            ])
            ->add('name', TextType::class, [
                'label' => 'Name *',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control', 'placeholder' => 'Name *'],
                'empty_data' => '',
                'required' => true,
            ])
//            ->add('surName', TextType::class, [
//                'label' => 'w.surname',
//                'label_attr' => ['class' => 'bmd-label'],
//                'attr' => ['class' => 'last-form-control', 'placeholder' => 'w.surname'],
//                'required' => false,
//                'empty_data' => '',
//            ])
            ->add('phone', TextType::class, [
                'label' => 'w.phone',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control', 'placeholder' => 'w.phone'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-Mail *',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control', 'placeholder' => 'E-Mail *'],
                'empty_data' => ' ',
                'required' => true,
            ])
//            ->add('address', TextType::class, [
//                'label' => 'w.address',
//                'label_attr' => ['class' => 'bmd-label'],
//                'attr' => ['class' => 'last-form-control', 'placeholder' => 'w.address'],
//                'empty_data' => ' ',
//                'required' => false,
//            ])
            ->add('zip', TextType::class, [
                'label' => 'w.zip',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control', 'placeholder' => 'w.zip'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'w.city',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'last-form-control', 'placeholder' => 'w.city'],
                'required' => false,
                'empty_data' => 'Berlin',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }
}
