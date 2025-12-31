<?php

namespace App\Controller;

use App\Entity\Issue;
use App\Entity\Task;
use App\Form\IssueType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/task/{id}/status', name: 'user_task_status', methods: ['POST'])]
    public function changeTaskStatus(Task $task, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($task->getAssignedTo() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $status = $request->request->get('status');
        if (!in_array($status, [Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED])) {
            return new JsonResponse(['success' => false, 'message' => 'Statut invalide'], 400);
        }

        $task->setStatus($status);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'status' => $status,
            'statusLabel' => $status === Task::STATUS_COMPLETED ? 'Terminé' : 'En cours'
        ]);
    }

    #[Route('/task/{id}/report', name: 'user_report_issue')]
    public function reportIssue(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $project = $task->getProject();
        if (!$project->isUserMember($this->getUser()) && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $issue = new Issue();
        $issue->setTask($task);
        $issue->setReportedBy($this->getUser());

        $form = $this->createForm(IssueType::class, $issue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($issue);
            $em->flush();

            $this->addFlash('success', 'Problème signalé avec succès!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('user/report_issue.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }
}