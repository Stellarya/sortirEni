<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Ville;
use App\Repository\VilleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LieuType extends AbstractType implements EventSubscriberInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, array('required' => true))
            ->add('rue', TextType::class, array('required' => false))
            ->add('latitude', HiddenType::class, array('required' => false))
            ->add('longitude', HiddenType::class, array('required' => false))
            ->add('ajouter', SubmitType::class,
                [
                    'label' => 'Ajouter le lieu',
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ]);
        $this->addVille($builder);
        $builder->addEventSubscriber($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Lieu::class,
            ]
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'ensureOneFieldIsSubmitted',
        ];
    }

    public function ensureOneFieldIsSubmitted(FormEvent $event)
    {
        $lieu = $event->getData();
        $nom = $lieu->getNom();
        $rue = $lieu->getRue();
        $latitude = $lieu->getLatitude();
        $longitude = $lieu->getLongitude();

        if ((!isset($nom) || !isset($rue)) && (!isset($latitude) || !isset($longitude))) {
            throw new TransformationFailedException(
                'nom/rue ou marqueur',
                0, // code
                null, // previous
                'Un nom et une rue ou un point sur la carte est nécessaire.', // user message
                ['{{ whatever }}' => 'here'] // message context for the translater
            );
        }
    }

    private function addVille(FormBuilderInterface $builder)
    {
        $oLieu = $builder->getData();
        if(isset($oLieu))
            $id = $oLieu->getId();
        else
            $id = null;

        if (isset($id)) {
            $oVille = $oLieu->getVille();
            if ($oLieu->getId() != null && $oVille) {
                $builder->add(
                    'ville',
                    EntityType::class,
                    array(
                        'class' => Ville::class,
                        'query_builder' => function (VilleRepository $r) use ($oVille) {
                            return $r->createQueryBuilder('v')
                                ->where('v.id='.$oVille->getId())
                                ->orderBy('v.codePostal');
                        },
                        'choice_label' => function ($ville) {
                            return $ville->getCodePostal().' - '.$ville->getNom();
                        },
                    )
                );
            }
        } else {
            $builder->add(
                'ville',
                EntityType::class,
                array(
                    'class' => Ville::class,
                    'query_builder' => function (VilleRepository $r) {
                        return $r->createQueryBuilder('v')
                            ->orderBy('v.codePostal');
                    },
                    'choice_label' => function ($ville) {
                        return $ville->getCodePostal().' - '.$ville->getNom();
                    },
                )
            );
        }
    }
}
