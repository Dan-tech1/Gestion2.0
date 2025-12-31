<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\ProjectMember;
use App\Entity\ProjectImage;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Repository\WorkspaceRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\IssueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project-manager')]
#[IsGranted('ROLE_PROJECT_MANAGER')]
class ProjectManagerController extends AbstractController
{
    #[Route('/project/new', name: 'pm_project_new')]
    public function newProject(
        Request $request,
        EntityManagerInterface $em,
        WorkspaceRepository $workspaceRepo
    ): Response {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setCreatedBy($this->getUser());
            $em->persist($project);
            $em->flush();

            $this->addFlash('success', 'Projet créé avec succès!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('project_manager/project_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/project/{id}', name: 'pm_project_show')]
    public function showProject(Project $project, IssueRepository $issueRepo): Response
    {
        if (!$project->isUserManager($this->getUser()) && !$project->isUserMember($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $issues = $issueRepo->createQueryBuilder('i')
            ->join('i.task', 't')
            ->where('t.project = :project')
            ->andWhere('i.resolved = false')
            ->setParameter('project', $project)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('project_manager/project_show.html.twig', [
            'project' => $project,
            'issues' => $issues,
        ]);
    }

    #[Route('/project/{id}/test', name: 'pm_project_test')]
    public function test(Project $project): Response
    {
        return new Response('Test route works for project ' . $project->getId());
    }

    public function editProject(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$project->isUserManager($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProjectType::class, $project, ['show_workspace' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Projet modifié avec succès!');
            return $this->redirectToRoute('pm_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project_manager/project_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'project' => $project,
        ]);
    }

    #[Route('/project/{id}/task/new', name: 'pm_task_new')]
    public function newTask(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$project->isUserManager($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $task = new Task();
        $task->setProject($project);
        $form = $this->createForm(TaskType::class, $task, ['project' => $project]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Tâche créée avec succès!');
            return $this->redirectToRoute('pm_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project_manager/task_form.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'is_edit' => false,
        ]);
    }

    #[Route('/task/{id}/edit', name: 'pm_task_edit')]
    public function editTask(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $project = $task->getProject();
        if (!$project->isUserManager($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TaskType::class, $task, ['project' => $project]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès!');
            return $this->redirectToRoute('pm_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project_manager/task_form.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'is_edit' => true,
            'task' => $task,
        ]);
    }

    #[Route('/project/{id}/members', name: 'pm_project_members')]
    public function projectMembers(Project $project, UserRepository $userRepo): Response
    {
        if (!$project->isUserManager($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $allUsers = $userRepo->findAll();
        $currentMembers = [];
        foreach ($project->getProjectMembers() as $member) {
            $currentMembers[] = $member->getUser()->getId();
        }

        return $this->render('project_manager/project_members.html.twig', [
            'project' => $project,
            'all_users' => $allUsers,
            'current_members' => $currentMembers,
        ]);
    }

    #[Route('/project/{id}/member/add', name: 'pm_add_member', methods: ['POST'])]
    public function addMember(Project $project, Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        if (!$project->isUserManager($this->getUser())) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $userId = $request->request->get('user_id');
        $user = $userRepo->find($userId);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        if ($project->isUserMember($user)) {
            return new JsonResponse(['success' => false, 'message' => 'L\'utilisateur est déjà membre'], 400);
        }

        $member = new ProjectMember();
        $member->setProject($project);
        $member->setUser($user);

        $em->persist($member);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Membre ajouté avec succès']);
    }

    #[Route('/project/{projectId}/member/{userId}/remove', name: 'pm_remove_member', methods: ['POST'])]
    public function removeMember(
        int $projectId,
        int $userId,
        ProjectRepository $projectRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $project = $projectRepo->find($projectId);
        $user = $userRepo->find($userId);

        if (!$project || !$user) {
            return new JsonResponse(['success' => false, 'message' => 'Projet ou utilisateur non trouvé'], 404);
        }

        if (!$project->isUserManager($this->getUser())) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser() === $user) {
                $em->remove($member);
                $em->flush();
                return new JsonResponse(['success' => true, 'message' => 'Membre retiré avec succès']);
            }
        }

        return new JsonResponse(['success' => false, 'message' => 'Membre non trouvé'], 404);
    }

    #[Route('/issue/{id}/resolve', name: 'pm_resolve_issue', methods: ['POST'])]
    public function resolveIssue(int $id, EntityManagerInterface $em, IssueRepository $issueRepo): JsonResponse
    {
        $issue = $issueRepo->find($id);

        if (!$issue) {
            return new JsonResponse(['success' => false], 404);
        }

        $project = $issue->getTask()->getProject();
        if (!$project->isUserManager($this->getUser())) {
            return new JsonResponse(['success' => false], 403);
        }

        $issue->setResolved(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}