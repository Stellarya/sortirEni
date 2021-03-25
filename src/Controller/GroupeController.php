<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\GroupeType;
use App\Repository\GroupeRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GroupeController extends AbstractController
{
    /**
     * @Route("/groupe", name="groupe_list")
     */
    public function groupe_list(): Response
    {
        $em = $this->getDoctrine()->getManager();
        /** @var GroupeRepository $groupeRepo */
        $groupeRepo = $em->getRepository(Groupe::class);
        $listeGroupes = array_unique(array_merge(
            $groupeRepo->findByParticipant($this->getUser()->getParticipant()),
            $groupeRepo->findByOwner($this->getUser()->getParticipant())
        ), SORT_REGULAR);

        return $this->render('groupe/liste.html.twig', [
            'controller_name' => 'GroupeController',
            "listeGroupes" => $listeGroupes,
            "title" => "Mes groupes"
        ]);
    }

    /**
     * @Route("/groupe/{id}", name="groupe_view")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function groupe_view(int $id, EntityManagerInterface $em): Response {
        /** @var GroupeRepository $groupeRepo */
        $groupeRepo = $em->getRepository(Groupe::class);
        /** @var Groupe $groupe */
        $groupe = $groupeRepo->find($id);

        if (!$groupe->getParticipants()->contains($this->getUser())) {
            // throw "vous n'est pas membre"
        }

        /** @var SortieRepository $sortieRepo */
        $sortieRepo = $em->getRepository(Sortie::class);
        $listeSorties = $sortieRepo->findSortiesByGroupe($id);


        return $this->render('groupe/view.html.twig', [
            'controller_name'       => 'GroupeController',
            "groupe"                => $groupe,
            "title"                 => $groupe->getLibelle(),
            "sorties"         => $listeSorties,
        ]);

    }

    /**
     * @Route("/groupe/edit/{id<\d+>?0}", name="groupe_edit", priority="1")
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function groupe_edit(Request $request, int $id): Response {
        $em = $this->getDoctrine()->getManager();
        /** @var GroupeRepository $groupeRepo */
        $groupeRepo = $em->getRepository(Groupe::class);
        $groupe = $groupeRepo->find($id);

        $form = $this->createForm(GroupeType::class);
        $form->setData($groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($form->get('Supprimer')->isClicked()) {
                    $em->remove($groupe);
                    $em->flush();
                    return $this->redirectToRoute("groupe_list");
                } else {
                    $em->persist($groupe);
                    $em->flush();
                    return $this->redirectToRoute("groupe_view", ["id" => $groupe->getId()]);
                }
            } else {
                foreach($form->getErrors(true) as $e) {
                    $this->addFlash("alert", $e->getMessage());
                }
            }
        }

        return $this->render('groupe/edit.html.twig', [
            'controller_name'       => 'GroupeController',
            "form"                  => $form->createView(),
            "title"                 => "Modifier: {$groupe->getLibelle()}"
        ]);
    }

    /**
     * @Route("/groupe/add", name="groupe_add", priority="1")
     * @param Request $request
     * @return Response
     */
    public function groupe_add(Request $request): Response {
        $em = $this->getDoctrine()->getManager();
        $groupe = new Groupe();
        $groupe->setOwner($this->getUser()->getParticipant());

        $form = $this->createForm(GroupeType::class);
        $form->remove("Supprimer");
        $form->setData($groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($groupe);
                $em->flush();
                return $this->redirectToRoute("groupe_view", ["id" => $groupe->getId()]);
            } else {
                foreach($form->getErrors(true) as $e) {
                    $this->addFlash("alert", $e->getMessage());
                }
            }
        }

        return $this->render('groupe/add.html.twig', [
            'controller_name'       => 'GroupeController',
            "form"                  => $form->createView(),
            "title"                 => "Création d'un nouveau groupe"
        ]);
    }

    public function test() {


        $em = $this->getDoctrine()->getManager();

        $participantRepo = $em->getRepository(Participant::class);
        /** @var Participant $participant */
        $participant = $participantRepo->find(26);

        /*
        $groupe = new Groupe();
        $groupe->setOwner($participant);
        $groupe->setLibelle("un groupe");
        $em->persist($groupe);
        $em->flush();
        */

        /** @var GroupeRepository $groupeRepo */
        $groupeRepo = $em->getRepository(Groupe::class);
        /** @var Groupe $groupe */
        $groupe = $groupeRepo->find(1);

        /*
        $groupe->addParticipant($participantRepo->find(32));
        $groupe->addParticipant($participantRepo->find(33));
        $groupe->addParticipant($participantRepo->find(34));
        $em->persist($groupe);
        $em->flush();
        */

        /** @var Participant $participant32 */
        /** @var Participant $participant33 */
        /** @var Participant $participant34 */
        $participant32 = $participantRepo->find(32);
        $participant33 = $participantRepo->find(33);
        $participant34 = $participantRepo->find(34);

        $list = $groupeRepo->findByOwner($participant);
        var_dump($list);die;



    }
}
