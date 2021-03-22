<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Repository\GroupeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GroupeController extends AbstractController
{
    /**
     * @Route("/groupe", name="groupe")
     */
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




        return $this->render('groupe/index.html.twig', [
            'controller_name' => 'GroupeController',
            "title" => "Groupe"
        ]);
    }
}
