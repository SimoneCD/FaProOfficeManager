<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserOwnType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];

        $builder
            ->add('image', FileType::class, [
                'label' => 'u.image',
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
                        'mimeTypesMessage' => 'Bitte das Bild im Format jpg oder png hochladen!',
                    ]),
                ],
            ])
            ->add('salutation', ChoiceType::class, [
                'multiple' => false,
                'label' => 'w.salutation',
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'salutations.mr' => 'mr',
                    'salutations.ms' => 'ms',
                    'salutations.ma' => 'ma',
                ],
                'empty_data' => 'mr',
            ])
            ->add('color', ColorType::class, [
                'html5' => true,
                'label' => 'Farbe',
                'data' => '#'.($user ? $user->getColor() : ''),
            ])
            ->add('username', TextType::class, [
                'label' => 'w.username',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Voller Name (ohne Anrede)',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('slackId', TextType::class, [
                'label' => 'SlackID',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'w.phone',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'w.email',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'w.password'],
                'second_options' => ['label' => 'w.password_confirm'],
                'auto_initialize' => false,
                'attr' => ['autocomplete' => 'off'],
                'required' => false,
            ])
//            ->add('password', PasswordType::class, [
//                'label' => 'label.password',
//                'attr' => ['class' => 'form-control'],
//                'empty_data' => null,
//                'required' => false,
//            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
