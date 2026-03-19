<?php

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Offer;
use App\Form\ActionLogType;
use App\Repository\ActionLogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/action/log')]
class ActionLogController extends BaseController
{
    #[Route('/', name: 'app_action_log_index', methods: ['GET'])]
    public function index(ActionLogRepository $actionLogRepository): Response
    {
        return $this->render('action_log/index.html.twig', [
            'action_logs' => $actionLogRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_action_log_delete', methods: ['POST'])]
    public function delete(Request $request, ActionLog $actionLog, ActionLogRepository $actionLogRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$actionLog->getId(), $request->request->get('_token'))) {
            $actionLogRepository->remove($actionLog, true);
        }

        return $this->redirectToRoute('app_action_log_index', [], Response::HTTP_SEE_OTHER);
    }
}
