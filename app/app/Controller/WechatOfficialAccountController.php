<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\WechatOfficialAccount;
use App\Service\Wechat\WechatReplyService;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

class WechatOfficialAccountController extends AbstractController
{
    public function callback(?string $appId = null): ResponseInterface
    {
        $signature = (string) $this->request->input('signature', '');
        $timestamp = (string) $this->request->input('timestamp', '');
        $nonce = (string) $this->request->input('nonce', '');
        $account = $this->findAccount($appId);
        $token = $account?->token ?: (string) env('WECHAT_OFFICIAL_ACCOUNT_TOKEN', '');

        if (! $this->isSignatureValid($token, $signature, $timestamp, $nonce)) {
            return $this->text('invalid signature', 403);
        }

        if ($this->request->getMethod() === 'GET') {
            return $this->text((string) $this->request->input('echostr', ''));
        }

        if ($account === null) {
            return $this->text('success');
        }

        $message = $this->parseMessage((string) $this->request->getBody()->getContents());
        if ($message === []) {
            return $this->text('success');
        }

        $xml = $this->container->get(WechatReplyService::class)->buildReplyXml((int) $account->id, $message);

        return $this->xml($xml);
    }

    private function findAccount(?string $appId): ?WechatOfficialAccount
    {
        $query = WechatOfficialAccount::query()->where('is_active', 1);

        if ($appId !== null && $appId !== '') {
            return $query->where('app_id', $appId)->first();
        }

        return $query->orderBy('id')->first();
    }

    private function isSignatureValid(string $token, string $signature, string $timestamp, string $nonce): bool
    {
        if ($token === '' || $signature === '' || $timestamp === '' || $nonce === '') {
            return false;
        }

        $parts = [$token, $timestamp, $nonce];
        sort($parts, SORT_STRING);

        return hash_equals(sha1(implode('', $parts)), $signature);
    }

    private function parseMessage(string $body): array
    {
        if (trim($body) === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            return [];
        }

        return json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function text(string $content, int $status = 200): ResponseInterface
    {
        return $this->response
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody(new SwooleStream($content));
    }

    private function xml(string $content): ResponseInterface
    {
        return $this->response
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withBody(new SwooleStream($content));
    }
}
