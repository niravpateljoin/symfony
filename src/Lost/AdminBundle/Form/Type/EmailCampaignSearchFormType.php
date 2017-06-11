<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailCampaignSearchFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $searchParams = $options['data']['searchParams'];

        $builder->add('subject', 'text', array(
                    'data' => $searchParams ? $searchParams['subject'] : ''
                ))
                ->add('emailType', 'choice', array(
                    'choices' => array('' => 'Email Type', 'M' => 'Marketing', 'S' => 'Support'),
                    'data' => $searchParams ? $searchParams['emailType'] : ''
                ))
                ->add('emailStatus', 'choice', array(
                    'choices' => array('' => 'Status', 'Not Active' => 'Not Active', 'Active' => 'Active', 'Sending' => 'Sending', 'Sent' => 'Sent'),
                    'data' => $searchParams ? $searchParams['emailStatus'] : ''
                ));
    }

    public function getName() {

        return 'search';
    }

}
