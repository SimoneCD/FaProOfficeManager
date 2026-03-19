<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferAnswers;
use App\Entity\OfferQuestion;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OfferAnswersType extends AbstractType
{
    private array $exist = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OfferAnswers $answer */
        $answer = $options['data'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'osc.quest.a.name',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('answerImage', FileType::class, [
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'label' => 'oqa.image',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control form-file-upload'],
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'osc.quest.a.error.size',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'osc.quest.a.error.format',
                    ]),
                ],
            ])
            ->add('dependencies', ChoiceType::class, [
                'label' => 'osc.quest.a.showOnly',
                'multiple' => true,
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'choice_loader' => new CallbackChoiceLoader(function () use ($answer) {
                    $subCategory = $answer->getQuestion()->getSubCategory();
                    $questions = $this->entityManager->getRepository(OfferQuestion::class)->findBy([
                        'subCategory' => $subCategory,
                    ], ['sort' => 'ASC']);
                    $array = [];
                    foreach ($questions as $question) {
                        if ($question->getId() !== $answer->getQuestion()->getId()) {
                            $array[$question->getName()] = $question->getId();
                        }
                    }

                    return $array;
                }),
                'attr' => ['class' => 'form-control', 'style' => 'height:100px'],
                'required' => false,
            ])
            ->add('helptext', TextareaType::class, [
                'label' => 'oqa.montageInfo',
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'attr' => ['class' => 'form-control', 'style' => 'height:100px'],
                'required' => false,
            ])
            ->add('products', null, [
                'label' => 'osc.productCategoryAnswerAssign',
                'label_attr' => ['class' => 'col-form-label'],
                'choice_label' => function (Product $product) {
                    return $product->getName().' ('.$product->getId().' / '.$product->getProductNumber().') '.number_format($product->getPrice(), 2, ',', '.').'€';
                },
                'group_by' => function (Product $product, $key, $value) {
                    return $name = $product->getProductCategory()->getName().' | '.$product->getProductSubCategory()->getName();
                    //                    if (!in_array($name, $this->exist)) {
                    //                        array_push($this->exist, $name);
                    //
                    //                        return $name;
                    //                    } else {
                    //                        return $product->getProductNumber().' ('.$product->getId().') | '.$product->getProductSubCategory()->getName();
                    //                    }
                },
                'attr' => ['class' => 'form-control', 'style' => 'height:400px'],
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($answer) {
                    $cat = $answer->getQuestion()->getSubCategory();
                    $cc = $cat->getProductSubCategory();
                    if ($cc->getCategory() instanceof ProductCategory) {
                        $qb = $er->createQueryBuilder('p');
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

                    return $er->createQueryBuilder('p')->orderBy('p.productNumber');
                },
                // 'choice_name' => 'name',
            ])
            ->add('funnelEnd', CheckboxType::class, [
                'label' => 'Nach dieser Antwort zum Kontaktformular.',
                // 'label_attr' => ['style' => 'display:none'],
                'attr' => ['class' => ''],
                'required' => false,
            ])
            ->add('productMultiplicator', ChoiceType::class, [
                'label' => 'o.productMultiplicator.name',
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'o.productMultiplicator.one' => '1',
                    'o.productMultiplicator.two' => '2',
                    'o.productMultiplicator.three' => '3',
                    'o.productMultiplicator.four' => '4',
                    'o.productMultiplicator.installAmount' => 'install_amount',
                    'o.productMultiplicator.amount' => 'amount',
                    'o.productMultiplicator.length' => 'length',
                    'o.productMultiplicator.answer' => 'answer',
                ],
                'choice_loader' => new CallbackChoiceLoader(function () use ($answer) {
                    $question = $answer->getQuestion();
                    $choices = [
                        'o.productMultiplicator.one' => '1',
                        'o.productMultiplicator.two' => '2',
                        'o.productMultiplicator.three' => '3',
                        'o.productMultiplicator.four' => '4',
                        'o.productMultiplicator.installAmount' => 'install_amount',
                        'o.productMultiplicator.amount' => 'amount',
                        'o.productMultiplicator.length' => 'length',
                    ];
                    if (!in_array($question->getAnswerType(), ['text', 'textarea', 'checkbox'])) {
                        $choices['o.productMultiplicator.answer'] = 'answer';
                    }

                    return $choices;
                }),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferAnswers::class,
        ]);
    }
}
