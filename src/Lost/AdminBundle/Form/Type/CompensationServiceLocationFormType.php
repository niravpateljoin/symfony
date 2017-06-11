<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\AdminBundle\Entity\ServiceLocation;
use Lost\AdminBundle\Repository\ServiceLocationRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class CompensationServiceLocationFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('serviceLocations', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Lost\AdminBundle\Entity\ServiceLocation',
                ));                            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\ServiceLocation'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Lost\AdminBundle\Entity\ServiceLocation',
        );
    }

    public function getName() {
        
        return 'compensation_service_location';
    }

}
