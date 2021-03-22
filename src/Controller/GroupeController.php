<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GroupeController extends AbstractController
{
    #[Route('/groupe', name: 'groupe')]
    public function index(): Response
    {


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

        $groupeRepo = $em->getRepository(Groupe::class);
        /** @var Groupe $groupe */
        $groupe = $groupeRepo->find(1);

        $groupe->addParticipant($participant);
        $em->persist($groupe);
        $em->flush();


        return $this->render('groupe/index.html.twig', [
            'controller_name' => 'GroupeController',
            "title" => "Groupe"
        ]);
    }
}
