<?php

namespace Lost\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\UserBundle\Repository\SupportCategoryRepository;
//use Lost\UserBundle\Entity\SupportCategory;



class SupportFormType extends AbstractType {
    
    protected $user;
   

    public function __construct ($user = false) {
        
       $this->email = $user->getEmail();
        
    }
    

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('supportCategory', 'entity', array(
                    'class' => 'LostUserBundle:SupportCategory',
                    'empty_value' => 'Support Category',
                    'property' => 'name',
                    'query_builder' => function(SupportCategoryRepository $er) {
                                        return $er->createQueryBuilder('sc')
                                               ->add('orderBy', 'sc.id ASC');
                                        }, 
                    'required' => true))
                ->add('user', 'hidden', array('label' => 'User', 'required' => true))
                ->add('email', 'email', array('label' => 'Email', 'required' => true, 'data' =>  $this->email, 'read_only' => true))
                ->add('subject', null, array('label' => 'Subject', 'required' => true))
                ->add('description', null, array('label' => 'Description'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\Support',
        ));
    }

    public function getName() {
        return 'lost_user_support';
    }

}
