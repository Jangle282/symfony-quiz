<?php

namespace App\EventListener;

use App\Attribute\RateLimited;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
class RateLimitListener
{
    public function __construct(
        private ServiceLocator $rateLimiters,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getController();
        if (\is_array($controller)) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif ($controller instanceof \Closure) {
            return;
        } else {
            $reflection = new \ReflectionMethod($controller, '__invoke');
        }

        $attributes = $reflection->getAttributes(RateLimited::class);
        if ($attributes === []) {
            return;
        }

        $request = $event->getRequest();
        $ip = $request->getClientIp() ?? 'anonymous';

        // Use user ID for authenticated routes, fall back to IP for unauthenticated
        $key = $ip;
        $token = $this->tokenStorage->getToken();
        if ($token !== null) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $key = 'user_' . $user->getUserIdentifier();
            }
        }

        foreach ($attributes as $attribute) {
            $rateLimited = $attribute->newInstance();
            $limiterName = $rateLimited->limiter;

            if (!$this->rateLimiters->has($limiterName)) {
                throw new \RuntimeException(sprintf('Rate limiter "%s" is not configured.', $limiterName));
            }

            /** @var RateLimiterFactory $factory */
            $factory = $this->rateLimiters->get($limiterName);
            $limiter = $factory->create($key);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                $event->setController(fn () => new JsonResponse(
                    ['error' => $rateLimited->message],
                    Response::HTTP_TOO_MANY_REQUESTS,
                ));

                return;
            }
        }
    }
}
