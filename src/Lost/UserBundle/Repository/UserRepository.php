<?php

namespace Lost\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository {

    public function getAllAdminQuery($admin) {
        
        
        $query = $this->createQueryBuilder('u')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');
        
        if($admin->getGroup() != 'Super Admin') {
            $query->andWhere('u.id = :id')
            ->setParameter('id', $admin->getId());
        }
        return $query;
    }

    public function getAdminSearch($query, $searchParam) {
        if (isset($searchParam['search']) && $searchParam['search']) {
            $query->andWhere('u.username LIKE :username OR u.email LIKE :email')
                    //->setParameters(array('username' => '%' . $searchParam['search'] . '%', 'email' => '%' . $searchParam['search'] . '%'))
                    ->setParameter('username', '%' . $searchParam['search'] . '%')
                    ->setParameter('email', '%' . $searchParam['search'] . '%')
                    ->andWhere('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%');

            return $query;
        }
        return false;
    }

    public function getAllUserQuery($ipAddressZones) {

        
        $query = $this->createQueryBuilder('u')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%');
        
        if(!empty($ipAddressZones)) {
            
               foreach($ipAddressZones as $key => $value) {
                   
                    $query->orWhere('u.ipAddressLong >= :fromIp')
                          ->setParameter('fromIp', $value['fromIP']);
                    
                    $query->andWhere('u.ipAddressLong <= :toIp')
                          ->setParameter('toIp', $value['toIP']);
                    
               }
            
        }
        
        return $query;
    }

    public function getUserByUsernameOrEmail($username, $email) {

        $query = $this->createQueryBuilder('u')
                ->select('u.email')
                ->where('u.username = :username')
                ->setParameter('username', $username)
                ->orWhere('u.email = :email')
                ->setParameter('email', $email);
        return $result = $query->getQuery()->getOneOrNullResult();
    }
    
    public function getUserSearch($query, $searchParam) {
        
            $query->andWhere('u.username LIKE :username OR u.email LIKE :email')
                    ->setParameter('username', '%' . $searchParam['search'] . '%')
                    ->setParameter('email', '%' . $searchParam['search'] . '%')
                    ->andWhere('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_USER%');

            return $query;
    }
    
    public function getAllCustomer() {
        
        $query = $this->createQueryBuilder('u')
        ->where('u.locked = :locked')
        ->setParameter('locked', 0)
        ->andWhere('u.isDeleted = :deleted')
        ->setParameter('deleted', 0)
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_USER%');
    
        return $query;
    }
    
    
    public function getAllCustomerIpAddressZoneWise($fromIpAddress, $toIpAddress) {
        
        $query = $this->createQueryBuilder('u')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%')
                ->andWhere('u.ipAddressLong >= :fromIp')
                ->setParameter('fromIp', $fromIpAddress)
                ->andWhere('u.ipAddressLong <= :toIp')
                ->setParameter('toIp', $toIpAddress);
        
        
        return $query->getQuery()->getResult();
    }
    
    public function getSearchUser($tag,$serviceIds){
        
        $query = $this->createQueryBuilder('u')
                        ->leftjoin('u.userServices', 'us')
                        ->leftJoin('us.service', 'sr')
                        ->where('u.locked = :locked')
                        ->setParameter('locked', 0)
                        ->andWhere('u.isDeleted = :deleted')
                        ->setParameter('deleted', 0)
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_USER%')
                        ->andWhere('sr.id IN(:id)')
                        ->setParameter('id', $serviceIds)
                        ->andWhere('us.status =:status')
                        ->setParameter('status', 1);
        
        $query->andWhere('u.username LIKE :username OR u.email LIKE :email OR u.firstname LIKE :firstname OR u.lastname LIKE :lastname')
              ->setParameter('username', '%' . $tag . '%')
              ->setParameter('email', '%' . $tag . '%')
              ->setParameter('firstname', '%' . $tag . '%')
              ->setParameter('lastname', '%' . $tag . '%');
        
        return $query->getQuery()->getResult();
    }
    
    public function getUserByActiveService($serviceIds,$selectedUserIds){
    
        $query = $this->createQueryBuilder('u')
                        ->leftjoin('u.userServices', 'us')
                        ->leftJoin('us.service', 'sr')
                        ->where('u.locked = :locked')
                        ->setParameter('locked', 0)
                        ->andWhere('u.isDeleted = :deleted')
                        ->setParameter('deleted', 0)
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_USER%')
                        ->andWhere('u.id IN(:uid)')
                        ->setParameter('uid', $selectedUserIds)
                        ->andWhere('sr.id IN(:id)')
                        ->setParameter('id', $serviceIds)
                        ->andWhere('us.status =:status')
                        ->setParameter('status', 1);
    
        return $query->getQuery()->getResult();
    }

}
