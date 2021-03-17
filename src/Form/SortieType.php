<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Repository\LieuRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


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
            ->add('dateHeureDebut', DateTimeType::class)
            ->add('dateLimiteInscription', DateType::class,
                [
                    'label' => 'Date limite d\'inscription '
                ])
            ->add('duree')
            ->add('nbInscriptionMax')
            ->add('infosSortie',
                TextareaType::class,
                [
                    'label' => 'Informations sur la sortie',
                    'attr' => [
                        'class' => 'textarea-big',
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
