<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\WechatOfficialAccount;
use Psr\Http\Message\ResponseInterface;

class WechatOfficialAccountAdminController extends AbstractController
{
    public function index(): ResponseInterface
    {
        $accounts = WechatOfficialAccount::query()
            ->orderByDesc('id')
            ->get();

        return $this->json(['items' => $accounts]);
    }

    public function show(int $id): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($id);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        return $this->json(['item' => $account]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->accountPayload();
        $error = $this->validateAccount($data);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $account = WechatOfficialAccount::query()->create($data);

        return $this->json(['item' => $account], 201);
    }

    public function update(int $id): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($id);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        $data = $this->accountPayload(false);
        $error = $this->validateAccount($data, false);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $account->fill($data);
        $account->save();

        return $this->json(['item' => $account->refresh()]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $account = WechatOfficialAccount::query()->find($id);
        if ($account === null) {
            return $this->json(['message' => 'official account not found'], 404);
        }

        $account->delete();

        return $this->json(['message' => 'deleted']);
    }

    private function accountPayload(bool $withDefaults = true): array
    {
        $input = $this->request->all();
        $data = [
            'name' => $this->request->input('name'),
            'app_id' => $this->request->input('app_id'),
            'app_secret' => $this->request->input('app_secret'),
            'token' => $this->request->input('token'),
            'aes_key' => $this->request->input('aes_key'),
            'original_id' => $this->request->input('original_id'),
            'qrcode_url' => $this->request->input('qrcode_url'),
            'encoding_type' => $this->request->input('encoding_type', $withDefaults ? 'plaintext' : null),
            'is_active' => (int) $this->request->input('is_active', $withDefaults ? 1 : null),
            'menu_config' => $this->request->input('menu_config'),
            'remark' => $this->request->input('remark'),
        ];

        if (! $withDefaults && ! array_key_exists('is_active', $input)) {
            unset($data['is_active']);
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function validateAccount(array $data, bool $creating = true): ?string
    {
        foreach (['name', 'app_id', 'app_secret', 'token'] as $field) {
            if ($creating && trim((string) ($data[$field] ?? '')) === '') {
                return "{$field} is required";
            }
        }

        if (isset($data['encoding_type']) && ! in_array($data['encoding_type'], ['plaintext', 'compatible', 'safe'], true)) {
            return 'encoding_type must be plaintext, compatible, or safe';
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
