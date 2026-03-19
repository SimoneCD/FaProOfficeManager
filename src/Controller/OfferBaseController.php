<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\OfferAnswers;
use App\Entity\OfferItem;
use App\Entity\OfferOption;
use App\Entity\OfferQuestion;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OfferRepository;
use App\Repository\ProductRepository;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * OfferController.
 */
class OfferBaseController extends BaseController
{
    use TargetPathTrait;

    protected OfferRepository $offerRepository;

    protected ProductRepository $productRepository;

    protected array $inquiryContext = [];

    protected bool $inquiryInWork = false;

    protected array $addedItems = [];

    public function __construct(OfferRepository $offerRepository, ProductRepository $productRepository, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);

        $this->offerRepository = $offerRepository;
        $this->productRepository = $productRepository;
    }

    protected function setInquiryContext(Request $request, Offer $offer)
    {
        $questions = $offer->getSubCategory()->getOfferQuestionsSortByAnswerType();
        $postAll = $request->request->all();
        $post = $postAll['offer_option'] ?? [];
        $this->inquiryContext = [];
        /** @var OfferQuestion $question */
        foreach ($questions as $question) {
            $id = $question->getQuestionArea() ? $question->getQuestionArea()->getId() : 0;
            if (isset($post[$question->getId()])) {
                $this->inquiryContext[$id]['questions'][$question->getId()] = [
                    'name' => $question->getName(),
                    'title' => $question->getTitle(),
                ];
                switch ($question->getAnswerType()) {
                    case 'number':
                        /** @var OfferAnswers $answer */
                        foreach ($question->getOfferAnswers() as $answer) {
                            if ('answer' === $answer->getProductMultiplicator()) {
                                if (!empty((int) $post[$question->getId()])) {
                                    $this->addAnswerProducts($request, $offer, $answer, (int) $post[$question->getId()]);
                                    $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
                                    $offer->setNotice($question->getName().' '.$post[$question->getId()]."\n".$offer->getNotice());
                                }
                            }
                        }
                        break;
                    case 'text':
                    case 'textarea':
                        /** @var OfferAnswers $answer */
                        foreach ($question->getOfferAnswers() as $answer) {
                            if ('answer' === $answer->getProductMultiplicator()) {
                                $this->addAnswerProducts($request, $offer, $answer, (int) $post[$question->getId()]);
                            }
                        }
                        if (!empty($post[$question->getId()])) {
                            $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
                        }
                        break;
                    case 'hauptproduct':
                        foreach ($question->getOfferAnswers() as $answer) {
                            if (!empty($post[$question->getId()]) && (int) $answer->getName() === (int) $post[$question->getId()]) {
                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
                                $this->addAnswerProducts($request, $offer, $answer, $offer->getAmount());
                                if (empty($offer->getKw())) {
                                    $offer->setKw($offer->getWallboxProduct()->getKw());
                                }
                            }
                        }
                        break;
                    case 'length':
                        if (!empty($post[$question->getId()])) {
                            $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
                            $option = $offer->getOption();
                            $option->setOutletFuseMeter((int) $post[$question->getId()]);
                        }
                        $offer->setNotice($question->getName().' = '.$post[$question->getId()]."\n".$offer->getNotice());
                        break;
                    case 'select':
                        foreach ($question->getOfferAnswers() as $answer) {
                            if ($answer->getId() === (int) $post[$question->getId()]) {
                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
                                $this->addAnswerProducts($request, $offer, $answer, $id);
                            }
                        }
                        break;
                    case 'radio':
                        foreach ($question->getOfferAnswers() as $answer) {
                            if ($answer->getId() === (int) $post[$question->getId()]) {
                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
                                    'n' => (int) $post[$question->getId()],
                                ];
                                $this->addAnswerProducts($request, $offer, $answer, $id);
                            }
                        }
                        break;
                    case 'radio-plus':
                        foreach ($question->getOfferAnswers() as $answer) {
                            if ($answer->getId() === (int) $post[$question->getId()]) {
                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
                                    'n' => $post[$question->getId()],
                                    'np' => 'plus' === $post[$question->getId()],
                                ];
                                $this->addAnswerProducts($request, $offer, $answer, $id);
                            } elseif (!empty($post[$question->getId()]) && 'plus' == $post[$question->getId()]) {
                                $np = ($postAll['offer_option_plus'] && !empty($postAll['offer_option_plus'][$question->getId()]) ? $postAll['offer_option_plus'][$question->getId()] : '');
                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
                                    'n' => $post[$question->getId()],
                                    'np' => $np,
                                ];
                                $this->addAnswerProducts($request, $offer, $answer, $id);
                            }
                        }
                        break;
                    case 'selectproduct':
                        /** @var OfferItem $offerItem */
                        $ra = $request->request->all();
                        $postAnz = $this->getQuestionCount($offer, $question, $ra['offer'] ?? []);
                        $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
                            'name' => $post[$question->getId()],
                            'anz' => $postAnz,
                        ];
                        $product = $this->em->getRepository(Product::class)->find($post[$question->getId()]);
                        if ($product instanceof Product) {
                            $item = $this->em->getRepository(OfferItem::class)->findOneBy([
                                'offer' => $offer,
                                'item' => $product,
                            ]);
                            if ($item instanceof OfferItem) {
                                $this->updateSelectedProduct($item, $postAnz, $question);
                            } else {
                                $this->addSelectedProduct($offer, $product, $postAnz, $question);
                            }
                        }
                        break;
                    case 'checkbox':
                        foreach ($question->getOfferAnswers() as $answer) {
                            foreach ($post[$question->getId()] as $pk => $pa) {
                                if ($answer->getId() === (int) $pa) {
                                    $this->inquiryContext[$id]['questions'][$question->getId()]['answer'][] = $pa;
                                    $this->addAnswerProducts($request, $offer, $answer, $id);
                                }
                            }
                        }
                        break;
                }
            } else {
                $this->inquiryContext[$id]['questions'][$question->getId()] = [
                    'name' => $question->getName(),
                    'title' => $question->getTitle(),
                    'answer' => false,
                ];
            }
        }
    }

    protected function addInquiryContext(Request $request, Offer $offer)
    {
        $this->inquiryContext = [];
        $this->setInquiryContext($request, $offer);
        //        /** @var OfferQuestion $question */
        //        foreach ($questions as $question) {
        //            $id = $question->getQuestionArea() ? $question->getQuestionArea()->getId() : 0;
        //            if (!empty($post[$question->getId()])) {
        //                $this->inquiryContext[$id]['questions'][$question->getId()] = [
        //                    'name' => $question->getName(),
        //                    'title' => $question->getTitle(),
        //                ];
        //                switch ($question->getAnswerType()) {
        //                    case 'number':
        //                        /** @var OfferAnswers $answer */
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            if ($answer->getProductMultiplicator() === 'answer') {
        //                                if (!empty((int) $answer->getName())) {
        //                                    $this->addAnswerProducts($request, $offer, $answer, (int) $post[$question->getId()]);
        //                                    $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
        //                                    $offer->setNotice($question->getName().' '.$post[$question->getId()]."\n".$offer->getNotice());
        //                                }
        //                            }
        //                        }
        //                        break;
        //                    case 'text':
        //                    case 'textarea':
        //                        /** @var OfferAnswers $answer */
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            if ($answer->getProductMultiplicator() === 'answer') {
        //                                $this->addAnswerProducts($request, $offer, $answer, (int) $post[$question->getId()]);
        //                            }
        //                        }
        //                        if (!empty($post[$question->getId()])) {
        //                            $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
        //                            $offer->setNotice($question->getName().' '.$post[$question->getId()]."\n".$offer->getNotice());
        //                        }
        //                        break;
        //                    case 'hauptproduct':
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            if (!empty($post[$question->getId()]) && (int) $answer->getName() === (int) $post[$question->getId()]) {
        //                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
        //                                $this->addAnswerProducts($request, $offer, $answer, $offer->getAmount());
        //                                if (empty($offer->getKw())) {
        //                                    $offer->setKw($offer->getWallboxProduct()->getKw());
        //                                }
        //                            }
        //                            if (!empty($answer->getHelptext())) {
        //                                $offer->setNotice($question->getName().' = '.$answer->getHelptext()."\n".$offer->getNotice());
        //                            }
        //                        }
        //                        $offer->setNotice('Produktleistung: '.$offer->getKw().' '.($power ? $offer->getWallboxProduct()->getValueName() : ''));
        //                        break;
        //                    case 'length':
        //                        if (!empty($post[$question->getId()])) {
        //                            $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
        //                            $option = $offer->getOption();
        //                            $option->setOutletFuseMeter((int) $post[$question->getId()]);
        //                            if ($this->inquiryInWork === false) {
        //                                $this->em->persist($option);
        //                                $this->em->persist($offer);
        //                            }
        //                        }
        //                        $offer->setNotice($question->getName().' = '.$post[$question->getId()]."\n".$offer->getNotice());
        //                        break;
        //                    case 'select':
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            if ($answer->getId() === (int) $post[$question->getId()]) {
        //                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = $post[$question->getId()];
        //                            }
        //                        }
        //                        break;
        //                    case 'radio':
        //                    case 'radio-plus':
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            if ($answer->getId() === (int) $post[$question->getId()]) {
        //                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
        //                                    'n' => $post[$question->getId()],
        //                                    'np' => $post[$question->getId()] === 'plus',
        //                                ];
        //                                $this->addAnswerProducts($request, $offer, $answer, $id);
        //                            } elseif (!empty($post[$question->getId()]) && $post[$question->getId()] == 'plus') {
        //                                $np = ($postAll['offer_option_plus'] && !empty($postAll['offer_option_plus'][$question->getId()]) ? $postAll['offer_option_plus'][$question->getId()] : '');
        //                                $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
        //                                    'n' => $post[$question->getId()],
        //                                    'np' => $np,
        //                                ];
        //                            }
        //                        }
        //                        if (!empty($answer) && !empty($answer->getHelptext())) {
        //                            $offer->setNotice($question->getName().' = '.$answer->getHelptext()."\n".$offer->getNotice());
        //                        }
        //                        break;
        //                    case 'selectproduct':
        //                        if (!empty($post[$question->getId()])) {
        //                            $product = $this->em->getRepository(Product::class)->find($post[$question->getId()]);
        //                            /** @var OfferItem $offerItem */
        //                            $offerItem = $this->em->getRepository(OfferItem::class)->findOneBy([
        //                                'offer' => $offer,
        //                                'item' => $product,
        //                            ]);
        //                            $ra = $request->request->all();
        //                            $postAnz = $this->getQuestionCount($offer, $question, $ra['offer']);
        //                            if ($this->inquiryInWork === false && empty($offerItem) and $product instanceof Product) {
        //                                $add = true;
        //                                foreach ($this->addedItems as $oi) {
        //                                    if ($oi->getItem() instanceof Product && $oi->getItem()->getId() == $product->getId()) {
        //                                        $add = false;
        //                                        $this->updateSelectedProduct($oi, $postAnz ?? 1, $question);
        //                                    }
        //                                }
        //                                if ($add === true) {
        //                                    $this->addSelectedProduct($offer, $product, $postAnz ?? 1, $question);
        //                                }
        //                            } elseif ($this->inquiryInWork === false && $offerItem instanceof OfferItem) {
        //                                $amount = $offerItem->getAmount();
        //                                foreach ($this->addedItems as $oi) {
        //                                    if ($oi->getItem()->getId() == $offerItem->getItem()->getId()) {
        //                                        $amount += $oi->getAmount() ?? 0;
        //                                    }
        //                                }
        //                                $offerItem->setAmount($amount + ($postAnz ?? 1));
        //                                $this->em->persist($offerItem);
        //                            }
        //                            $this->inquiryContext[$id]['questions'][$question->getId()]['answer'] = [
        //                                'name' => $post[$question->getId()],
        //                                'anz' => $postAnz,
        //                            ];
        //                        }
        //                        break;
        //                    case 'checkbox':
        //                        foreach ($question->getOfferAnswers() as $answer) {
        //                            foreach ($post[$question->getId()] as $pk => $pa) {
        //                                if ($answer->getId() === (int) $pa) {
        //                                    $this->inquiryContext[$id]['questions'][$question->getId()]['answer'][] = $pa;
        //                                    $this->addAnswerProducts($request, $offer, $answer, $id);
        //                                }
        //                                if (!empty($answer->getHelptext())) {
        //                                    $offer->setNotice($question->getName().' = '.$answer->getHelptext()."\n".$offer->getNotice());
        //                                }
        //                            }
        //                        }
        //                        break;
        //                }
        //            } else {
        //                $this->inquiryContext[$id]['questions'][$question->getId()] = [
        //                    'name' => $question->getName(),
        //                    'title' => $question->getTitle(),
        //                    'answer' => false,
        //                ];
        //            }
        //        }
    }

    protected function addSubCategorieData(Offer $offer)
    {
        if ($offer->getSubCategory() && false === $this->inquiryInWork) {
            foreach ($offer->getSubCategory()->getProducts() as $product) {
                foreach ($offer->getOfferItems() as $item) {
                    if ($item->getItem() === $product) {
                        $this->addedItems[] = $item;
                        continue 2;
                    }
                }
                $offerItem = new OfferItem();
                $offerItem->setOffer($offer);
                $offerItem->setName($product->getName());
                $offerItem->setTax(19);
                $offerItem->setAmount(1);
                $offerItem->setDescription($product->getDescription());
                $offerItem->setItem($product);
                $offerItem->setPrice($product->getPrice());
                $offer->addOfferItem($offerItem);
                $this->addedItems[] = $offerItem;
                try {
                    $this->em->persist($offerItem);
                } catch (\Exception $exception) {
                }
            }
            $this->em->flush();
        }
    }

    protected function addSelectedProduct(Offer $offer, Product $product, int $anz, OfferQuestion $question)
    {
        $offerItem = new OfferItem();
        $offerItem->setOffer($offer);
        $offerItem->setName($product->getName());
        $offerItem->setTax(19);
        $offerItem->setAmount($anz);
        $offerItem->setDescription($product->getDescription());
        $offerItem->setItem($product);
        $offerItem->setPrice(($product->getPrice() ?? 0) * $anz);
        $offer->addOfferItem($offerItem);
        try {
            if (false === $this->inquiryInWork) {
                $this->em->persist($offerItem);
                $this->em->flush();
            }
            $offer->setNotice($question->getName().' = '.$product->getName().' x '.$anz."\n".$offer->getNotice());
        } catch (\Exception $exception) {
        }
    }

    protected function updateSelectedProduct(OfferItem $offerItem, int $anz, OfferQuestion $question)
    {
        $offerItem->setAmount($offerItem->getAmount() + $anz);
        try {
            if (false === $this->inquiryInWork) {
                $this->em->persist($offerItem);
                $this->em->flush();
            }
        } catch (\Exception $exception) {
        }
    }

    protected function addAnswerProducts(Request $request, Offer $offer, OfferAnswers $answer, int $id)
    {
        if (false === $this->inquiryInWork) {
            $post = $request->request->all();
            $postOffer = !empty($post['offer']) ? $post['offer'] : [];
            $postOptions = !empty($post['offer_option']) ? $post['offer_option'] : [];
            $anz = 1;
            if ('amount' === $answer->getProductMultiplicator()) {
                if (!empty($offer->getAmount())) {
                    $anz = $offer->getAmount();
                } else {
                    $anz = !empty($postOffer) && !empty($postOffer['amount']) ? $postOffer['amount'] : 1;
                }
            } elseif ('answer' === $answer->getProductMultiplicator()) {
                $anz = $id;
            } elseif ('install_amount' === $answer->getProductMultiplicator()) {
                if (!empty($offer->getInstallAmount())) {
                    $anz = $offer->getInstallAmount();
                } else {
                    $anz = !empty($postOffer) && !empty($postOffer['install_amount']) ? $postOffer['install_amount'] : 1;
                }
            } elseif ('length' === $answer->getProductMultiplicator()) {
                $qs = $answer->getQuestion()->getSubCategory()->getOfferQuestions();
                /** @var OfferQuestion $q */
                $anz = $offer->getOption()->getOutletFuseMeter() ?? 0;
                if (empty($anz)) {
                    foreach ($qs as $q) {
                        if ('length' == $q->getAnswerType()) {
                            if (!empty($postOptions[$q->getId()])) {
                                $anz = (int) $postOptions[$q->getId()];
                            }
                        }
                    }
                }
            } elseif (in_array($answer->getProductMultiplicator(), ['1', '2', '3', '4'])) {
                $anz = (int) $answer->getProductMultiplicator();
            }
            /** @var Product $product */
            foreach ($answer->getProducts() as $product) {
                $existItem = $this->em->getRepository(OfferItem::class)->findOneBy([
                    'offer' => $offer,
                    'item' => $product,
                ]);
                if (!$existItem instanceof OfferItem) {
                    $offerItem = new OfferItem();
                    $offerItem->setAmount($anz);
                    $offer->addOfferItem($offerItem);
                    $this->addedItems[] = $offerItem;
                    $offerItem->setOffer($offer);
                    $offerItem->setEinheit($product->getEinheit());
                    $offerItem->setName($product->getName());
                    $offerItem->setDescription($product->getDescription());
                    $offerItem->setItem($product);
                    $offerItem->setPrice($product->getPrice());
                } else {
                    $existAnz = $existItem->getAmount() ?? 0;
                    /** @var OfferItem $ai */
                    foreach ($this->addedItems as $ai) {
                        if ($ai->getItem()->getId() == $existItem->getItem()->getId()) {
                            $existAnz += $ai->getAmount();
                        }
                    }
                    $offerItem = $existItem;
                    $offerItem->setEinheit($product->getEinheit());
                    $offerItem->setAmount($existAnz + $anz);
                }

                try {
                    $this->em->persist($offerItem);
                    $offer->setNotice(''.$answer->getQuestion()->getName().' = '.$product->getName().' x '.$anz."\n".$offer->getNotice());
                } catch (\Exception $exception) {
                }
            }
            $this->em->flush();
        }
    }

    protected function getQuestionCount(Offer $offer, OfferQuestion $question, array $postOffer): int
    {
        $anz = 1;
        if ('amount' === $question->getProductSelectSubCategoryAnz()) {
            if (!empty($offer->getAmount())) {
                $anz = $offer->getAmount();
            } else {
                $anz = !empty($postOffer) && !empty($postOffer['amount']) ? $postOffer['amount'] : 1;
            }
        } elseif ('install_amount' === $question->getProductSelectSubCategoryAnz()) {
            if (!empty($offer->getInstallAmount())) {
                $anz = $offer->getInstallAmount();
            } else {
                $anz = !empty($postOffer) && !empty($postOffer['install_amount']) ? $postOffer['install_amount'] : 1;
            }
        } elseif ('length' === $question->getProductSelectSubCategoryAnz()) {
            if (!empty($offer->getOption()->getOutletFuseMeter())) {
                $anz = $offer->getOption()->getOutletFuseMeter();
            }
        } elseif (in_array($question->getProductSelectSubCategoryAnz(), ['1', '2', '3', '4'])) {
            $anz = (int) $question->getProductSelectSubCategoryAnz();
        } elseif ('answer' === $question->getProductSelectSubCategoryAnz()) {
            $anz = !empty($postOffer) && !empty($postOffer['answer']) ? $postOffer['answer'] : 1;
        }

        return $anz;
    }

    protected function getRequestedUser(Request $request, &$userFilter, bool $search = false): ?User
    {
        $ru = $request->query->get('user');
        $userFilter = $ru ?: $this->getUser()->getId();
        $user = null;
        if (!empty($search)) {
            $userFilter = 'all';
            if (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
                $user = $this->getUser();
                $userFilter = $this->getUser()->getId();
            }
        } elseif ($this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            if ('all' !== $userFilter) {
                $user = $this->em->getRepository(User::class)->find($userFilter);
            }
        } elseif (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            $user = $this->getUser();
        }

        return $user;
    }

    #[Pure]
    protected function getOption(Offer $offer): ?OfferOption
    {
        $option = $offer->getOption();
        if (!$option instanceof OfferOption) {
            $option = new OfferOption();
        }

        return $option;
    }

    protected function setAutoStationAddress(Offer $offer): void
    {
        if (null === $offer->getStationAddress()) {
            $offer->setStationAddress($offer->getCustomer()->getAddress());
            $offer->setStationZip($offer->getCustomer()->getZip().' '.$offer->getCustomer()->getCity());
        }
    }

    protected function setNewKeyValue(Offer $offer)
    {
        $context = !empty($offer->getOption()) ? $offer->getOption()->getContext() : [];
        foreach ($offer->getSubCategory()->getKeyValueSubCategoryData() as $k => $kv) {
            $context['kv']['name'][$k] = $kv->getKeyName();
            $context['kv']['value'][$k] = $kv->getKeyValue();
        }
        if (empty($offer->getOption())) {
            $option = new OfferOption();
            $option->setOffer($offer);
            $offer->setOption($option);
            $this->em->persist($option);
        }

        $offer->getOption()->setContext($context);
        $this->em->persist($offer);
        $this->em->flush();
    }

    protected function setNewInvoicePercent(Offer $offer)
    {
        $context = [
            'invoice_pay' => [
                0 => ['value' => ($offer->getOption() && $offer->getOption()->isSolar() ? 80 : 50), 'name' => 'Vorauszahlung zur Anschaffung des Materials.'],
                1 => ['value' => 0, 'name' => ''],
                2 => ['value' => 0, 'name' => ''],
            ],
        ];

        $offer->setContext($context);
        $this->em->persist($offer);
        $this->em->flush();
    }
}
