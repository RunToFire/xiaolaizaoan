<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;

use function Hyperf\Support\env;

class AdminAuthController extends AbstractController
{
    private const COOKIE_NAME = 'wechat_admin_token';

    public function login(): ResponseInterface
    {
        $token = (string) env('ADMIN_TOKEN', '');
        if ($token === '') {
            return $this->json(['message' => 'ADMIN_TOKEN is not configured'], 500);
        }

        $password = (string) $this->request->input('password', '');
        if (! hash_equals($token, $password)) {
            return $this->json(['message' => 'invalid username or password'], 401);
        }

        return $this->json(['message' => 'ok'])
            ->withHeader('Set-Cookie', $this->cookie($token, 86400));
    }

    public function logout(): ResponseInterface
    {
        return $this->json(['message' => 'ok'])
            ->withHeader('Set-Cookie', $this->cookie('', 0));
    }

    private function cookie(string $value, int $maxAge): string
    {
        return sprintf(
            '%s=%s; Max-Age=%d; Path=/; HttpOnly; SameSite=Lax',
            self::COOKIE_NAME,
            rawurlencode($value),
            $maxAge
        );
    }

    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->json([
            'code' => $status >= 400 ? 1 : 0,
            'data' => $status >= 400 ? null : $data,
            'error' => $status >= 400 ? $data : null,
        ])->withStatus($status);
    }
}
