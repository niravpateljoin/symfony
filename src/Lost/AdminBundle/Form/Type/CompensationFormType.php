<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Lost\UserBundle\Entity\Service;
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Repository\UserRepository;
use Lost\AdminBundle\Entity\ServiceLocation;

class CompensationFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder
                ->add('title', null, array('required' => true))
//                 ->add('admin', 'hidden')
                ->add('ispHours', 'text')
                ->add('iptvDays', 'text')
                ->add('services', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Lost\UserBundle\Entity\Service'
                ))
                ->add('users', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'username',
                                'class' => 'Lost\UserBundle\Entity\User',
                                'query_builder' => function(UserRepository $ur) {
                                    return $ur->getAllCustomer();
                                },
                ))
                ->add('serviceLocations', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Lost\AdminBundle\Entity\ServiceLocation'
                ))
                ->add('status', 'choice', array('choices'  => array('Queued' => 'Queued','Inprogress' =>'Inprogress','Completed' =>'Completed'),'required' => true))
                ->add('isActive', 'choice', array('choices'  => array(1 => 'Active', 0 => 'Inactive'),'required' => true, 'empty_value' => 'Select Status'))
                ->add('isEmailActive', 'checkbox', array('data' => false))
                ->add('emailSubject', 'text')
                ->add('emailContent', 'textarea', array(
                        'attr' => array('class' => 'tinymce')
                ))
                ->add('type', 'choice', array('choices'  => array('ServiceLocation' => 'Service Location Wise', 'Customer' => 'Customer Wise'),'required' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\Compensation',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Lost\UserBundle\Entity\Compensation',
        );
    }
    
    public function getName() {
        
        return 'lost_admin_compensation';
    }

}
