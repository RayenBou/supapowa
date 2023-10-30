<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name'

            ])
            ->add('activated', ChoiceType::class, [
                'label' => 'actif',
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                ],
                // Permet d'afficher les choix sous forme de boutons radio
                'multiple' => false, // Permet de sélectionner une seule option
            ])
            ->add('inStock', ChoiceType::class, [
                'label' => 'en stock',
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                ],
                // Permet d'afficher les choix sous forme de boutons radio
                'multiple' => false, // Permet de sélectionner une seule option
            ])

            ->add('photo', FileType::class, [
                'multiple' => true,
                'mapped' => false,
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
