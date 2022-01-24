<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Task;
use App\Entity\Categories;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TaskController extends AbstractController
{
    /**
     * @Route("/task", name="task")
     */
    public function index(): Response
    {
        return $this->render('task/index.html.twig', [
            'controller_name' => 'TaskController',
        ]);
    }


    // Page Creation des tâches
    
    /**
     * @Route("/task/create", name="task_create")
     */
    public function create(Request $request, ManagerRegistry $doctrine): Response
    // On passe dans dans la fonction les arguments (l'objet Request  $request, et DOCTRINE) pour obtenir la reponse plus bas
    {
        // Mise en place du gestionnaire de BDD
        $entityManager = $doctrine->getManager();
        // creates a task object and initializes some data for this example
        $task = new Task();
        $task->setNameTask('écrire un Nom de tâche');
        $task->setDueDateTask(new \DateTime('Now'));

       

        $form = $this->createFormBuilder($task)
            ->add('nameTask', TextType::class, ['label' => 'Nom de la tâche', 'attr' => ['class' => 'form-control mb-3']])
            ->add('descriptionTask', TextareaType::class, ['label' => 'Decsription de la tâche','attr' => ['class' => 'form-control mb-3']])
            ->add('dueDateTask', DateType::class, ["widget"=>"single_text",'label' => 'Date de la tâche','attr' => ['class' => 'form-control mb-3']])
            ->add('category', EntityType::class, ['label' => 'Catégorie de la tâche :', 'class' => Categories::class,'choice_label' => 'libelleCategory', 'attr' => ['class' => 'form-select mb-4'],
                // used to render a select box, check boxes or radios
                // 'multiple' => true,
                // 'expanded' => true,
            ])
            ->add('priorityTask', ChoiceType::class, [
                'choices' => [
                     'Haute' => 'Haute',
                     'Normal' => 'Normal',
                     'Basse' => 'Basse',
                     ], 'label' => 'Priorité de la tâche', 'attr' => ['class' => 'form-select  mb-3']
            ])
            ->add('save', SubmitType::class, ['label' => 'Creer la tâche','attr' => ['class' => 'btn btn-primary mb-3']])
            ->getForm();


        // Validaion du formulaire (au prealable installer composer require symfony/validator)
        $form->handleRequest($request);

        // le if vaide tous les champs du formulaire de la bdd (si une des entrées est Null dans la bdd, Valid va verifier la bdd)
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();

            // Creation de la date d'enregitrement de la tâche
            $task->setCreatedDateTask(new \DateTime('Now'));

            // ... effectuer une action, telle que l'enregistrement de la tâche dans la base de données
            // indiquez à Doctrine que vous souhaitez (éventuellement) enregistrer le produit (aucune requête pour le moment)
            
            $entityManager->persist($task);
            
            // exécute réellement les requêtes (c'est-à-dire la requête INSERT)
            $entityManager->flush();
            
            // Affichage Bandeau Success
            $this->addFlash('success', 'Tâche ajoutée! Sans probleme!');
                
            // Task_Listing = Name de la route
            return $this->redirectToRoute('task_listing');
        }

        return $this->renderForm('task/create.html.twig', [
            'form' => $form,
        ]);
    }



    #[Route('/task/listing', name: 'task_listing')]
    public function listing(ManagerRegistry $doctrine): Response
    {
        $tasks = $doctrine->getRepository(Task::class)->findAll();

        return $this->render('task/listing.html.twig', [
            'tasks' => $tasks,
        ]);
    }


    // Update les taches = Modification de la tâche
    // Sur la route, nous rajoutons l'id
    #[Route('/task/editing/{id}', name: 'task_editing')]
    //une methode Update ou l'on passe en argument l'ID
    public function update(Request $request, ManagerRegistry $doctrine, int $id): Response
    {

        // recopier les code de la creation de la tache. En ne mofifiant que les element du bouton (creer la tache devient 'modifier la tache)
        $entityManager = $doctrine->getManager();

        $task = $entityManager->getRepository(task::class)->find($id);

        $form = $this->createFormBuilder($task)
            ->add('nameTask', TextType::class, ['label' => 'Nom de la tâche :', 'attr' => ['class' => 'form-control mb-4']])
            ->add('descriptionTask', TextareaType::class, ['label' => 'Description de la tâche :','attr' => ['class' => 'form-control mb-4']])
            ->add('dueDateTask', DateType::class, ["widget"=>"single_text",'label' => 'Date création de la tâche :','attr' => ['class' => 'form-control mb-4']])
            ->add('priorityTask', ChoiceType::class, ['label' => 'Priorité de la tâche :','choices' => ['Haute' => 'Haute','Normal' => 'Normal','Basse' => 'Basse',], 'attr' => ['class' => 'form-select mb-4'],])
            ->add('category', EntityType::class, ['label' => 'Catégorie de la tâche :', 'class' => Categories::class,'choice_label' => 'libelleCategory', 'attr' => ['class' => 'form-select mb-4'],])
            ->add('save', SubmitType::class, ['label' => 'Modifier la tâche','attr' => ['class' => 'btn btn-primary']])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original $task variable has also been updated
            $task = $form->getData();

            $task->setCreatedDateTask(new \DateTime('today'));

            // ... perform some action, such as saving the task to the database
            $entityManager->persist($task);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();

            // Ajout du bandeau affichage succes
            $this->addFlash('success', 'Tâche modifiée! Sans probleme!');


            return $this->redirectToRoute('task_listing');
        }
        // retourner vers la route Edditing.html.twig (nouvelle page de modification de la tâche)
        return $this->renderForm('task/editing.html.twig', [
            'form' => $form,
        ]);
    }

    // Delete de la tache: Delete de l'id dans la route
    #[Route('/task/delete/{id}', name: 'task_delete')]
    public function remove(Request $request, ManagerRegistry $doctrine, int $id): Response
    {
        // Instancier l'objet EntityManager
        $entityManager = $doctrine->getManager();

        // recuperer le getRepository
        $task = $entityManager->getRepository(task::class)->find($id);
        // Remove $task
        $entityManager->remove($task);
        // et envoi EntityManager
        $entityManager->flush();

        // Ajout du bandeau affichage
        $this->addFlash('danger', 'Tâche Supprimée!');

        return $this->redirectToRoute('task_listing');
    }
}
