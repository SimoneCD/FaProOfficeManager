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

class FaqType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'w.title',
                'label_attr' => ['class' => 'col-12 text-left'],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'w.description',
                'label_attr' => ['class' => 'col-12 text-left'],
            ])
            ->add('video', FileType::class, [
                'label' => 'w.video',
                'label_attr' => ['class' => 'col-md-6 col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control form-file-upload'],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'video/mov', // .mp4
                            'video/mp4', // .mp4
                            'video/quicktime', // .mov
                        ],
                        'mimeTypesMessage' => 'Bitte das Video im Format .mov oder .mp4 hochladen!',
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'w.image',
                'label_attr' => ['class' => 'col-md-6 col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control form-file-upload'],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg', // .mp4
                            'image/png', // .mov
                        ],
                        'mimeTypesMessage' => 'Bitte das Bild im .jpg oder .png hochladen!',
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
