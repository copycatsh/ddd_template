<?php

namespace App\Infrastructure\EventSubscriber;

use App\Account\Domain\Exception\AccountAlreadyExistsException;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Exception\CurrencyMismatchException;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Account\Domain\Exception\InvalidAmountException;
use App\Shared\Domain\Exception\DomainException;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DomainExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Map Domain Exceptions to HTTP responses
        $response = match (true) {
            $exception instanceof AccountAlreadyExistsException,
            $exception instanceof UserAlreadyExistsException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_CONFLICT
            ),

            $exception instanceof AccountNotFoundException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND
            ),

            $exception instanceof InsufficientFundsException,
            $exception instanceof CurrencyMismatchException,
            $exception instanceof InvalidAmountException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            ),

            $exception instanceof InvalidCredentialsException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNAUTHORIZED
            ),

            $exception instanceof DomainException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            ),

            default => null,
        };

        if (null !== $response) {
            $event->setResponse($response);
        }
    }
}
