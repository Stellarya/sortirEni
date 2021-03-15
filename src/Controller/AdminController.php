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
     * @Route("/admin/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response {
        // 1) build the form
        $participant = new Participant();
        $form = $this->createForm(RegisterType::class, $participant);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getConnection()->beginTransaction();

            //save participant
            $participant->setAdministrateur(false);
            $participant->setActif(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($participant);
            $entityManager->flush();

            // save user
            $data = $form->getData();
            $user = new User();
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

            return $this->redirectToRoute('home');
        }

        return $this->render(
            'admin/register.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/admin/register_csv", name="register_csv")
     */
    public function register_csv(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response {

        foreach ([] as $a_user) {

        }

        return $this->render(
            'admin/register_csv.html.twig'
        );
    }
}
