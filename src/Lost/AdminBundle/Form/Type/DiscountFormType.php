<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\AdminBundle\Repository\ServiceLocationRepository;

class DiscountFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('minAmount', null, array('label' => 'Minimum Amount', 'required' => true))
                ->add('maxAmount', null, array('label' => 'Maximum Amount', 'required' => true))
                ->add('percentage', null, array('label' => 'Discount', 'required' => true));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\ServiceLocationDiscount'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Lost\AdminBundle\Entity\ServiceLocationDiscount',
        );
    }

    public function getName() {
        return 'lost_service_discount';
    }
}
