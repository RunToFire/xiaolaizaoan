<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Hyperf\Support\env;

class AdminTokenMiddleware implements MiddlewareInterface
{
    private const COOKIE_NAME = 'wechat_admin_token';

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
        if (isset($query['admin_token'])) {
            return (string) $query['admin_token'];
        }

        $cookies = $request->getCookieParams();
        if (isset($cookies[self::COOKIE_NAME])) {
            return (string) $cookies[self::COOKIE_NAME];
        }

        return '';
    }

    private function deny(string $message): PsrResponseInterface
    {
        return $this->response->json([
            'code' => 1,
            'data' => null,
            'error' => ['message' => $message],
        ])->withStatus(401);
    }
}
