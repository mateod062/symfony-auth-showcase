<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $JWTManager
    )
    {
    }

    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->userRepository->findOneBy(['username' => $data['username']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $this->JWTManager->create($user);

        return $this->json(['token' => $token]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/api/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setUsername($data['username']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setContractStartDate(new DateTime($data['contract_start_date']));
        $user->setContractEndDate(new DateTime($data['contract_end_date']));
        $user->setType($data['type']);
        $user->setVerified($data['verified']);

        $this->userRepository->save($user);

        return $this->json(['message' => 'User registered'], 201);
    }

    #[Route('/api/logout', name: 'app_logout', methods: ['POST'])]
    public function logout()
    {
    }

    #[Route('/api/user', name: 'user', methods: ['GET'])]
    public function user(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->json($user);
    }
}
