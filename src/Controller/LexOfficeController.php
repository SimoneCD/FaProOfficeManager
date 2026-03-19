<?php

namespace App\Controller;

use App\Service\LexOfficeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: 'lex-office')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class LexOfficeController extends AbstractController
{
    #[Route('/invoices', name: 'app_lex_office')]
    public function index(LexOfficeService $officeService): Response
    {
        return $this->render('lex_office/index.html.twig', [
            'invoices' => $officeService->getContact(),
            'contacts' => $officeService->getVoucherList(),
        ]);
    }

    #[Route('/upload-part-invoice', name: 'app_lex_upload_part_invoice')]
    public function uploadPartInvoice(LexOfficeService $officeService): Response
    {
        return $this->render('lex_office/index.html.twig', [
            'invoices' => json_decode($officeService->getAllInvoices()),
        ]);
    }

    #[Route('/upload-invoice', name: 'app_lex_upload_invoice')]
    public function uploadInvoice(LexOfficeService $officeService): Response
    {
        return $this->render('lex_office/index.html.twig', [
            'invoices' => json_decode($officeService->getAllInvoices()),
        ]);
    }
}
