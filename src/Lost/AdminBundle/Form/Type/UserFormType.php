<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Lost\UserBundle\Repository\CountryRepository;

class UserFormType extends AbstractType {

    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('email', 'email', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
                ->add('username', null, array('label' => 'form.username', 'read_only' => $builder->getData()->getId() ? true : false ,'translation_domain' => 'FOSUserBundle', 'constraints' =>
                    array(
                        new Length(array(
                            'min' => 8,
                            'max' => 18,
                            'minMessage' => 'Your username must have minimum {{ limit }} characters.',
                            'maxMessage' => 'Your username can have maximum {{ limit }} characters.',
                                )),
                        new Regex(array(
                            'pattern' => '/^[A-Za-z0-9-_!#$]+$/',
                            'match' => true,
                            'message' => 'username can contains character, number and special chars like -_!#$'
                                )),
        )));
               
        // disable password fields while editing.
        if (!$builder->getData()->getId()) {
            $builder
                    ->add('plainPassword', 'repeated', array(
                        'type' => 'password',
                        'options' => array('translation_domain' => 'FOSUserBundle'),
                        'first_options' => array('label' => 'form.password'),
                        'second_options' => array('label' => 'form.password_confirmation'),
                        'invalid_message' => 'fos_user.password.mismatch', 'constraints' =>
                        array(
                            new Length(array(
                                'min' => 8,
                                'max' => 18,
                                'minMessage' => 'Your password must have minimum {{ limit }} characters.',
                                'maxMessage' => 'Your password can have maximum {{ limit }} characters.',
                                    )),
                            new Regex(array(
                                'pattern' => '/^[A-Za-z0-9!@#$_]+$/',
                                'match' => true,
                                'message' => 'Password can contains characters, numbers and special chars like !@#$_'
                                    )),
                        )
            ));
        }
	 $builder
            ->add('firstname', 'text', array('label' => 'form.first_name', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('lastname', 'text', array('label' => 'form.last_name', 'translation_domain' => 'FOSUserBundle', 'required' => true)) 
            ->add('address', 'text', array('label' => 'form.address', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('city', 'text', array('label' => 'form.city', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('state', 'text', array('label' => 'form.state', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('zip', 'text', array('label' => 'form.zip', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('phone', 'text', array('label' => 'form.phone', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('country','entity',array(
            'class'=>'LostUserBundle:Country',
            'property'      => 'name',
            'query_builder' => function(CountryRepository $er) {
                return $er->createQueryBuilder('c')
                    ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                    ->add('orderBy','ORD ASC')
                    ->addOrderBy('c.name','ASC');
            },
            'required'=>true));
        
        //$builder->add('enabled', null, array('label' => 'Active?', 'required' => false));
        $builder->add('enabled', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => false, 'empty_value' => 'Select Status'));
    }

    public function getName() {
        return 'lost_admin_registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\User',
            'intention' => 'registration',
            'validation_groups' => array('Profile'),
        ));
    }

}
