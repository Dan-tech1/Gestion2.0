<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Workspace;
use App\Form\UserType;
use App\Form\WorkspaceType;
use App\Repository\IssueRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        WorkspaceRepository $workspaceRepo,
        ProjectRepository $projectRepo,
        IssueRepository $issueRepo
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'users' => $userRepo->findAll(),
            'workspaces' => $workspaceRepo->findAll(),
            'projects' => $projectRepo->findAll(),
            'issues' => $issueRepo->findBy(['resolved' => false]),
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepo): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/user/new', name: 'admin_user_new')]
    public function newUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès!');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès!');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte!');
            return $this->redirectToRoute('admin_users');
        }

        // Vérifier si l'utilisateur a créé des projets
        if (count($user->getCreatedProjects()) > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il a créé des projets.');
            return $this->redirectToRoute('admin_users');
        }

        // Désassigner les tâches
        foreach ($user->getAssignedTasks() as $task) {
            $task->setAssignedTo(null);
        }

        // Retirer le rôle de manager des espaces de travail
        foreach ($user->getManagedWorkspaces() as $workspace) {
            $workspace->setManager(null);
        }

        // Supprimer les problèmes signalés
        foreach ($user->getReportedIssues() as $issue) {
            $em->remove($issue);
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès!');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/projects', name: 'admin_projects')]
    public function projects(ProjectRepository $projectRepo): Response
    {
        return $this->render('admin/projects.html.twig', [
            'projects' => $projectRepo->findAll(),
        ]);
    }

    #[Route('/workspaces', name: 'admin_workspaces')]
    public function workspaces(WorkspaceRepository $workspaceRepo): Response
    {
        return $this->render('admin/workspaces.html.twig', [
            'workspaces' => $workspaceRepo->findAll(),
        ]);
    }

    #[Route('/workspace/new', name: 'admin_workspace_new')]
    public function newWorkspace(Request $request, EntityManagerInterface $em): Response
    {
        $workspace = new Workspace();
        $form = $this->createForm(WorkspaceType::class, $workspace);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($workspace);
            $em->flush();

            $this->addFlash('success', 'Espace de travail créé avec succès!');
            return $this->redirectToRoute('admin_workspaces');
        }

        return $this->render('admin/workspace_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/workspace/{id}/edit', name: 'admin_workspace_edit')]
    public function editWorkspace(Workspace $workspace, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(WorkspaceType::class, $workspace);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Espace de travail modifié avec succès!');
            return $this->redirectToRoute('admin_workspaces');
        }

        return $this->render('admin/workspace_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'workspace' => $workspace,
        ]);
    }

    #[Route('/system/lock', name: 'admin_system_lock', methods: ['POST'])]
    public function lockSystem(UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            if ($user !== $this->getUser()) {
                $user->setIsActive(false);
            }
        }
        $em->flush();

        $this->addFlash('warning', 'Système verrouillé! Tous les comptes sauf le vôtre sont désactivés.');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/system/unlock', name: 'admin_system_unlock', methods: ['POST'])]
    public function unlockSystem(UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            $user->setIsActive(true);
        }
        $em->flush();

        $this->addFlash('success', 'Système déverrouillé! Tous les comptes sont réactivés.');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/issue/{id}/resolve', name: 'admin_resolve_issue', methods: ['POST'])]
    public function resolveIssue(int $id, EntityManagerInterface $em, IssueRepository $issueRepo): JsonResponse
    {
        $issue = $issueRepo->find($id);

        if (!$issue) {
            return new JsonResponse(['success' => false], 404);
        }

        $issue->setResolved(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}