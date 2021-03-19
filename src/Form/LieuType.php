<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Ville;
use App\Repository\VilleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LieuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class)
            ->add('rue', TextType::class, array('required' => false))
            ->add('latitude', TextType::class, array('required' => false))
            ->add('longitude', TextType::class, array('required' => false))
            ->add('ajouter', SubmitType::class);
        $this->addVille($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class,
        ]);
    }

    private function addVille(FormBuilderInterface $builder)
    {
        $oLieu = $builder->getData();
        $oVille = $oLieu->getVille();
        if ($oLieu->getId() != NULL && $oVille) {
            $builder->add('ville', EntityType::class, array('class' => Ville::class,
                'query_builder' => function (VilleRepository $r) use ($oVille) {
                    return $r->createQueryBuilder('v')
                        ->where('v.id=' . $oVille->getId())
                        ->orderBy('v.codePostal');
                },
                'choice_label' => function ($ville) {
                    return $ville->getCodePostal(). ' - '. $ville->getNom()   ;
                }));
        } else {
            $builder->add('ville', EntityType::class, array('class' => Ville::class,
                'query_builder' => function (VilleRepository $r) {
                    return $r->createQueryBuilder('v')
                        ->orderBy('v.codePostal');
                },
                'choice_label' => function ($ville) {
                    return $ville->getCodePostal(). ' - '. $ville->getNom()  ;
                }));
        }
    }
}
