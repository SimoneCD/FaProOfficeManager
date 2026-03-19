<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProjectTeam;
use App\Entity\ProjectTeamCategory;
use App\Form\ProjectTeamCategoryType;
use App\Form\ProjectTeamType;
use App\Repository\ProjectTeamCategoryRepository;
use App\Repository\ProjectTeamRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * User Teams for Montagen.
 */
#[Route(['de' => '/admin/projekt-teams', 'en' => '/admin/project-teams'])]
class ProjectTeamController extends BaseController
{
    #[Route('/', name: 'app_project_team_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $projectTeamCategory = new ProjectTeamCategory();
        $projectTeamCategory->setIntern(false);
        $form = $this->createForm(ProjectTeamCategoryType::class, $projectTeamCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ept = $this->em->getRepository(ProjectTeamCategory::class)->findOneBy([
                'name' => trim($projectTeamCategory->getName()),
            ]);
            if (!$ept instanceof ProjectTeamCategory) {
                $projectTeamCategory->setName(trim($projectTeamCategory->getName()));
                $this->em->persist($projectTeamCategory);
                $this->em->flush();
            }
        }

        return $this->render('project_team/index.html.twig', [
            'project_team_categories' => $this->getTeamCategories(),
            'project_teams' => $this->getTeams(),
            'categoryForm' => $form->createView(),
        ]);
    }

    #[Route(['de' => '/erstellen', 'en' => '/create'], name: 'app_project_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProjectTeamRepository $projectTeamRepository): Response
    {
        $projectTeam = new ProjectTeam();
        $form = $this->createForm(ProjectTeamType::class, $projectTeam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectTeamRepository->save($projectTeam, true);
            $this->fa->delete('teams');

            return $this->redirectToRoute('app_project_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('project_team/new.html.twig', [
            'project_team' => $projectTeam,
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'app_project_team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProjectTeam $projectTeam, ProjectTeamRepository $projectTeamRepository): Response
    {
        $form = $this->createForm(ProjectTeamType::class, $projectTeam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectTeamRepository->save($projectTeam, true);
            $this->fa->delete('teams');

            return $this->redirectToRoute('app_project_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('project_team/edit.html.twig', [
            'project_team' => $projectTeam,
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/kategorie-loeschen', 'en' => '/{id}/category-delete'], name: 'app_project_team_cat_delete', methods: ['GET', 'POST'])]
    public function deleteCat(Request $request, ProjectTeamCategory $projectTeamCategory, ProjectTeamCategoryRepository $projectTeamCategoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$projectTeamCategory->getId(), $request->request->get('_token'))) {
            $projectTeamCategoryRepository->remove($projectTeamCategory, true);
            $this->fa->delete('teams');
        }

        return $this->redirectToRoute('app_project_team_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'app_project_team_delete', methods: ['POST'])]
    public function delete(Request $request, ProjectTeam $projectTeam, ProjectTeamRepository $projectTeamRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$projectTeam->getId(), $request->request->get('_token'))) {
            $projectTeamRepository->remove($projectTeam, true);
            $this->fa->delete('teams');
        }

        return $this->redirectToRoute('app_project_team_index', [], Response::HTTP_SEE_OTHER);
    }
}
