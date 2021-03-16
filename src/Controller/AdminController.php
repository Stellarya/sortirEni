<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use App\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    /**
     * @Route("/admin/register", name="admin_register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        // 1) build the form
        $participant = new Participant();
        $form = $this->createForm(RegisterType::class, $participant);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getConnection()->beginTransaction();

            //save participant
            $participant->setActif(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($participant);
            $entityManager->flush();

            // save user
            $data = $form->getData();
            $user = new User();
            $user->setRoles(["ROLE_USER"]);
            $user->setUsername($form["username"]->getData());
            $user->setPassword($form["password"]->getData());
            $user->setEmail($form["email"]->getData());
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            $user->setParticipant($participant);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->getDoctrine()->getConnection()->commit();

            $this->addFlash("success", "Utilisateur enregistré !");
        }

        return $this->render(
            'admin/register.html.twig', [
                'form' => $form->createView(),
                'title' => 'Inscription d\'un utilisateur'
            ]
        );
    }

    /**
     * @Route("/admin/registercsv", name="admin_registercsv")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function register_csv(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        foreach ([] as $a_user) {

        }

        return $this->render(
            'admin/register_csv.html.twig'
        );
    }
}
