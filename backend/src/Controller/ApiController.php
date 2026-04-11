<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
    protected function getJsonBody(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'username' => $user->getUsername(),
            'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
