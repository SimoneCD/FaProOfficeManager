<?php

namespace App\Form;

use App\Entity\ContactPerson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactPersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sex', ChoiceType::class, [
                'label' => 'w.salutation',
                'label_attr' => ['class' => 'form-label'],
               // 'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    '' => '',
                    'mr' => 'mr',
                    'ms' => 'ms',
                    'ma' => 'ma',
                ],
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'required' => false,
            ])
            ->add('surName', TextType::class, [
                'label' => 'Nachname *',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'empty_data' => '',
            ])
            ->add('phone', TextType::class, [
                'label' => 'w.phone',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'height:30px'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-Mail *',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'height:30px'],
                'empty_data' => ' ',
                'required' => true,
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Ihre Nachricht *',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'border-radius:10px;min-height:100px'],
                'empty_data' => ' ',
                'required' => true,
            ])
//            ->add('address', TextType::class, [
//                'label' => 'w.address',
//                'label_attr' => ['class' => 'bmd-label'],
//                'attr' => ['class' => 'form-control'],
//                'empty_data' => ' ',
//                'required' => false,
//            ])
//            ->add('zip', TextType::class, [
//                'label' => 'w.zip',
//                'label_attr' => ['class' => 'bmd-label'],
//                'attr' => ['class' => 'form-control'],
//                'empty_data' => ' ',
//                'required' => false,
//            ])
//            ->add('city', TextType::class, [
//                'label' => 'w.city',
//                'label_attr' => ['class' => 'bmd-label'],
//                'attr' => ['class' => 'form-control'],
//                'required' => false,
//                'empty_data' => 'Berlin',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactPerson::class,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }
}
