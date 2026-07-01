<?php

declare(strict_types=1);

namespace App\Service\Wechat;

use App\Model\WechatOfficialAccount;
use App\Model\WechatUser;
use GuzzleHttp\Client;
use RuntimeException;
use Throwable;

class WechatUserService
{
    public function touchUser(int $accountId, array $message): ?WechatUser
    {
        $openid = (string) ($message['FromUserName'] ?? '');
        if ($openid === '') {
            return null;
        }

        $user = WechatUser::query()->firstOrCreate([
            'account_id' => $accountId,
            'openid' => $openid,
        ], [
            'last_active_at' => date('Y-m-d H:i:s'),
        ]);

        $user->last_active_at = date('Y-m-d H:i:s');
        if ($this->isSubscribeEvent($message) && $user->subscribed_at === null) {
            $user->subscribed_at = date('Y-m-d H:i:s');
        }

        $this->bindParentFromScene($accountId, $user, $this->sceneFromMessage($message));
        $this->saveLocation($user, $message);

        $account = WechatOfficialAccount::query()->find($accountId);
        if ($account instanceof WechatOfficialAccount && $this->shouldSyncProfile($user, $message)) {
            $this->syncProfile($account, $user);
        }

        $user->save();

        return $user;
    }

    public function ensureQrcodePath(WechatOfficialAccount $account, WechatUser $user): string
    {
        if (! $user->qr_scene) {
            $user->qr_scene = 'REF_' . $user->id;
        }

        if (! $user->qr_ticket || ! $user->qr_url) {
            $result = $this->createPermanentQrcode($account, (string) $user->qr_scene);
            $user->qr_ticket = (string) ($result['ticket'] ?? '');
            $user->qr_url = (string) ($result['url'] ?? '');
            $user->save();
        }

        $dir = BASE_PATH . '/runtime/materials/qrcodes';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . '/user_' . $user->id . '.jpg';
        if (is_file($path)) {
            return $path;
        }

        if (! $user->qr_ticket) {
            throw new RuntimeException('用户二维码生成失败');
        }

        $client = new Client(['timeout' => 10.0]);
        $response = $client->get('https://mp.weixin.qq.com/cgi-bin/showqrcode', [
            'query' => ['ticket' => $user->qr_ticket],
        ]);
        file_put_contents($path, (string) $response->getBody());

        return $path;
    }

    public function ensureAvatarPath(WechatUser $user): ?string
    {
        $avatarUrl = trim((string) ($user->avatar_url ?? ''));
        if ($avatarUrl === '') {
            return null;
        }

        $dir = BASE_PATH . '/runtime/materials/avatars';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . '/user_' . $user->id . '.jpg';
        if (is_file($path) && filesize($path) > 0) {
            return $path;
        }

        try {
            $client = new Client(['timeout' => 12.0]);
            $response = $client->get($avatarUrl);
            file_put_contents($path, (string) $response->getBody());
            return is_file($path) ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function coordinatesForPunch(?WechatUser $user, array $message): array
    {
        $location = $this->locationFromMessage($message);
        if ($location['latitude'] !== null || $location['longitude'] !== null) {
            return $location;
        }

        return [
            'latitude' => $user?->last_latitude,
            'longitude' => $user?->last_longitude,
            'label' => $user?->last_location_label,
        ];
    }

    private function shouldSyncProfile(WechatUser $user, array $message): bool
    {
        if ($this->isSubscribeEvent($message)) {
            return true;
        }

        return trim((string) ($user->nickname ?? '')) === '' || trim((string) ($user->avatar_url ?? '')) === '';
    }

    private function syncProfile(WechatOfficialAccount $account, WechatUser $user): void
    {
        try {
            $client = new Client(['timeout' => 10.0]);
            $response = $client->get('https://api.weixin.qq.com/cgi-bin/user/info', [
                'query' => [
                    'access_token' => $this->accessToken($account),
                    'openid' => $user->openid,
                    'lang' => 'zh_CN',
                ],
            ]);

            $result = json_decode((string) $response->getBody(), true);
            if (! is_array($result) || (int) ($result['subscribe'] ?? 0) !== 1) {
                return;
            }

            $nickname = trim((string) ($result['nickname'] ?? ''));
            $avatarUrl = trim((string) ($result['headimgurl'] ?? ''));
            if ($nickname !== '') {
                $user->nickname = $nickname;
            }
            if ($avatarUrl !== '') {
                $user->avatar_url = $avatarUrl;
            }
        } catch (Throwable) {
        }
    }

    private function bindParentFromScene(int $accountId, WechatUser $user, string $scene): void
    {
        if ($scene === '' || ! str_starts_with($scene, 'REF_')) {
            return;
        }

        $parentId = (int) substr($scene, 4);
        if ($parentId <= 0 || $parentId === (int) $user->id || $user->parent_user_id) {
            return;
        }

        $parent = WechatUser::query()
            ->where('account_id', $accountId)
            ->where('id', $parentId)
            ->first();
        if ($parent === null) {
            return;
        }

        $user->parent_user_id = $parent->id;
    }

    private function saveLocation(WechatUser $user, array $message): void
    {
        $location = $this->locationFromMessage($message);
        if ($location['latitude'] === null && $location['longitude'] === null) {
            return;
        }

        $user->last_latitude = $location['latitude'];
        $user->last_longitude = $location['longitude'];
        $user->last_location_label = $location['label'];
        $user->location_updated_at = date('Y-m-d H:i:s');
    }

    private function locationFromMessage(array $message): array
    {
        $latitude = $message['Latitude'] ?? $message['Location_X'] ?? null;
        $longitude = $message['Longitude'] ?? $message['Location_Y'] ?? null;

        return [
            'latitude' => $latitude !== null && $latitude !== '' ? (float) $latitude : null,
            'longitude' => $longitude !== null && $longitude !== '' ? (float) $longitude : null,
            'label' => (string) ($message['Label'] ?? ''),
        ];
    }

    private function sceneFromMessage(array $message): string
    {
        $eventKey = (string) ($message['EventKey'] ?? '');
        if (str_starts_with($eventKey, 'qrscene_')) {
            return substr($eventKey, 8);
        }

        return $eventKey;
    }

    private function isSubscribeEvent(array $message): bool
    {
        return strtolower((string) ($message['MsgType'] ?? '')) === 'event'
            && strtolower((string) ($message['Event'] ?? '')) === 'subscribe';
    }

    private function createPermanentQrcode(WechatOfficialAccount $account, string $scene): array
    {
        $client = new Client(['timeout' => 10.0]);
        $response = $client->post('https://api.weixin.qq.com/cgi-bin/qrcode/create', [
            'query' => ['access_token' => $this->accessToken($account)],
            'json' => [
                'action_name' => 'QR_LIMIT_STR_SCENE',
                'action_info' => [
                    'scene' => ['scene_str' => $scene],
                ],
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);
        if (! is_array($result) || empty($result['ticket'])) {
            throw new RuntimeException('生成带参二维码失败：' . (string) ($result['errmsg'] ?? 'wechat qrcode request failed'));
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
            throw new RuntimeException('获取 access_token 失败：' . (string) ($result['errmsg'] ?? 'wechat access_token request failed'));
        }

        return (string) $result['access_token'];
    }
}
