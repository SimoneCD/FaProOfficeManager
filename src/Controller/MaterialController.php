<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\OfferItem;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class IndexController.
 */
#[Route('/backend/material')]
#[IsGranted('ROLE_MONTAGE')]
class MaterialController extends BaseController
{
    #[Route(path: '/', name: 'backend_material_index', methods: ['GET'])]
    public function material(Request $request): Response
    {
//        if (!$this->isGranted('ROLE_EMPLOYEE_EXTERN')) {
//            return $this->redirectToRoute('booking_index');
//        }
        $materialOrders = [];

        $offerNumber = !empty($request->query->get('offerNumberBasic')) ? $request->query->get('offerNumberBasic') : null;
        $productNumber = !empty($request->query->get('productNumber')) ? $request->query->get('productNumber') : null;
        if (!empty($offerNumber)) {
            $offer = $this->em->getRepository(Offer::class)->findOneBy([
                'number' => $offerNumber,
            ]);
            if ($offer instanceof Offer) {
                $materialOrders = $this->em->getRepository(Offer::class)->findMaterialsByOffer($offer);
            }
        } elseif (!empty($productNumber !== null)) {
            $materialOrders = $this->em->getRepository(Product::class)->findByMaterialSearch($productNumber);
        } else {
            $materialOrders = $this->em->getRepository(Offer::class)->findMaterials();
        }

        return $this->render('material/index.html.twig', [
            'offersMaterials' => $materialOrders,
            'offerNumber' => $offerNumber,
        ]);
    }


    #[Route(path: '/prices', name: 'backend_material_prices', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function dashboard(Request $request): Response
    {
        if (!$this->isGranted('ROLE_MONTAGE')) {
            return $this->redirectToRoute('public_index');
        }
        if (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            return $this->redirectToRoute('booking_index');
        }
        $user = null;
        /* @var User $user */
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
        }
        $offers = $this->em->getRepository(Offer::class)->findBySearch(null, $user, null);

        $offer = $request->query->get('offerNumber') ?? null;
        if ($offer !== null) {
            $offer = $this->em->getRepository(Offer::class)->findOneBy([
                'number' => $offer,
            ]);
        }

        return $this->render('material/prices.html.twig', [
            'offerItems' => $this->em->getRepository(OfferItem::class)->findMaterialPayedOffer($offer),
            'itemProducts' => $this->em->getRepository(Offer::class)->findPayedProducts($offer),
        ]);
    }
}
