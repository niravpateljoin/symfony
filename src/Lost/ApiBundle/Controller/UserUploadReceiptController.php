<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Userreceipt;

class UserUploadReceiptController extends Controller {

    public function uploadReceiptAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            //if (!empty($data)) {

            $businessId = $request->request->get('businessId');
            $userId = $request->request->get('userId');
            $receipt_number = $request->request->get('receipt_number');
            $transactionValue = $request->request->get('transactionValue');
            $pass_receipt_date = date('Y-m-d', strtotime($request->request->get('receipt_date')));
            $receipt_date = new \DateTime($pass_receipt_date);
            $uploaded_datetime = new \DateTime();

            if (!empty($businessId) && !empty($userId) && !empty($receipt_number) && !empty($transactionValue) && !empty($pass_receipt_date)) {

                //echo "<pre>"; print_r($uploaded_datetime); exit;
                $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $businessId, 'status' => 'Active', 'publishstatus' => 'Y'));
                $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                if ($objBusiness && $objUserAccess) {

                    /* Start - Add Receipt */
                    $objUserReceipt = new Userreceipt();

                    $objUserReceipt->setUserId($objUserAccess);
                    $objUserReceipt->setBusinessId($objBusiness);
                    $objUserReceipt->setReceiptNumber($receipt_number);
                    $objUserReceipt->setReceiptDate($receipt_date);
                    $objUserReceipt->setTransactionValue($transactionValue);
                    $objUserReceipt->setUploadedDatetime($uploaded_datetime);
                    $objUserReceipt->setStatus('Pending');
                    //$objUserReceipt->setStatusChangedBy($objUserAccess);

                    $em->persist($objUserReceipt);
                    $em->flush();

                    $last_id = $objUserReceipt->getId();
                    $uploadedReceipt = $request->files->get('receipt_image'); // uploaded receipt image
                    $receipt_name = $this->uploadReceipt($last_id, $uploadedReceipt);

                    if ($receipt_name) {

                        $objReceiptImage = $em->getRepository('LostFrontBundle:Userreceipt')->find($last_id);
                        $objReceiptImage->setReceiptImage($receipt_name);
                        $em->persist($objReceiptImage);
                        $em->flush();
                    }
                    /* End - Add Receipt */

                    $message = "Receipt has been uploaded successfully";
                    $status = "success";
                } else {
                    $message = "Invalid business or user found.";
                    $status = "error";
                }
            } else {
                $message = "Invalid data.";
                $status = "error";
            }
        } else {
            $message = "Invalid request.";
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

    protected function uploadReceipt($receipt_id, $uploadedReceipt) {

        $basicPath = __DIR__ . '/../../../../web/uploads/business';
        $basicReceiptPath = $basicPath . '/receipt';

        //echo "<pre>"; print_r($basicReceiptPath); exit;
        if (!is_dir($basicReceiptPath)) {
            @mkdir($basicReceiptPath);
            @chmod($basicReceiptPath, 0777);
            //echo "ss"; exit;
        }

        $receiptIdFolderexists = $basicReceiptPath . '/' . $receipt_id;

        if (!is_dir($receiptIdFolderexists)) {
            @mkdir($basicReceiptPath . '/' . $receipt_id);
            @chmod($basicReceiptPath . '/' . $receipt_id, 0777);
        }

        if (null === $uploadedReceipt) {
            return;
        } else {

            $extention = explode('.', $uploadedReceipt->getClientOriginalName());
            $this->path = $extention[0] . '_' . time() . '.' . $extention[1];

            if ($uploadedReceipt->move($receiptIdFolderexists, $this->path))
                return $this->path;
            else
                return false;
        }
    }

}
