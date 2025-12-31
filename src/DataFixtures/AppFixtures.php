<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\ProjectMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un administrateur
        $admin = new User();
        $admin->setUsername('Admin');
        $admin->setEmail('admin@taskmanager.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsActive(true);
        $manager->persist($admin);

        // Créer un chef de projet
        $manager1 = new User();
        $manager1->setUsername('Chef Projet');
        $manager1->setEmail('manager@taskmanager.com');
        $manager1->setRoles(['ROLE_PROJECT_MANAGER']);
        $manager1->setPassword($this->passwordHasher->hashPassword($manager1, 'manager123'));
        $manager1->setIsActive(true);
        $manager->persist($manager1);

        // Créer un utilisateur normal
        $user1 = new User();
        $user1->setUsername('Utilisateur');
        $user1->setEmail('user@taskmanager.com');
        $user1->setRoles(['ROLE_USER']);
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'user123'));
        $user1->setIsActive(true);
        $manager->persist($user1);

        // Créer un autre utilisateur
        $user2 = new User();
        $user2->setUsername('Jean Dupont');
        $user2->setEmail('jean.dupont@taskmanager.com');
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'user123'));
        $user2->setIsActive(true);
        $manager->persist($user2);

        // Créer un espace de travail
        $workspace = new Workspace();
        $workspace->setName('Espace Développement Web');
        $workspace->setDescription('Espace dédié aux projets de développement web');
        $workspace->setManager($manager1);
        $manager->persist($workspace);

        // Créer un projet
        $project = new Project();
        $project->setName('Application de Gestion');
        $project->setDescription('Développement d\'une application de gestion de tâches');
        $project->setStatus(Project::STATUS_IN_PROGRESS);
        $project->setCreatedBy($manager1);
        $project->setWorkspace($workspace);
        $manager->persist($project);

        // Ajouter des membres au projet
        $projectMember1 = new ProjectMember();
        $projectMember1->setProject($project);
        $projectMember1->setUser($user1);
        $manager->persist($projectMember1);

        $projectMember2 = new ProjectMember();
        $projectMember2->setProject($project);
        $projectMember2->setUser($user2);
        $manager->persist($projectMember2);

        // Créer des tâches
        $task1 = new Task();
        $task1->setTitle('Concevoir la base de données');
        $task1->setDescription('Créer le schéma de la base de données avec toutes les entités nécessaires');
        $task1->setStatus(Task::STATUS_COMPLETED);
        $task1->setProject($project);
        $task1->setAssignedTo($user1);
        $task1->setDueDate(new \DateTimeImmutable('+7 days'));
        $manager->persist($task1);

        $task2 = new Task();
        $task2->setTitle('Développer l\'interface utilisateur');
        $task2->setDescription('Créer les templates Twig et intégrer Tailwind CSS');
        $task2->setStatus(Task::STATUS_IN_PROGRESS);
        $task2->setProject($project);
        $task2->setAssignedTo($user2);
        $task2->setDueDate(new \DateTimeImmutable('+14 days'));
        $manager->persist($task2);

        $task3 = new Task();
        $task3->setTitle('Implémenter l\'authentification');
        $task3->setDescription('Mettre en place le système de connexion et de gestion des rôles');
        $task3->setStatus(Task::STATUS_IN_PROGRESS);
        $task3->setProject($project);
        $task3->setAssignedTo($user1);
        $task3->setDueDate(new \DateTimeImmutable('+10 days'));
        $manager->persist($task3);

        $manager->flush();
    }
}