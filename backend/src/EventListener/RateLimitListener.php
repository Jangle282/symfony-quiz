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

#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
class RateLimitListener
{
    public function __construct(
        private ServiceLocator $rateLimiters,
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

        foreach ($attributes as $attribute) {
            $rateLimited = $attribute->newInstance();
            $limiterName = $rateLimited->limiter;

            if (!$this->rateLimiters->has($limiterName)) {
                throw new \RuntimeException(sprintf('Rate limiter "%s" is not configured.', $limiterName));
            }

            /** @var RateLimiterFactory $factory */
            $factory = $this->rateLimiters->get($limiterName);
            $limiter = $factory->create($ip);
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
