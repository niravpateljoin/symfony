<?php

namespace Lost\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserServiceSettingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserServiceSettingRepository extends EntityRepository
{
    public function getDisableServices($userId) {
        $services = array();
        
        $qb = $this->createQueryBuilder('us')
        ->where('us.user = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('us.serviceStatus = :status')
        ->setParameter('status', 'Disabled');
        
        $result = $qb->getQuery()->getResult();
        
        if($result) {
            foreach($result as $record) {
                $services[$record->getService()->getName()] = $record->getService()->getName();
            }
        }
        
        return $services;
    }
}
