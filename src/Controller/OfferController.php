<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferItem;
use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\User;
use App\Form\ActionLogType;
use App\Form\OfferContextType;
use App\Form\OfferEmptyWallboxType;
use App\Form\OfferImageType;
use App\Form\OfferItemType;
use App\Form\OfferType;
use App\Form\OfferWallboxType;
use App\Repository\OfferCategoryRepository;
use App\Repository\OfferRepository;
use App\Repository\ProductRepository;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * OfferController.
 */
#[IsGranted('ROLE_MONTAGE')]
#[Route(['de' => '/angebote', 'en' => '/offers'])]
class OfferController extends OfferBaseController
{
    use TargetPathTrait;

    public function __construct(OfferRepository $offerRepository, ProductRepository $productRepository, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        parent::__construct($offerRepository, $productRepository, $em, $client, $mailerService, $translator, $subdomain);
    }

    private function getUserFilter($request, $user)
    {
        return $request->query->get('user') ?? $user->getId();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/kategorien', 'en' => '/categorys'], name: 'offer_category_all', methods: ['GET'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function categoryAll(Request $request): Response
    {
        //        $url = $this->checkUserTime($request);
        //        if(!$this->isGranted('ROLE_ADMIN') && $url !== false) {
        //            return new RedirectResponse($url);
        //        }
        $isEmployeeService = $this->isGranted('ROLE_EMPLOYEE_SERVICE');

        $user = $this->getUser();
        $userFilter = $this->getUserFilter($request, $user);
        list($search, $filter) = $this->getStartParameter($request);

        if ($isEmployeeService) {
            if (empty($userFilter)) {
                $userFilter = $user->getId();
            }
            $f = match ($filter) {
                'in' => false,
                'out' => true,
                default => null,
            };
            $teams = $this->getTeams();
            $requestedUser = $this->getRequestedUser($request, $userFilter);
            $today = !empty($request->query->get('today'));
            $offers = $this->offerRepository->findByCategory(null, $search, $requestedUser, $f, $today);
        } else {
            $teams = [];
            $offers = $this->offerRepository->findByCategoryAndUser(null, $search, $user, null, false);
        }
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        return $this->render('offer/index.html.twig', [
            'users' => $this->getServiceUsers(),
            'teams' => $teams,
            'inquiries' => $offers,
            'category' => null,
            'filter' => $filter ?: 'all',
            'filterUser' => $userFilter,
            'today' => $request->query->getBoolean('today', false),
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2->createView(),
        ]);
    }

    #[Route(['de' => '/kategorie/{id}', 'en' => '/category/{id}'], name: 'offer_category_index', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function categoryIndex(Request $request, OfferCategory $category): Response
    {
        //        $url = $this->checkUserTime($request);
        //        if(!$this->isGranted('ROLE_ADMIN') && $url !== false) {
        //            return new RedirectResponse($url);
        //        }

        list($search, $filter) = $this->getStartParameter($request);
        $userFilter = $request->query->get('user') ?? $this->getUser()->getId();
        if (empty($userFilter)) {
            $userFilter = $this->getUser()->getId();
        }
        $f = 'in' === $filter ? false : ('out' === $filter ? true : null);
        $user = $this->getRequestedUser($request, $userFilter);
        $offers = $this->offerRepository->findByCategory($category, $search, $user, $f, !empty($request->query->get('today')));
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        return $this->render('offer/index.html.twig', [
            'users' => $this->getServiceUsers(),
            'teams' => $this->getTeams(),
            'inquiries' => $offers,
            'category' => $category,
            'filter' => $filter ?? 'all',
            'filterUser' => $userFilter,
            'today' => (int) $request->query->get('today'),
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2,
        ]);
    }

    #[Route(['de' => '/beendet', 'en' => '/done'], name: 'offer_archive', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archive(): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            return $this->redirectToRoute('security_login');
        }
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        return $this->render('offer/archive.html.twig', [
            'users' => $this->getServiceUsers(),
            'inquiries' => $this->offerRepository->findBy(['status' => 'archive']),
            'teams' => $this->getTeams(),
            'block' => 'archive',
            'status' => 'archive',
            'filter' => null,
            'color' => 'lightgrey',
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2,
        ]);
    }

    #[Route(['de' => '/unterkategorie/{id}', 'en' => '/sub-category/{id}'], name: 'offer_subcategory_index', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function subCategoryIndex(Request $request, OfferSubCategory $category): Response
    {
        list($search, $filter) = $this->getStartParameter($request);
        $userFilter = $request->query->get('user');
        $user = $this->getRequestedUser($request, $userFilter);
        $f = 'in' === $filter ? false : ('out' === $filter ? true : null);
        $offers = $this->offerRepository->findBySubCategory($category, $search, $user, $f, !empty($request->query->get('today')));
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());

        return $this->render('offer/index.html.twig', [
            'users' => $this->getServiceUsers(),
            'teams' => $this->getTeams(),
            'inquiries' => $offers,
            'category' => $category->getCategory(),
            'subCategory' => $category,
            'filter' => $filter,
            'filterUser' => $userFilter,
            'today' => $request->query->get('today'),
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/suchen', 'en' => '/search'], name: 'offer_category_isearch', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function categorySearch(Request $request): Response
    {
        list($search, $filter) = $this->getStartParameter($request);
        $user = $this->getRequestedUser($request, $userFilter, true);
        $f = 'in' === $filter ? false : ('out' === $filter ? true : null);
        $offers = $this->offerRepository->findBySearch($search, $user, $f);
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        return $this->render('offer/index.html.twig', [
            'users' => $this->getServiceUsers(),
            'user' => $user,
            'teams' => $this->getTeams(),
            'inquiries' => $offers,
            'category' => null,
            'filter' => $filter,
            'filterUser' => $userFilter,
            'search' => true,
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2->createView(),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     *                                  Fragebogen beim Kundenanruf
     */
    #[Route(path: ['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'offer_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function edit(Request $request, Offer $offer, OfferCategoryRepository $categoryRepository): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('POST_EDIT', $offer)) {
            return $this->redirectToRoute('booking_index');
        }
        $this->inquiryInWork = ('2' === $request->request->get('save', 0));
        $this->setAutoStationAddress($offer);
        $offerCategories = $this->getCategories();
        $option = $this->getOption($offer);
        $offer->setOption($option);
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        $changeCategory = $request->request->get('change_category');

        if ($changeCategory) {
            $cat = $categoryRepository->find($changeCategory);
            if (!$offer->getCategory() instanceof OfferCategory || (int) $changeCategory !== $offer->getCategory()->getId()) {
                $offer->setCategory($cat);
                $offer->setSubCategory(null);
                $this->fa->delete('category'.$offer->getCategory()->getId());
                $this->em->persist($offer);
                $this->em->flush();
            }
            $subCategory = $offer->getSubCategory() ?? $cat->getOfferSubCategories()[0];

            return $this->render('offer/types/edit.html.twig', [
                'offer' => $offer,
                'subCategory' => $subCategory,
                'category' => $cat,
                'form' => $form->createView(),
                //                'products' => $offerProducts,
                'categories' => $offerCategories,
                'inquiryContext' => $offer->getInquiry()->getContext(),
            ]);
        }
        if ((!$offer->getCategory() && $request->request->get('category')) || ($request->request->get('category') && $offer->getCategory()->getId() !== (int) $request->request->get('category'))) {
            $cat = $this->em->getRepository(OfferCategory::class)->find($request->request->get('category'));
            $offer->setCategory($cat);
            // $this->fa->delete('categories');
        }
        if ((!$offer->getSubCategory() && $request->request->get('subCategory')) || ($request->request->get('subCategory') && $offer->getSubCategory()->getId() !== (int) $request->request->get('subCategory'))) {
            $cat = $this->em->getRepository(OfferSubCategory::class)->find($request->request->get('subCategory'));
            $offer->setSubCategory($cat);
            // $this->fa->delete('categories');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->em->refresh($offer);
            $this->addSubCategorieData($offer);

            $this->addInquiryContext($request, $offer);

            $iq = $offer->getInquiry();
            $iq->setContext($this->inquiryContext);

            $this->em->persist($iq);
            if ('2' === $request->request->get('save', 0)) {
                if ('call' === $offer->getStatus()) {
                    $offer->setStatus('call-plus');
                    $iq->setStatus('call-plus');
                }
            } else {
                $offer->setStatus('estimate');
            }
            $offer->setStatusDate(new \DateTime());
            if ('2' === $request->request->get('save', 0)) {
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('o.success.saved'));

                return $this->redirectToRoute('offer_category_index', ['id' => $offer->getCategory()->getId()], Response::HTTP_SEE_OTHER);
            }
            $this->em->persist($offer);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('o.success.created'));

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offer/types/edit.html.twig', [
            // 'products' => $this->getOfferMainProducts($offer),
            'offer' => $offer,
            'category' => $offer->getCategory(),
            'form' => $form->createView(),
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: ['de' => '/{id}/bearbeitung', 'en' => '/{id}/editing'], name: 'offer_edit_plus', methods: ['GET', 'POST'])]
    public function editplus(Request $request, Offer $offer, OfferCategoryRepository $categoryRepository): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('POST_EDIT', $offer)) {
            return $this->redirectToRoute('booking_index');
        }
        $this->inquiryInWork = true;
        $offerCategories = $this->getCategories();
        $option = $this->getOption($offer);
        $offer->setOption($option);
        $form = $this->createForm(OfferContextType::class, $offer);
        $form->handleRequest($request);

        $changeCategory = $request->request->get('change_category');

        if ($changeCategory) {
            $cat = $categoryRepository->find($changeCategory);
            if (!$offer->getCategory() instanceof OfferCategory || (int) $changeCategory !== $offer->getCategory()->getId()) {
                $offer->setCategory($cat);
                $offer->setSubCategory(null);
                $this->fa->delete('category'.$offer->getCategory()->getId());
                $this->em->persist($offer);
                $this->em->flush();
            }
            $subCategory = $offer->getSubCategory() ?? $cat->getOfferSubCategories()[0];

            return $this->render('offer/types/editplus.html.twig', [
                'offer' => $offer,
                'subCategory' => $subCategory,
                'category' => $cat,
                'form' => $form->createView(),
                //                'products' => $offerProducts,
                'categories' => $offerCategories,
                'inquiryContext' => $offer->getInquiry()->getContext(),
            ]);
        }
        if ((!$offer->getCategory() && $request->request->get('category')) || ($request->request->get('category') && $offer->getCategory()->getId() !== (int) $request->request->get('category'))) {
            $cat = $this->em->getRepository(OfferCategory::class)->find($request->request->get('category'));
            $offer->setCategory($cat);
            // $this->fa->delete('categories');
        }
        if ((!$offer->getSubCategory() && $request->request->get('subCategory')) || ($request->request->get('subCategory') && $offer->getSubCategory()->getId() !== (int) $request->request->get('subCategory'))) {
            $cat = $this->em->getRepository(OfferSubCategory::class)->find($request->request->get('subCategory'));
            $offer->setSubCategory($cat);
            // $this->fa->delete('categories');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->em->refresh($offer);
            $this->addSubCategorieData($offer);

            $this->inquiryContext = [];
            $this->setInquiryContext($request, $offer);

            $iq = $offer->getInquiry();
            $iq->setContext($this->inquiryContext);

            $this->em->persist($iq);
            $this->em->persist($offer);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('o.success.created'));

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offer/types/editplus.html.twig', [
            // 'products' => $this->getOfferMainProducts($offer),
            'offer' => $offer,
            'category' => $offer->getCategory(),
            'form' => $form->createView(),
            'categories' => $this->getCategories(),
        ]);
    }

    #[Route(path: ['de' => '/{id}/zip', 'en' => '/{id}/zip'], name: 'offer_zip', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function zip(Offer $offer): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            return $this->redirectToRoute('security_login');
        }
        $archive_file_name = 'a.zip';

        // function zipFilesAndDownload($file_names, $archive_file_name, $file_path)
        $zip = new \ZipArchive();
        // WE REUSED THE $file_path VARIABLE HERE THEN ADDED zipped FOLDER
        if (true !== $zip->open($this->getParameter('kernel.project_dir').'/zipped/'.$archive_file_name, \ZipArchive::CREATE)) {
            exit('cannot open <'.$archive_file_name.'>');
        }
        // add each files of $file_name array to archive
        //        foreach ($file_names as $files) {
        //            $zip->addFile($file_path.$files, $files);
        //        }
        $zip->close();
        // then send the headers to force download the zip file
        header('Content-type: application/zip');
        header("Content-Disposition: attachment; filename=$archive_file_name");
        header('Content-length: '.filesize('zipped/'.$archive_file_name));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile("$archive_file_name");
        exit;
    }

    #[Route(['de' => '/{offer}/loeschen', 'en' => '/offer/{offer}/delete'], name: 'offer_delete_offer', methods: ['POST', 'GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function delete(Offer $offer, string $hdDir): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('security_login');
        }
        $customer = $offer->getCustomer();
        $this->fa->delete('find_offer_main_products'.$offer->getId());

        $this->log(
            'delete',
            'Angebot '.$offer->getNumber().' gelöscht.',
            'Angebot '.$offer->getNumber().' wurde von '.$this->getUser()->getName().' gelöscht',
            $offer->getCustomer(),
        );

        $this->deleteOffer($offer, $hdDir);
        // $offer->setStatus('deleted');
        $this->em->persist($offer);
        $this->em->flush();
        $this->addFlash('success', $this->translator->trans('o.success.delete'));

        return $this->redirectToRoute('customer_edit', ['id' => $customer->getId()]);
    }

    #[Route(path: ['de' => '/{id}', 'en' => '/{id}'], name: 'offer_show', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function show(Offer $offer): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            return $this->redirectToRoute('security_login');
        }

        if ('call-plus' === $offer->getStatus()) {
            return $this->redirectToRoute('offer_edit', ['id' => $offer->getId()]);
        }

        if (empty($offer->getCategory()) || empty($offer->getSubCategory())) {
            return $this->redirectToRoute('offer_edit', ['id' => $offer->getId()]);
        }
        $offerItem = new OfferItem();
        $offerItem->setOffer($offer);
        $offerItem->setTax(19);
        $form = $this->createForm(OfferItemType::class, $offerItem);
        $items = [];
        $i = 0;
        foreach ($offer->getOfferItems() as $item) {
            if ($item->getItem() instanceof Product) {
                $items[$item->getItem()->getProductNumber().$i] = $item;
            } else {
                $items['00.'.sprintf('%2d', $i)] = $item;
            }
            ++$i;
        }
        $context = !empty($offer->getOption()) ? $offer->getOption()->getContext() : [];

        if (empty($context['kv'])) {
            $this->setNewKeyValue($offer);
        }
        ksort($items);
        $boxform = $this->createForm(OfferWallboxType::class, $offer);
        $boxformEmpty = $this->createForm(OfferEmptyWallboxType::class, $offer);

        if (empty($offer->getContext())) {
            $this->setNewInvoicePercent($offer);
        }

        $imageForm = $this->createForm(OfferImageType::class, $offer);

        return $this->render('app/offer_new/offer.html.twig', [
            'offer' => $offer,
            'addOfferForm' => $form->createView(),
            'items' => $items,
            'boxform' => $boxform->createView(),
            'boxformEmpty' => $boxformEmpty->createView(),
            'total' => 0,
            'imageForm' => $imageForm->createView(),
        ]);
    }
}
