<?php

namespace App\Form;

use App\Entity\ProductManufactura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductManufacturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
            ])
            ->add('manufacturaLogo', FileType::class, [
                'label' => 'Logo',
                'label_attr' => ['class' => 'col-12 col-md-3 col-form-label'],
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
                        'mimeTypesMessage' => 'Bitte das Logo im Format jpg oder png hochladen!',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'w.description',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('link', TextType::class, [
                'label' => 'Link',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('userName', null, [
                'label' => 'Benutzername',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('password', null, [
                'label' => 'Passwort',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'w.phone',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Hersteller Typ',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => ProductManufactura::MANUFACTURA_TYPES,
                'required' => true,
            ])
            ->add('email', null, [
                'label' => 'w.email',
                'label_attr' => ['class' => 'col-md-2 col-form-label'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductManufactura::class,
            'csrf_protection' => false,
        ]);
    }
}
