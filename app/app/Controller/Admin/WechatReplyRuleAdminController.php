<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\WechatOfficialAccount;
use App\Model\WechatReplyRule;
use Psr\Http\Message\ResponseInterface;

class WechatReplyRuleAdminController extends AbstractController
{
    public function index(): ResponseInterface
    {
        $query = WechatReplyRule::query()->orderByDesc('priority')->orderByDesc('id');
        $accountId = (int) $this->request->input('account_id', 0);
        if ($accountId > 0) {
            $query->where('account_id', $accountId);
        }

        return $this->json(['items' => $query->get()]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->rulePayload();
        $error = $this->validateRule($data);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $rule = WechatReplyRule::query()->create($data);

        return $this->json(['item' => $rule], 201);
    }

    public function update(int $id): ResponseInterface
    {
        $rule = WechatReplyRule::query()->find($id);
        if ($rule === null) {
            return $this->json(['message' => 'reply rule not found'], 404);
        }

        $data = $this->rulePayload(false);
        $error = $this->validateRule($data, false);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $rule->fill($data);
        $rule->save();

        return $this->json(['item' => $rule->refresh()]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $rule = WechatReplyRule::query()->find($id);
        if ($rule === null) {
            return $this->json(['message' => 'reply rule not found'], 404);
        }

        $rule->delete();

        return $this->json(['message' => 'deleted']);
    }

    private function rulePayload(bool $withDefaults = true): array
    {
        $input = $this->request->all();
        $content = $this->request->input('reply_content', $withDefaults ? [] : null);
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            $content = is_array($decoded) ? $decoded : ['text' => $content];
        }

        $data = [
            'account_id' => $this->request->input('account_id'),
            'name' => $this->request->input('name'),
            'msg_type' => $this->request->input('msg_type', $withDefaults ? '*' : null),
            'event' => $this->request->input('event'),
            'keyword' => $this->request->input('keyword'),
            'keyword_match' => $this->request->input('keyword_match', $withDefaults ? 'contains' : null),
            'reply_type' => $this->request->input('reply_type', $withDefaults ? 'text' : null),
            'reply_content' => $content,
            'priority' => (int) $this->request->input('priority', $withDefaults ? 0 : null),
            'is_active' => (int) $this->request->input('is_active', $withDefaults ? 1 : null),
        ];

        if (! $withDefaults && ! array_key_exists('priority', $input)) {
            unset($data['priority']);
        }

        if (! $withDefaults && ! array_key_exists('is_active', $input)) {
            unset($data['is_active']);
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function validateRule(array $data, bool $creating = true): ?string
    {
        if ($creating && (int) ($data['account_id'] ?? 0) <= 0) {
            return 'account_id is required';
        }

        if (isset($data['account_id']) && WechatOfficialAccount::query()->find((int) $data['account_id']) === null) {
            return 'official account not found';
        }

        if (isset($data['msg_type']) && ! in_array($data['msg_type'], ['*', 'text', 'image', 'voice', 'video', 'shortvideo', 'location', 'link', 'event'], true)) {
            return 'unsupported msg_type';
        }

        if (isset($data['keyword_match']) && ! in_array($data['keyword_match'], ['contains', 'equals', 'prefix'], true)) {
            return 'unsupported keyword_match';
        }

        if (isset($data['reply_type']) && ! in_array($data['reply_type'], ['text', 'image', 'voice', 'video', 'music', 'news'], true)) {
            return 'unsupported reply_type';
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
