<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\EditProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{


    /**
     * @Route("/profile/{id<\d+>?0}", name="profile_view")
     * @param Request $request
     * @return Response
     */
    public function profile(Request $request, int $id) {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        $entityManager = $this->getDoctrine()->getManager();

        if ($id > 0) {
            $user = $entityManager->getRepository(User::class)->find($id);
            $title = "Profile de {$user->getParticipant()->getPrenom()}";
        } else {
            $user = $this->getUser();
            $title = "Mon profil";
        }

        $sortieRepo = $entityManager->getRepository(Sortie::class);
        $nbSorties = count($sortieRepo->findSortiesByParticipant($user->getParticipant()->getId()));

        return $this->render('profile/view.html.twig', [
            "controller_name" => "ProfileController",
            "user" => $user,
            "nbSorties" => $nbSorties,
            "title" => $title,
        ]);

    }


    /**
     * @Route("/profile/edit", name="profile_edit")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function profile_edit(Request $request, UserPasswordEncoderInterface $passwordEncoder, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $form = $this->createForm(EditProfileType::class);
        $participantForm = $form->get("participant");
        $participantForm->remove("actif");
        $participantForm->remove("estRattacheA");
        $participantForm->remove("estInscrit");
        $userForm = $form->get("user");
        $userForm->remove("roles");
        $userForm->remove("password");

        /** @var User $user */
        $user = $this->getUser();
        /** @var Participant $participant */
        $participant = $user->getParticipant();
        $participantForm->setData($participant);
        $userForm->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->getDoctrine()->getConnection()->beginTransaction();
                try {
                    $old_passwd = $userForm["old_password"]->getData();
                    if (isset($old_passwd)) {
                        if (!$passwordEncoder->isPasswordValid($user, $old_passwd)) {
                            throw new \Exception("L'ancien mot de passe saisi ne correspond pas à celui attendu.");
                        }
                        $user->setPassword($passwordEncoder->encodePassword($user, $userForm["new_password"]->getData()));
                    }

                    $avatarFile = $userForm["avatar"]->getData();
                    if ($avatarFile) {
                        $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                        // this is needed to safely include the file name as part of the URL
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

                        // Move the file to the directory where brochures are stored
                        try {
                            $avatarFile->move(
                                $this->getParameter('avatar_directory'),
                                $newFilename
                            );
                            $user->setAvatar($newFilename);
                        } catch (FileException $e) {
                            $this->addFlash("alert", $e->getMessage());
                        }
                    }

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($participant);
                    $em->persist($user);
                    $em->flush();
                    $this->getDoctrine()->getConnection()->commit();
                    $this->addFlash("success", "Profil édité avec succès !");
                } catch (\Exception $e) {
                    $this->getDoctrine()->getConnection()->rollback();
                    $this->addFlash("alert", $e->getMessage());
                }
            } else {
                foreach($form->getErrors(true) as $e) {
                    $this->addFlash("alert", $e->getMessage());
                }
            }
        }

        return $this->render('profile/edit.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form->createView(),
            'title' => 'Édition profil'
        ]);
    }
}
