<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTime;
use Lost\FrontBundle\Entity\AppLogActivities;

class BlogController extends Controller {
	
    public function getMostRecentBlogsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $body = $request->getContent();
        $data = json_decode($body, true);
        $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;
        $blogHostUrl = $this->container->getParameter('api_blog_host_url');
        $host = $blogHostUrl.'/bvblogapidispcom/get_posts/?';
        $params = array();
       
        if(!empty($data['pageNumber'])){
            $params['page'] = $pageNumber;
            $params['count'] = 10;
        } else {
            $params['count'] = -1;
        }
        
        $params['apikey'] = 'j89s8@3OL8x-9Gm936';
        $params['exclude'] = 'attachments,comments,author,tags,categories,modified,excerpt,title_plain,slug,status,comment_count,comment_status,custom_fields,thumbnail_size,content';
//        $params['include'] = 'title,thumbnail_images,date';

        $postStr = '';
        foreach($params as $key => $value) { 
                $postStr .= $key.'='.$value.'&'; 
        }
        rtrim($postStr, '&');

        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $arrBlog = json_decode(json_encode(json_decode($response)), True);
        
      
        $blogListSection = array();
        
        $blogListSection = $this->blogsListData($arrBlog);

        $totalCount = 0;
        if (!empty($blogListSection)) {
            $message = "";
            $status = "success";
            $totalCount = $arrBlog['count_total'];
        } else {
            $message = "No Blog available.";
            $status = "error";
        }

        $merge_array = array(
            'blogList' => $blogListSection,
            'totalCount' => $totalCount
        );

        /* --- STORE ACTIVITY LOGS [START] ----- */
        $objAppLogActivity = new AppLogActivities();
        $objAppLogActivity->setActivityType('Market-Place - Blogs-List');
        $objAppLogActivity->setRecordType('cpg');
        $objAppLogActivity->setLogDatetime(new \DateTime());
        $em->persist($objAppLogActivity);
        $em->flush();
        /* ---- STORE ACTIVITY LOGS [END] ------ */

        $response_data = array(
            'message' => $message,
            'status' => $status
        );

        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }
    
    protected function blogsListData($pagination = '') {
        $blogSection = array();
        $hostUrl = $this->container->getParameter('hostUrl');
      
        foreach ($pagination['posts'] as $arrBlog) {
              if(!empty($arrBlog['thumbnail_images']['thumbnail']['url'])){
                    $smallImage = $arrBlog['thumbnail_images']['thumbnail']['url'];
                } else {
                    $smallImage = '';
                }
            
            $blogSection[] = array(
                'id' => $arrBlog['id'],
                'title' => html_entity_decode($arrBlog['title']),
                'date' => $arrBlog['date'],
                'smallImage' => $smallImage,
            );
        }
        return $blogSection;
    }
    
    public function getBlogDetailsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $body = $request->getContent();
        $data = json_decode($body, true);
        $blogHostUrl = $this->container->getParameter('api_blog_host_url');
        $host = $blogHostUrl.'/bvblogapidispcom/get_post/?';
        $params = array();
        $params['apikey'] = 'j89s8@3OL8x-9Gm936';
        $params['post_id'] = $data['postId'];
        $params['exclude'] = 'attachments,comments,author,tags,categories,modified,excerpt,title_plain,slug,status,comment_count,comment_status,custom_fields';

        $postStr = '';
        foreach($params as $key => $value) { 
                $postStr .= $key.'='.$value.'&'; 
        }
        rtrim($postStr, '&');

        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $arrBlog = json_decode(json_encode(json_decode($response)), True);
        $blogdetailsSection = array();
        
        if(!empty($arrBlog['post']['title'])){
            $title = $arrBlog['post']['title'];
        } else {
            $title ='';
        }
        $position = 0;
        if(!empty($arrBlog['post']['content'])){
            $position =  strpos($arrBlog['post']['content'],'apss-social-share');
            if($position > 1){
                $content = substr($arrBlog['post']['content'],0,$position-13);
            } else {
                $content = $arrBlog['post']['content'];
            }
        } else {
            $content ='';
        }
		
		$content = str_replace('<h3>Comments</h3>', '', $content);
        
        if(!empty($arrBlog['post']['date'])){
            $date = $arrBlog['post']['date'];
        } else {
            $date ='';
        }
        
        if(!empty($arrBlog['post']['url'])){
            $url = $arrBlog['post']['url'];
        } else {
            $url ='';
        }
        
        if(!empty($arrBlog['post']['thumbnail_images']['full']['url'])){
            $image = $arrBlog['post']['thumbnail_images']['full']['url'];
        } else {
            $image = '';
        }
     
        $objEmailTemplate = $em->getRepository('LostFrontBundle:Emailtemplates')->findOneBy(array('emailkey' => 'BLOG_DETAILS', 'status' => 'Active'));
        $datecreate = date_create($date);
        $dateformat = date_format($datecreate,"F j, Y");
        
        $arrSearch = array('##BLOG_HOST_URL##','##BLOG_IMAGE##','##HEADER_TITLE##', '##BLOG_DATE##', '##BLOG_CONTENT##');
        $arrReplace = array($blogHostUrl,$image, mb_convert_encoding($title,"HTML-ENTITIES", "UTF-8"), $dateformat, mb_convert_encoding($content,"HTML-ENTITIES", "UTF-8"));
        $blogDetails = str_replace($arrSearch, $arrReplace, $objEmailTemplate->getBody());
        
        $message = "";
        $status = "success";
        $merge_array = array(
            'blogDetails' => $blogDetails,
            'url'=>$url
        );
          
        /* --- STORE ACTIVITY LOGS [START] ----- */
        $objAppLogActivity = new AppLogActivities();
        $objAppLogActivity->setActivityType('Market-Place - Blog-Details');
        $objAppLogActivity->setRecordType('blog');
        $objAppLogActivity->setLogDatetime(new \DateTime());
        $em->persist($objAppLogActivity);
        $em->flush();
        /* ---- STORE ACTIVITY LOGS [END] ------ */
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