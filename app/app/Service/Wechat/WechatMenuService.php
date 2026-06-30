<?php

declare(strict_types=1);

namespace App\Service\Wechat;

use App\Model\WechatOfficialAccount;
use GuzzleHttp\Client;
use RuntimeException;

class WechatMenuService
{
    public function publish(WechatOfficialAccount $account): array
    {
        $menu = $account->menu_config;
        if (! is_array($menu) || $menu === []) {
            throw new RuntimeException('menu_config is empty');
        }

        $accessToken = $this->accessToken($account);
        $client = new Client(['timeout' => 10.0]);
        $response = $client->post('https://api.weixin.qq.com/cgi-bin/menu/create', [
            'query' => ['access_token' => $accessToken],
            'json' => $menu,
        ]);

        $result = json_decode((string) $response->getBody(), true);
        if (! is_array($result)) {
            throw new RuntimeException('invalid wechat menu response');
        }

        if ((int) ($result['errcode'] ?? 0) !== 0) {
            throw new RuntimeException((string) ($result['errmsg'] ?? 'wechat menu publish failed'));
        }

        return $result;
    }

    private function accessToken(WechatOfficialAccount $account): string
    {
        $client = new Client(['timeout' => 10.0]);
        $response = $client->get('https://api.weixin.qq.com/cgi-bin/token', [
            'query' => [
                'grant_type' => 'client_credential',
                'appid' => $account->app_id,
                'secret' => $account->app_secret,
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);
        if (! is_array($result) || empty($result['access_token'])) {
            throw new RuntimeException((string) ($result['errmsg'] ?? 'wechat access_token request failed'));
        }

        return (string) $result['access_token'];
    }
}
