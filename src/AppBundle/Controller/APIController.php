<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Post;

/**
 * @Route("/api")
 */
class APIController extends Controller
{
    /**
     * @Route("/", name="api_home")
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(array('name' => $name));
    }
    
    /**
     * @Route("/users/{user_id}", name="get_user", requirements={"id" = "\d+"})
     */ 
    public function getUserAction(Request $request, $user_id)
    {
        // Get the user by their user_id from the database
        $user = $this->getDoctrine()
                     ->getRepository('AppBundle:User')
                     ->find($user_id);
    
        // If something other than a user is returned (incuding null)
        // throw an error.
        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id ' . $user_id
            );
        }
        
        $serializer = $this->container->get('serializer');
        $reports = $serializer->serialize($user, 'json');
        return new Response($reports);
    }    
    
    /**
     * @Route("/posts/{post_id}", name="get_post", requirements={"id" = "\d+"})
     */
    public function getPostAction(Request $request, $post_id)
    {
        // Get the post from the post_id in the database
        $post = $this->getDoctrine()
                     ->getRepository('AppBundle:Post')
                     ->find($post_id);
    
        // If anything other than a post is returned (including null)
        // throw an error.
        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for id ' . $id
            );
        }
        
        $serializer = $this->container->get('serializer');
        $reports = $serializer->serialize($post, 'json');
        return new Response($reports);
    }
    
    /**
     * @Route("/posts", name="new_post")
     * @Method({"POST"})
     */
    public function newPostAction(Request $request) 
    {
        // Get the User's IP address
        $post_ip = self::getCurrentIp($this);
        
        // Need to get the current user based on security acces
        $user = self::getCurrentUser($this);
        
        // Get the body of the post from the request
        $body = $request->get('body');
        
        // We have everything we need now
        // Time to add the post to the database
        try {
            $em = self::getEntityManager();
            $post = new Post;
            $post->setBody($body);
            $post->setIpAddress($post_ip);
            $post->setUser($user);
            $em->persist($post);
            $em->flush();
            return new JsonResponse(array('status' => 200, 'message' => 'Success'));
        } catch (\Doctrine\DBAL\DBALException $e) {
            return new JsonResponse(array('status' => 400, 'message' => 'Unable to submit post.'));
        }   
    }
    
    /**
     * @return Doctrine entity manager
     */
    protected function getEntityManager() {
        return $this->get('doctrine')->getManager();
    }
    
    /**
     * @param $context – pss in $this as the variable
     * @return IP Address from the request
     */
    protected function getCurrentIp($context) {
        return $context->container->get('request_stack')->getMasterRequest()->getClientIp();
    }
    
    /**
     * @param $context – pass in $this as the variable
     * @return the User object that is currently authenticated
     */
    protected function getCurrentUser($context) {
        return $context->get('security.token_storage')->getToken()->getUser();
    }
}
