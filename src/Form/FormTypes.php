<?php

namespace App\Form;

use App\Entity\Workspace;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Issue;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkspaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom de l\'espace'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('manager', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Chef de projet',
                'required' => false,
                'query_builder' => function (UserRepository $repo) {
                    return $repo->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_PROJECT_MANAGER%');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Workspace::class]);
    }
}

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du projet'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => Project::STATUS_IN_PROGRESS,
                    'Terminé' => Project::STATUS_COMPLETED,
                ],
            ]);

        if ($options['show_workspace']) {
            $builder->add('workspace', EntityType::class, [
                'class' => Workspace::class,
                'choice_label' => 'name',
                'label' => 'Espace de travail',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'show_workspace' => true,
        ]);
    }
}

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre de la tâche'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => Task::STATUS_IN_PROGRESS,
                    'Terminé' => Task::STATUS_COMPLETED,
                ],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date limite',
                'widget' => 'single_text',
                'required' => false,
            ]);

        if ($options['show_assignee']) {
            $project = $options['project'];
            $builder->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Assigné à',
                'required' => false,
                'choices' => $project ? $this->getProjectMembers($project) : [],
            ]);
        }
    }

    private function getProjectMembers(Project $project): array
    {
        $members = [];
        foreach ($project->getProjectMembers() as $member) {
            $members[] = $member->getUser();
        }
        return $members;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'show_assignee' => true,
            'project' => null,
        ]);
    }
}

class IssueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre du problème'])
            ->add('description', TextareaType::class, ['label' => 'Description']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Issue::class]);
    }
}