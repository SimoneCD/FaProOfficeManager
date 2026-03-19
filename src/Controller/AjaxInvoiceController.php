<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Invoice;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-invoice')]
class AjaxInvoiceController extends BaseController
{
    #[Route(path: '/{id}/update/context', name: 'ajax_update_invoice_context', methods: ['POST'])]
    public function updateInvoiceContext(Request $request, Invoice $invoice): JsonResponse
    {
        $post = $request->request->all();
        if (empty($post)) {
            $post = $request->getContentTypeFormat();
        }
        $postContext = $post['invoice_option']['context'];
        $invoiceContext = $invoice->getContext();
        $this->addInvoiceContext($postContext, $invoiceContext);
        $invoice->setContext($invoiceContext);
        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json($invoiceContext);
    }
}
