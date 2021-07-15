<?php

namespace App\Controller;

use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{

    private UserRepository $userRepository;
    private UserPasswordHasherInterface $encoder;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $encoder
    ) {
        $this->userRepository = $userRepository;
        $this->encoder        = $encoder;
    }

    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function index(Request $request): Response
    {
        $dados = json_decode($request->getContent());
        $user  = $dados->usuario ?? null;
        $senha = $dados->senha ?? null;
        $key   = $this->getParameter('JWT_KEY');
        //$request->server->get('JWT_KEY')
        if (empty($key)) {
            return new JsonResponse(
                ['erro' => 'Invalid configuration for JWT KEY'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        if (is_null($user) || is_null($senha)) {
            return new JsonResponse(
                ['erro' => 'Favor enviar usuário e senha'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $userData = $this->userRepository->findOneByUserName($user);

        if (is_null($userData)) {
            return new JsonResponse(
                ['erro' => 'Usuário ou senha inválido!'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if ( ! $this->encoder->isPasswordValid($userData, $senha)) {
            return new JsonResponse(
                ['erro' => 'Usuário ou senha inválido.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $date = new DateTime();
        $date->add(DateInterval::createFromDateString('10 minutes'));
        $expirationTime = $date->format('Y-m-d H:i:s');
        $token          = JWT::encode(
            [
                'id'       => $userData->getId(),
                'username' => $userData->getUserIdentifier(),
                'exp'      => strtotime($expirationTime),
            ],
            $key
        );

        return new JsonResponse([
            'access_token' => $token,
        ]);
    }

}
