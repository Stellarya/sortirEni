<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Repository\LieuRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\File;


class SortieType extends AbstractType
{
    public $token;

    public function __construct(TokenStorageInterface $token)
    {
        $this->token = $token;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$token = $this->token->getToken()->getUser()->getUsername();
        $builder
            ->add('nom', TextType::class)
            ->add('dateHeureDebut', DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Date de début '
                ])
            ->add('dateLimiteInscription', DateType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Date limite d\'inscription '
                ])
            ->add('duree', NumberType::class,
                [
                    'label' => 'Durée (en minutes)',
                    'html5' => true,
                ])
            ->add('nbInscriptionMax', NumberType::class,
                [
                    'label' => 'Nombre d\'inscriptions maximum',
                    'html5' => true,
                ])
            ->add('infosSortie',
                TextareaType::class,
                [
                    'label' => 'Informations sur la sortie',
                    'attr' => [
                        'class' => 'textarea-big',
                    ],
                ])
            ->add("urlPhoto", FileType::class, [
                "label" => "Avatar",
                "mapped" => false,
                "required"  => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' =>[
                            'image/jpeg',
                            'image/pjpeg',
                            'image/png',
                            'image/x-png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Image non conforme',
                    ])
                ],
            ]);
        $this->addLieu($builder);
        $builder
            ->add(
                'enregistrer',
                SubmitType::class,
                [
                    'label' => 'Enregistrer',
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ]
            )
            ->add(
                'publier',
                SubmitType::class,
                [
                    'label' => 'Publier la sortie',
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }

    private function addLieu(FormBuilderInterface $builder)
    {
        $oSortie = $builder->getData();
        $oLieu = $oSortie->getLieu();
        if ($oSortie->getId() != NULL && $oLieu) {
            $builder->add('lieu', EntityType::class, array('class' => Lieu::class,
                'query_builder' => function (LieuRepository $r) use ($oLieu) {
                    return $r->createQueryBuilder('l')
                        ->where('l.id=' . $oLieu->getId())
                        ->orderBy('l.nom');
                },
                'choice_label' => function ($lieu) {
                    return $lieu->getNom() . ' ('. $lieu->getVille()->getNom() . ')' ;
                }));
        } else {
            $builder->add('lieu', EntityType::class, array('class' => Lieu::class,
                'query_builder' => function (LieuRepository $r) {
                    return $r->createQueryBuilder('l')
                        ->orderBy('l.nom');
                },
                'choice_label' => function ($lieu) {
                    return $lieu->getNom() . ' ('. $lieu->getVille()->getNom() . ')';
                }));
        }
    }
}
