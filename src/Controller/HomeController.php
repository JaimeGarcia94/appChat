<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use League\OAuth2\Client\Provider\Google;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use DateInterval;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\HttpFoundation\Cookie;

class HomeController extends AbstractController
{
    #[Route('/login-mio', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('home/login.html.twig');
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $username = $this->getUser()->getUsername();
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->getParameter('mercure_secret_key')));
        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => [sprintf("/%s", $username)]])
            ->getToken(
                $config->signer(), 
                $config->signingKey()
            );
        
        $response = $this->render('home/index.html.twig',[
            
        ]);

        $response->headers->setCookie(
            new Cookie(
                'mercureAuthorization',
                $token->toString(),
                (new DateTime())
                ->add(new DateInterval('PT2H')),
                '/.well-known/mercure',
                null,
                false,
                true,
                false,
                'strict'
            )
        );

        return $response;

    }
}
