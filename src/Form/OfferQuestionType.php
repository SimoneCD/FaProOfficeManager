<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferQuestion;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class OfferQuestionType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OfferQuestion $data */
        $data = $options['data'];
        $builder->add('name', TextType::class, [
                'label' => 'w.question',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('title', TextType::class, [
                'label' => 'w.title',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('sort', null, [
                'label' => 'osc.quest.sort',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('view', ChoiceType::class, [
                'label' => 'oq.viewSelectedQuestions',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'choices' => [
                    'Anzeigen' => true,
                    'Ausblenden' => false,
                ],
                'attr' => ['class' => 'form-control form-radio'],
                'required' => true,
            ])
            ->add('needImage', CheckboxType::class, [
                'label' => 'osc.label.needImage',
                'label_attr' => ['class' => 'col-6 form-label'],
                'attr' => ['class' => 'condition-change-check form-check-input'],
                'required' => false,
            ])
            ->add('protocol', ChoiceType::class, [
                'label' => 'oq.viewState',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'choices' => [
                    'Protokoll und Fragebogen' => 'pq',
                    'Nur für den Fragebogen' => 'q',
                    'Nur für\'s Protokoll' => 'p',
                ],
                'attr' => ['class' => 'form-control form-radio'],
                'required' => true,
            ])
            ->add('productSelectSubCategoryAnz', ChoiceType::class, [
                'label' => 'o.productMultiplicator.name',
                'label_attr' => ['class' => 'col-6 col-form-label'],
                'choices' => [
                'o.productMultiplicator.one' => '1',
                'o.productMultiplicator.two' => '2',
                'o.productMultiplicator.three' => '3',
                'o.productMultiplicator.four' => '4',
                'o.productMultiplicator.amount' => 'amount',
                'o.productMultiplicator.installAmount' => 'install_amount',
                'o.productMultiplicator.length' => 'length',
//                'o.productMultiplicator.answer' => 'answer',
                ],
                'empty_data' => '1',
                'required' => false,
            ])
            ->add('productSelectSubCategory', null, [
                'label' => 'osc.quest.selectProductCategory',
                'expanded' => true,
                'class' => ProductSubCategory::class,
                'choice_label' => function (ProductSubCategory $psc) {
                    $title = '<strong>'.$psc->getCategory()->getName().'</strong> | '.$psc->getName().'';

                    return $title;
                },
                'label_html' => true,
                'query_builder' => function (EntityRepository $er) use ($data) {
                    /* @var OfferQuestion $data */
                    $subCategory = $data->getSubCategory();
                    $category = $subCategory->getCategory()->getProductCategory();
                    if (!$category instanceof ProductCategory) {
                        throw new BadRequestException();
                    }

                    return $er->createQueryBuilder('psc')
                        ->leftJoin('psc.category', 'pc')
                        ->where('pc.id = :cat')->setParameter('cat', $category->getId())
                        ->orWhere('psc.global = 1')
                        ->orderBy('pc.name');
                },
                'group_by' => function (ProductSubCategory $choice, $key, $value) {
                    return $choice->getCategory()->getName().' | '.$choice->getName().$value;
                },
                'label_attr' => ['class' => 'col-sm-6 col-form-label'],
                'required' => false,
            ])
            ->add('funnelEnd', CheckboxType::class, [
                'label' => 'Nach dieser Frage zum Kontaktformular.',
                // 'label_attr' => ['style' => 'display:none'],
                'attr' => ['class' => ''],
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($data) {
            $form = $event->getForm();
            $choices = [];
            foreach (OfferQuestion::ANSWER_HTML_TYPES_FORM as $key => $value) {
                $choice = OfferQuestion::ANSWER_HTML_TYPES[$key];
                $choices[$key] = '<strong>'.$this->translator->trans($value).'</strong>'.($choice['help'] ? ' ('.$this->translator->trans($choice['help']).') ' : '').($choice['icon'] ? '<span class="material-symbols-outlined">'.$choice['icon'].'</span>' : '');
            }
            $questions = $data->getSubCategory()->getOfferQuestions();
            $hasHauptproduct = false;
            $hasAmount = false;
            $hasInstallAmount = false;
            $hasLength = false;
            /** @var OfferQuestion $question */
            foreach ($questions as $question) {
                switch ($question->getAnswerType()) {
                    case 'hauptproduct':
                        $hasHauptproduct = true;
                        $hasAmount = true;
                        break;
                    case 'amount':
                        $hasAmount = true;
                        break;
                    case 'installAmount':
                        $hasInstallAmount = true;
                        break;
                    case 'length':
                        $hasLength = true;
                        break;
                }
            }
            $form->add('answerType', ChoiceType::class, [
                'multiple' => false,
                'expanded' => true,
                'label' => 'osc.quest.q.answerType',
                'label_html' => true,
                'choice_attr' => function ($key, $val, $index) use (
                    $hasHauptproduct,
                    $hasAmount,
                    $hasInstallAmount,
                    $hasLength,
                ) {
                    $disabled = false;
                    switch ($key) {
                        case 'hauptproduct':
                            if ($hasHauptproduct) {
                                $disabled = true;
                            }
                            break;
                        case 'amount':
                            if ($hasAmount) {
                                $disabled = true;
                            }
                            break;
                        case 'installAmount':
                            if ($hasInstallAmount) {
                                $disabled = true;
                            }
                            break;
                        case 'length':
                            if ($hasLength) {
                                $disabled = true;
                            }
                            break;
                    }
                    // set disabled to true based on the value, key or index of the choice...

                    return $disabled ? ['style' => 'display:none;'] : [];
                },
                'choices' => array_flip($choices),
                'group_by' => function ($choiceValue, $key, $value) {
                    switch ($value) {
                        case 'hauptproduct':
                        case 'selectproduct':
                            return $this->translator->trans('w.products');
                        case 'radio':
                        case 'radio-plus':
                        case 'select':
                            return $this->translator->trans('f.a.oneAnswer');
                    }

                    return '';
                },
                'label_attr' => ['style' => 'display: block', 'class' => 'col-6 required'],
                'required' => true,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferQuestion::class,
        ]);
    }
}
