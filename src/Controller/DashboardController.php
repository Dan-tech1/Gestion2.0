<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\IssueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        ProjectRepository $projectRepo,
        TaskRepository $taskRepo,
        IssueRepository $issueRepo
    ): Response {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($this->isGranted('ROLE_PROJECT_MANAGER')) {
            $managedProjects = $projectRepo->findManagedProjects($user);
            $memberProjects = $projectRepo->findMemberProjects($user);
            $issues = $issueRepo->findByProjectManager($user);

            return $this->render('project_manager/dashboard.html.twig', [
                'managed_projects' => $managedProjects,
                'member_projects' => $memberProjects,
                'issues' => $issues,
            ]);
        }

        $projects = $projectRepo->findProjectsByUser($user);
        $tasks = $taskRepo->findTasksByUser($user);

        return $this->render('user/dashboard.html.twig', [
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }
}