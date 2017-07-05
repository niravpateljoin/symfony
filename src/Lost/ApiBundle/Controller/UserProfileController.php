<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserProfileController extends Controller {

    public function profileAction(Request $request) {

        $message = '';
        $status = '';
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            $em = $this->getDoctrine()->getManager();
            $body = $request->getContent();
            $data = json_decode($body, true);
            $hostUrl = $this->container->getParameter('hostUrl');
            if (!empty($data)) {

                $userId = $data['userId'];
                
                $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                if ($objUserAccess) {

                    $merge_array = array(
                        'userId' => $objUserAccess->getId(),
                        'username' => $objUserAccess->getUsername(),
                        'email' => $objUserAccess->getEmail(),
                        'name' => $objUserAccess->getName(),
                        'countryId' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getCountry()->getId() : null,
                        'stateId' => ($objUserAccess->getEnduserinfo()) ? ($objUserAccess->getEnduserinfo()->getRegion()) ? $objUserAccess->getEnduserinfo()->getRegion()->getId() : null: null,
                        'stateName' => ($objUserAccess->getEnduserinfo()) ? ($objUserAccess->getEnduserinfo()->getRegion()) ? $objUserAccess->getEnduserinfo()->getRegion()->getRegionName(): null : null,
                        'cityId' => ($objUserAccess->getEnduserinfo()) ? ($objUserAccess->getEnduserinfo()->getCity()) ?$objUserAccess->getEnduserinfo()->getCity()->getId() : null :null,
                        'cityName' => ($objUserAccess->getEnduserinfo()) ? ($objUserAccess->getEnduserinfo()->getCity()) ? $objUserAccess->getEnduserinfo()->getCity()->getCityName() : null:null,
                        'zipCode' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getZipcode() : null,
                        'maritalStatus' => ($objUserAccess->getEnduserinfo()) ? $this->userMaritalStatus($objUserAccess->getEnduserinfo()->getMaritalstatus()) : null,
                        'gender' => ($objUserAccess->getEnduserinfo()) ? $this->userGender($objUserAccess->getEnduserinfo()->getGender()) : null,
                        'facebookLink' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getFacebookurl() : null,
                        'twitterLink' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getTwitterurl() : null,
                        'instagramLink' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getInstagramurl() : null,
                        'aboutMe' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getAboutme() : null,
                        'phone' => ($objUserAccess->getEnduserinfo()) ? $objUserAccess->getEnduserinfo()->getPhone() : null,
                        'profilePicUrl' => ($objUserAccess->getEnduserinfo()) ? ($objUserAccess->getEnduserinfo()->getProfilepic()!='') ? $hostUrl . '/uploads/users/' . $objUserAccess->getEnduserinfo()->getProfilepic() : '' : null,
                    );
                    
                    $message = "";
                    $status = "success";
                } else {
                    $message = "Invalid user.";
                    $status = "error";
                }
            } else {
                $message = "Invalid data.";
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

    protected function userGender($gender = '') {

        switch ($gender) {
            case 'F':
                return "Female";
                break;
            case 'M':
                return "Male";
                break;
            default:
                return "-";
        }
    }
        
    protected function userMaritalStatus($maritalStatus = '') {

        switch ($maritalStatus) {
            case 'S':
                return "Single";
                break;
            case 'M':
                return "Married";
                break;
            case 'D':
                return "Devorced";
                break;
            case 'SD':
                return "Single & Dating";
                break;
            case 'SF':
                return "Single & Friendship";
                break;
            case 'NS':
                return "Not So Simple";
                break;
            default:
                return "-";
        }
    }
}
