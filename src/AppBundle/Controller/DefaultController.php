<?php

namespace AppBundle\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use AppBundle\Entity\Post;
use AppBundle\Entity\PostLikes;

/**
 * @Route("/")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $em = self::getEntityManager();
        
        $sql =  "SELECT p FROM AppBundle\Entity\Post p ORDER BY p.created DESC";
                 
        $query = $em->createQuery($sql)
                    ->setFirstResult(0)
                    ->setMaxResults(100);
        
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        
        $c = count($paginator);
        
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'posts' => $paginator
        ]);
    }
    
    /**
     * @Route("/posts/{post_id}", name="post_view")
     */
    public function viewPostAction(Request $request, $post_id) 
    {
        // Get the post from the post_id in the database
        $post = $this->getDoctrine()
                     ->getRepository('AppBundle:Post')
                     ->find($post_id);
    
        // If anything other than a post is returned (including null)
        // throw an error.
        if (!$post) {
            throw $this->createNotFoundException(
                "Post not found!"
            );
        }
        
        return $this->render('default/post.html.twig', [
            'post' => $post
        ]);
    }
    
     /**
     * @Route("/upvote", name="upvote")
     * @Method({"POST"})
     */
    public function upvoteAction(Request $request) 
	{
        // Get post id from the request
        $post_id = $request->get("post_id");

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

        try{
            $em = self::getEntityManager();
            $like = getDoctrine()
                    ->getRepository('AppBundle:PostLikes')
                    ->findBy(array('post_id'=>$post_id));

            if(isset($like)) {
                $post->setUpvotes($post->getUpvotes() - 1);
                $em->remove($like);
                $em->persist($like);
                $em->flush();
            } else {
                $like = new PostLikes;
                $like->setIsLike(1);
                if($user = self::getCurrentUser($this)) {
                    $like->setUser($user);
                }
                $like->setPost($post);
                $em->persist($like); //updating database
                $em->flush();
            }
        } catch (\Docrine\DBAL\DBALException $e) {
            return new JsonResponse(array('status' => 400, 'message' => 'Unable to add like. $e->message'));
        }

        try{            
            $post->setUpvotes($post->getUpvotes() + 1);
            $em->persist($post);
            $em->flush();
        } catch (\Doctrine\DBAL\DBALException $e) {
            return new JsonResponse(array('status' => 400, 'message' => "Unable to upvote post. $e->message"));
        }

        return new JsonResponse(array('status' => 200, 'message' => 'Success on upvote a post'));
	}
    /**
     * @Route("/posts/new", name="new_post")
     */
    public function newPostAction(Request $request) 
    {
        // Only make the request submission on a POST request
        if($request->isMethod('POST')) {
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
        } else {
            return $this->render('default/new_post.html.twig');
        }
    }
    
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }
    
    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction(Request $request) 
    {
        
    }
    
    /**
     * @Route("/terms", name="terms")
     */
    public function termsAction(Request $request) {
        return $this->render('default/terms.html.twig');
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
