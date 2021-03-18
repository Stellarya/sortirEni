<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\User;
use App\Form\RegisterImportType;
use App\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @Route("/admin/users/{id<\d+>?0}", name="admin_users")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $id
     * @return Response
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, int $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = new User();
        $participant = new Participant();
        $form = $this->createForm(RegisterType::class);
        $participantForm = $form->get("participant");
        $userForm = $form->get("user");

        // si on a un id dans l'url on charge cet user pour pouvoir l'éditer, sinon on en créera un nouveau
        if ($id > 0) {
            $userRepo = $entityManager->getRepository(User::class);
            /** @var User $user */
            $user = $userRepo->find($id);
            if (!$user) {
                throw new NotFoundHttpException("Utilisateur non trouvé");
            }
            /** @var Participant $participant */
            $participant = $user->getParticipant();
            $userForm->remove("password");
        }

        $participantForm->remove("estInscrit");
        $userForm->remove("roles");
        $userForm->remove("old_password");
        $userForm->remove("new_password");
        $participantForm->setData($participant);
        $userForm->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getConnection()->beginTransaction();

            //save participant
            $participant->setActif(true);
            $entityManager->persist($participant);
            $entityManager->flush();

            // save user
            $user->setRoles($user->getRoles());
            $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
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
     * @Route("/admin/users/import", name="admin_users_import")
     */
    public function register_import(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        $form = $this->createForm(RegisterImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getConnection()->beginTransaction();

            try {
                /** @var UploadedFile $file */
                $file = $form["liste_participants"]->getData();
                $pathname = $file->getPathname();
                if ($file->getClientOriginalExtension() !== "csv") {
                    throw new \Exception("Le fichier n'est pas au bon format (csv)");
                }
                $handle = fopen($pathname, "r");
                $cnt = 0;
                $em = $this->getDoctrine()->getManager();

                while(($buffer = fgetcsv($handle, 1000, ";")) !== false) {
                    $siteRepo = $em->getRepository(Site::class);
                    /** @var Site $site */
                    $site = $siteRepo->find($buffer[8]);

                    $participant = new Participant();
                    $participant->setNom($buffer[4]);
                    $participant->setPrenom($buffer[5]);
                    $participant->setTelephone($buffer[6]);
                    $participant->setActif(boolval($buffer[7]));
                    $participant->setEstRattacheA($site);
                    $em->persist($participant);
                    $em->flush();

                    $user = new User();
                    $user->setUsername($buffer[0]);
                    $user->setPassword($passwordEncoder->encodePassword($user, $buffer[1]));
                    $user->setEmail($buffer[2]);
                    $a_roles = ["ROLE_USER"];
                    if (boolval($buffer[3])) {
                        $a_roles[] = "ROLE_ADMIN";
                    }
                    $user->setRoles($a_roles);
                    $user->setParticipant($participant);
                    $user->checkFieldsValidity();
                    $em->persist($user);
                    $em->flush();

                    $cnt++;
                }

                $this->getDoctrine()->getConnection()->commit();
                $this->addFlash("success", "Import réussi !");


            } catch (\Exception $e) {
                $this->getDoctrine()->getConnection()->rollback();
                $this->addFlash("alert", "Erreur lors de l'import de la ligne {$cnt} ".PHP_EOL." {$e->getMessage()}");
            }
            fclose($handle);

        }

        return $this->render(
            'admin/register_import.html.twig', [
                'form' => $form->createView(),
                "title" => "Import d'une liste d'utilisateur"
            ]
        );
    }

    /**
     * @Route("/admin/users/list", name="admin_users_list")
     */
    public function list_users(Request $request): Response {

        $em = $this->getDoctrine()->getRepository(User::class);
        $list_users = $em->findAll();

        return $this->render(
            'admin/list_users.html.twig', [
                "list_users" => $list_users,
                "title" => "Liste des utilisateurs"
            ]
        );
    }
}
