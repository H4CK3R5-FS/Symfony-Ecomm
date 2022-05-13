<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class NewProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        
        $builder
            ->add('nom')
            ->add('description')
            ->add('prix')
            ->add('imageFile', FileType::class, ['required' => false])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'titre',
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'attr' => [
                'novalidate' => 'novalidate'
            ]
            
        ]);
        
    }
}
