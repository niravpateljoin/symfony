<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Repository\UserRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class CompensationUserFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('customers', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'username',
                                'class' => 'Lost\UserBundle\Entity\User',
                                'query_builder' => function(UserRepository $ur) {
                                    return $ur->getAllCustomer();
                                },
                ));                            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\User'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Lost\UserBundle\Entity\User',
        );
    }

    public function getName() {
        
        return 'compensation_user';
    }

}
