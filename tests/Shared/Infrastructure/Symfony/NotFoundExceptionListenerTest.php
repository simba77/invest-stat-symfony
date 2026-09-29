<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Symfony;

use App\Shared\Infrastructure\Symfony\NotFoundException;
use App\Shared\Infrastructure\Symfony\NotFoundExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class NotFoundExceptionListenerTest extends TestCase
{
    public function testAnswersJsonRequestWithJson404(): void
    {
        $event = $this->exceptionEvent(
            Request::create('/api/accounts/update/5', 'POST', server: ['CONTENT_TYPE' => 'application/json']),
            new NotFoundException('Account with id "5" not found'),
        );

        (new NotFoundExceptionListener())($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            ['message' => 'Account with id "5" not found'],
            json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testAnswersOtherRequestsWithPlainText404(): void
    {
        $event = $this->exceptionEvent(Request::create('/api/accounts/5'), new NotFoundException('Account not found'));

        (new NotFoundExceptionListener())($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertNotInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Account not found', $response->getContent());
    }

    public function testLeavesOtherExceptionsToTheFramework(): void
    {
        $event = $this->exceptionEvent(Request::create('/api/accounts/5'), new \RuntimeException('Boom'));

        (new NotFoundExceptionListener())($event);

        self::assertNull($event->getResponse());
    }

    private function exceptionEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
