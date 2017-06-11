<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\AdminBundle\Entity\IpAddressZone;
use Lost\UserBundle\Repository\ServiceRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class IpAddressZoneFormType extends AbstractType {

    public function __construct($service = null) {
        // code
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('services', 'entity', array(
                    'class' => 'LostUserBundle:Service',
                    'property' => 'name',
                    'empty_value' => 'Select Service',
                    'multiple' => true,
                    'query_builder' => function(ServiceRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->where('s.status = :statusVal')
                                ->setParameter('statusVal', 1)
                                ->addOrderBy('s.name', 'ASC');
                    },
                    'required' => true))
                ->add('fromIpAddress', 'text', array('label' => 'From IP', 'required' => true))
                ->add('toIpAddress', 'text', array('label' => 'To IP', 'required' => true))
                ->add('isMilstarEnabled','checkbox', array('required' => false))
                ->add('milstarFacNumber', 'text', array('label' => 'FacNumber', 'required' => false));                            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\IpAddressZone'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Lost\AdminBundle\Entity\IpAddressZone',
        );
    }

    public function getName() {
        return 'ip_address_zone_add';
    }

}
