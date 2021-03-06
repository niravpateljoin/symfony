<?php

namespace Lost\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SettingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SettingRepository extends EntityRepository {
    
    public function getSettingGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {
        
        $data = $this->trim_serach_data($searchData, $SearchType);

        $query = $this->createQueryBuilder('s');

        if ($SearchType == 'ORLIKE') {

            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {

            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);
        
        $countData = count($query->getQuery()->getArrayResult());
        
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $result = $query->getQuery()->getResult();

        $dataResult = array();

        if ($countData > 0) {

            $dataResult['result'] = $result;
            $dataResult['totalRecord'] = $countData;

            return $dataResult;
        }
        return false;
    }

    public function trim_serach_data($searchData, $SearchType) {

        $QueryStr = array();

        if (!empty($searchData)) {

            if ($SearchType == 'ANDLIKE') {

                $i = 0;
                foreach ($searchData as $key => $val) {

                    if ($key == 'Name' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 's.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }

    public function getAllSetting() {

        $query = $this->createQueryBuilder('s')
                ->select('s.id, s.name, s.value');

        return $query;
    }

    public function checkMaxPhoneNumber($phoneNumberCount) {

        $qb = $this->createQueryBuilder('s')
                ->select('s')
                ->where('s.name = :maxNumber')
                ->setParameter('maxNumber', 'max_number');

        $record = $qb->getQuery()->getOneOrNullResult();

        if ($record) {

            if ($record->getValue() > $phoneNumberCount) {

                return true;
            }
        }

        return false;
    }
    
    public function getEmailCampaignDays() {

        $qb = $this->createQueryBuilder('s')
                ->select('s')
                ->where('s.name = :emial_campaign_days')
                ->setParameter('emial_campaign_days', 'email_campaign_days');

        $record = $qb->getQuery()->getOneOrNullResult();

        if ($record) {
            return $record->getValue();            
        }

        return false;
    }
    
    public function getSettingsValue($constantName,$userId) {
        
        $maxTransactionLimit = 0;
        
        $userSettings = $this->_em->getRepository('LostUserBundle:UserPhoneSetting')->findOneBy(array('user' => $userId));
        
        if($userSettings){
            $maxTransactionLimit = $userSettings->getMaxDailyTransaction();
        }
        
        if(!$maxTransactionLimit){
            
            $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.name = :name')
            ->setParameter('name', $constantName);
            
            $record = $qb->getQuery()->getOneOrNullResult();
            
            if ($record) {
                $maxTransactionLimit = $record->getValue();
            }    
        }        
        return $maxTransactionLimit;
    }


}
