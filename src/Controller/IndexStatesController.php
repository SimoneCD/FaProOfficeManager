<?php

namespace App\Controller;

use App\Entity\IndexStates;
use App\Form\IndexStatesType;
use App\Repository\IndexStatesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/index/states')]
class IndexStatesController extends AbstractController
{
    #[Route('/', name: 'app_index_states_index', methods: ['GET'])]
    public function index(IndexStatesRepository $indexStatesRepository): Response
    {
        return $this->render('index_states/index.html.twig', [
            'index_states' => $indexStatesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_index_states_new', methods: ['GET', 'POST'])]
    public function new(Request $request, IndexStatesRepository $indexStatesRepository): Response
    {
        $indexState = new IndexStates();
        $form = $this->createForm(IndexStatesType::class, $indexState);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $indexStatesRepository->save($indexState, true);

            return $this->redirectToRoute('app_index_states_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('index_states/new.html.twig', [
            'index_state' => $indexState,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_index_states_show', methods: ['GET'])]
    public function show(IndexStates $indexState): Response
    {
        return $this->render('index_states/show.html.twig', [
            'index_state' => $indexState,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_index_states_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, IndexStates $indexState, IndexStatesRepository $indexStatesRepository): Response
    {
        $form = $this->createForm(IndexStatesType::class, $indexState);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $indexStatesRepository->save($indexState, true);

            return $this->redirectToRoute('app_index_states_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('index_states/edit.html.twig', [
            'index_state' => $indexState,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_index_states_delete', methods: ['POST'])]
    public function delete(Request $request, IndexStates $indexState, IndexStatesRepository $indexStatesRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$indexState->getId(), $request->request->get('_token'))) {
            $indexStatesRepository->remove($indexState, true);
        }

        return $this->redirectToRoute('app_index_states_index', [], Response::HTTP_SEE_OTHER);
    }
}
