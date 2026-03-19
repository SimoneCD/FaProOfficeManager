<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('username', TextType::class, [
                'label' => 'w.username',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'w.fullname',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('color', ColorType::class, [
                'html5' => true,
                'label' => 'Farbe',
                'data' => '#'.($user instanceof User ? $user->getColor() : '#0996a9'),
            ])
            ->add('phone', TextType::class, [
                'label' => 'w.phone',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('slackId', TextType::class, [
                'label' => 'SlackID',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'w.email',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'w.securityRole',
                'multiple' => true,
                'expanded' => true,
                'choices' => array_flip(User::USER_ROLES),
                'attr' => ['style' => ''],
                'required' => true,
            ])
            ->add('slackLog', CheckboxType::class, [
                'label' => 'Slack Log',
                'attr' => ['class' => 'form-control-o'],
                'required' => false,
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Aktiv',
                'attr' => ['class' => 'form-control-o'],
                'required' => false,
            ])
            ->add('notice', TextareaType::class, [
                'label' => 'w.notice',
                'attr' => ['class' => 'form-control form-control-o'],
                'required' => false,
            ])
            ->add('positionName', TextType::class, [
                'attr' => ['class' => 'form-control form-control-o'],
                'required' => false,
            ])
            ->add('nettoSalary', MoneyType::class, [
                'label' => 'Netto Gehalt (im Ausbau)',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
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
