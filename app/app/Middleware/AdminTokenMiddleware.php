<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminTokenMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseInterface $response)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponseInterface
    {
        $token = (string) env('ADMIN_TOKEN', '');
        if ($token === '') {
            return $this->deny('ADMIN_TOKEN is not configured');
        }

        if (hash_equals($token, $this->resolveToken($request))) {
            return $handler->handle($request);
        }

        return $this->deny('Unauthorized');
    }

    private function resolveToken(ServerRequestInterface $request): string
    {
        $authorization = $request->getHeaderLine('Authorization');
        if (str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        if (str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6), true);
            if (is_string($decoded)) {
                $parts = explode(':', $decoded, 2);
                return (string) ($parts[1] ?? $parts[0]);
            }
        }

        $header = $request->getHeaderLine('X-Admin-Token');
        if ($header !== '') {
            return $header;
        }

        $query = $request->getQueryParams();

        return (string) ($query['admin_token'] ?? '');
    }

    private function deny(string $message): PsrResponseInterface
    {
        return $this->response
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="WeChat Admin"')
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody(new SwooleStream($message));
    }
}
