<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\WechatOfficialAccount;
use App\Service\Wechat\WechatMenuService;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class WechatMenuAdminController extends AbstractController
{
    public function show(int $accountId): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($accountId);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        return $this->json([
            'account_id' => $account->id,
            'menu_config' => $account->menu_config ?: ['button' => []],
            'menu_published_at' => $account->menu_published_at,
        ]);
    }

    public function save(int $accountId): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($accountId);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        $menu = $this->request->input('menu_config', $this->request->all());
        if (is_string($menu)) {
            $menu = json_decode($menu, true);
        }

        $error = $this->validateMenu($menu);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $account->menu_config = $menu;
        $account->save();

        return $this->json(['item' => $account->refresh()]);
    }

    public function publish(int $accountId): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($accountId);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        try {
            $result = $this->container->get(WechatMenuService::class)->publish($account);
        } catch (Throwable $exception) {
            return $this->json(['message' => $exception->getMessage()], 502);
        }

        $account->menu_published_at = date('Y-m-d H:i:s');
        $account->save();

        return $this->json(['wechat' => $result, 'item' => $account->refresh()]);
    }

    private function validateMenu(mixed $menu): ?string
    {
        if (! is_array($menu) || ! isset($menu['button']) || ! is_array($menu['button'])) {
            return 'menu_config.button must be an array';
        }

        if (count($menu['button']) > 3) {
            return 'top menu button count cannot exceed 3';
        }

        foreach ($menu['button'] as $button) {
            if (! is_array($button) || trim((string) ($button['name'] ?? '')) === '') {
                return 'each menu button requires name';
            }

            if (isset($button['sub_button']) && is_array($button['sub_button']) && count($button['sub_button']) > 5) {
                return 'sub menu button count cannot exceed 5';
            }
        }

        return null;
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
