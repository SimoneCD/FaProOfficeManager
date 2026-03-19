<?php

namespace App\Form;

use App\Entity\Faq;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('company', TextType::class, [
                'label' => 'c.companyName',
                'label_attr' => ['class' => 'col-12 text-left'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'w.description',
                'label_attr' => ['class' => 'col-12 text-left'],
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo',
                'label_attr' => ['class' => 'col-md-6 col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control form-file-upload'],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg', // .jpeg
                            'image/png', // .png
                            'image/gif', // .gif
                        ],
                        'mimeTypesMessage' => 'Bitte das Logo im Format .jpg, .png oder .gif hochladen!',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faq::class,
            'csrf_protection' => false,
        ]);
    }
}
