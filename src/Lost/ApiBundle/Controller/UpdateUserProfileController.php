<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Enduserinfo;

//use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UpdateUserProfileController extends Controller {

    public function updateProfileAction(Request $request) {

        $message = '';
        $status = '';
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            $em = $this->getDoctrine()->getManager();
            $body = $request->getContent();
            $data = json_decode($body, true);
            $hostUrl = $this->container->getParameter('hostUrl');

            $userId = $request->request->get('userId');
            $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

            if ($objUserAccess) {
                $requestAllParameter = $request->request->all();
                
                $userName = $request->request->get('username');
                $email = $request->request->get('email');
                $name = $request->request->get('name');
                $stateId = $request->request->get('stateId');
                $cityId = $request->request->get('cityId');
                $zipCode = $request->request->get('zipCode');
                $maritalStatus = $request->request->get('maritalStatus');
                $gender = $request->request->get('gender');
                $facebookLink = $request->request->get('facebookLink');
                $twitterLink = $request->request->get('twitterLink');
                $instagramLink = $request->request->get('instagramLink');
                $aboutMe = $request->request->get('aboutMe');
                $phone =  $request->request->get('phone');
                $profilePicUrl = $request->files->get('profilePicUrl');
                

                //echo "<pre>"; print_r($profilePicUrl); exit;
                if (!empty($userName) && !empty($email) && !empty($name) && !empty($stateId) && !empty($cityId)) {

                    $objCheckUserName = $em->getRepository('LostFrontBundle:Useraccess')->checkUserNameEmailExists($userName, $userId, '');
                    $objCheckEmail = $em->getRepository('LostFrontBundle:Useraccess')->checkUserNameEmailExists('', $userId, $email);
                    $objRegion = $em->getRepository('LostFrontBundle:Region')->find($stateId);
                    $objCity = $em->getRepository('LostFrontBundle:City')->find($cityId);

                    if ((!$objCheckEmail) && (!$objCheckUserName) && $objRegion && $objCity) {

                        //$objUserAccess->setUsername($userName);
                        //$objUserAccess->setEmail($email);
                        $objUserAccess->setName($name);

                        if (!$objUserAccess->getEnduserinfo()) {

                            /* Start - Add in enduserinfo table */
                            $objCountry = $em->getRepository('LostFrontBundle:Country')->find('226');
                            $objEnduserinfo = new Enduserinfo();
                            $objEnduserinfo->setUserid($objUserAccess);
                            $objEnduserinfo->setCreatedby($objUserAccess);
                            $objEnduserinfo->setUpdatedby($objUserAccess);
                            $objEnduserinfo->setCountry($objCountry);
                            $objEnduserinfo->setRegion($objRegion);
                            $objEnduserinfo->setCity($objCity);
                            $objEnduserinfo->setZipcode($zipCode);
                            $objEnduserinfo->setMaritalstatus($maritalStatus);
                            $objEnduserinfo->setGender($gender);
                            $objEnduserinfo->setFacebookurl($facebookLink);
                            $objEnduserinfo->setTwitterurl($twitterLink);
                            $objEnduserinfo->setInstagramurl($instagramLink);
                            $objEnduserinfo->setAboutme($aboutMe);
                            $objEnduserinfo->setPhone($phone);

                            if ($profilePicUrl) {

                                $objEnduserinfo->removeFile($objEnduserinfo->getProfilepic());

                                $profileImage = $this->uploadProfile($profilePicUrl);
                                $objEnduserinfo->setProfilepic($profileImage);
                            }
                            else{
                                $objEnduserinfo->removeFile($objEnduserinfo->getProfilepic());
                            }
                            $em->persist($objEnduserinfo);
                            //$em->flush();
                            /* End - Add in enduserinfo table */
                        } else {
                            
                            $objUserAccess->getEnduserinfo()->setRegion($objRegion);
                            $objUserAccess->getEnduserinfo()->setCity($objCity);
                            $objUserAccess->getEnduserinfo()->setZipcode($zipCode);
                            $objUserAccess->getEnduserinfo()->setMaritalstatus($maritalStatus);
                            $objUserAccess->getEnduserinfo()->setGender($gender);
                            $objUserAccess->getEnduserinfo()->setFacebookurl($facebookLink);
                            $objUserAccess->getEnduserinfo()->setTwitterurl($twitterLink);
                            $objUserAccess->getEnduserinfo()->setInstagramurl($instagramLink);
                            $objUserAccess->getEnduserinfo()->setAboutme($aboutMe);
                            $objUserAccess->getEnduserinfo()->setPhone($phone);

                            if ($profilePicUrl) {

                                $objUserAccess->getEnduserinfo()->removeFile($objUserAccess->getEnduserinfo()->getProfilepic());

                                $profileImage = $this->uploadProfile($profilePicUrl);
                                $objUserAccess->getEnduserinfo()->setProfilepic($profileImage);
                            }
                            else{
                               // $objEnduserinfo->removeFile($objEnduserinfo->getProfilepic());
							   $objUserAccess->getEnduserinfo()->removeFile($objUserAccess->getEnduserinfo()->getProfilepic());
							   $objUserAccess->getEnduserinfo()->setProfilepic('');
                            }
                        }

                        $em->persist($objUserAccess);
                        $em->flush();

                        $message = "Profile has been updated successfully.";
                        $status = "success";
                    } elseif ($objCheckUserName) {
                        $message = 'Username already exists.';
                        $status = 'error';
                        $merge_array = array();
                    } elseif ($objCheckEmail) {
                        $message = 'Email Id already exists.';
                        $status = 'error';
                        $merge_array = array();
                    } elseif (!$objRegion) {
                        $message = 'Invalid state.';
                        $status = 'error';
                        $merge_array = array();
                    } elseif (!$objCity) {
                        $message = 'Invalid city.';
                        $status = 'error';
                        $merge_array = array();
                    }
                } else {
                    $message = "Data can not be blanked.";
                    $status = "error";
                }
            } else {
                $message = "Invalid user.";
                $status = "error";
            }

            $response_data = array(
                'message' => $message,
                'status' => $status
            );

            $final_response_data = array_merge($response_data, $merge_array);
            $response = new Response(json_encode($final_response_data));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
    }

    protected function uploadProfile($uploadedFile) {

        $basicPath = __DIR__ . '/../../../../web/uploads';
        $basicProfilePath = $basicPath . '/users';

        //echo "<pre>"; print_r($basicReceiptPath); exit;
        if (!is_dir($basicProfilePath)) {
            @mkdir($basicProfilePath);
            @chmod($basicProfilePath, 0777);
        }

        if (null === $uploadedFile) {
            return;
        } else {

            $extention = explode('.', $uploadedFile->getClientOriginalName());
            $this->path = $extention[0] . '_' . time() . '.' . $extention[1];

            if ($uploadedFile->move($basicProfilePath, $this->path))
                return $this->path;
            else
                return false;
        }
    }

}
