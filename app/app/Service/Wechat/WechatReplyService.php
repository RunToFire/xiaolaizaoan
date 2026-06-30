<?php

declare(strict_types=1);

namespace App\Service\Wechat;

use App\Model\WechatReplyRule;

class WechatReplyService
{
    public function buildReplyXml(int $accountId, array $message): string
    {
        $rule = $this->findMatchedRule($accountId, $message);
        if ($rule === null) {
            return $this->text($message, 'success');
        }

        $content = is_array($rule->reply_content) ? $rule->reply_content : [];

        return match ($rule->reply_type) {
            'image' => $this->media($message, 'image', (string) ($content['media_id'] ?? '')),
            'voice' => $this->media($message, 'voice', (string) ($content['media_id'] ?? '')),
            'video' => $this->video(
                $message,
                (string) ($content['media_id'] ?? ''),
                (string) ($content['title'] ?? ''),
                (string) ($content['description'] ?? '')
            ),
            'music' => $this->music($message, $content),
            'news' => $this->news($message, $content['articles'] ?? []),
            default => $this->text($message, (string) ($content['text'] ?? 'success')),
        };
    }

    private function findMatchedRule(int $accountId, array $message): ?WechatReplyRule
    {
        $rules = WechatReplyRule::query()
            ->where('account_id', $accountId)
            ->where('is_active', 1)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matchesType($rule, $message)) {
                continue;
            }

            if (! $this->matchesEvent($rule, $message)) {
                continue;
            }

            if (! $this->matchesKeyword($rule, $message)) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    private function matchesType(WechatReplyRule $rule, array $message): bool
    {
        $ruleType = (string) ($rule->msg_type ?? '*');
        $msgType = strtolower((string) ($message['MsgType'] ?? ''));

        return $ruleType === '*' || strtolower($ruleType) === $msgType;
    }

    private function matchesEvent(WechatReplyRule $rule, array $message): bool
    {
        $ruleEvent = (string) ($rule->event ?? '');
        if ($ruleEvent === '') {
            return true;
        }

        return strtolower($ruleEvent) === strtolower((string) ($message['Event'] ?? ''));
    }

    private function matchesKeyword(WechatReplyRule $rule, array $message): bool
    {
        $keyword = (string) ($rule->keyword ?? '');
        if ($keyword === '') {
            return true;
        }

        $text = (string) ($message['Content'] ?? '');
        if ($text === '') {
            return false;
        }

        return match ((string) ($rule->keyword_match ?? 'contains')) {
            'equals' => $text === $keyword,
            'prefix' => str_starts_with($text, $keyword),
            default => str_contains($text, $keyword),
        };
    }

    private function text(array $message, string $text): string
    {
        return $this->xml([
            'ToUserName' => (string) ($message['FromUserName'] ?? ''),
            'FromUserName' => (string) ($message['ToUserName'] ?? ''),
            'CreateTime' => (string) time(),
            'MsgType' => 'text',
            'Content' => $text,
        ]);
    }

    private function media(array $message, string $type, string $mediaId): string
    {
        return $this->xml([
            'ToUserName' => (string) ($message['FromUserName'] ?? ''),
            'FromUserName' => (string) ($message['ToUserName'] ?? ''),
            'CreateTime' => (string) time(),
            'MsgType' => $type,
            ucfirst($type) => [
                'MediaId' => $mediaId,
            ],
        ]);
    }

    private function video(array $message, string $mediaId, string $title, string $description): string
    {
        return $this->xml([
            'ToUserName' => (string) ($message['FromUserName'] ?? ''),
            'FromUserName' => (string) ($message['ToUserName'] ?? ''),
            'CreateTime' => (string) time(),
            'MsgType' => 'video',
            'Video' => [
                'MediaId' => $mediaId,
                'Title' => $title,
                'Description' => $description,
            ],
        ]);
    }

    private function music(array $message, array $content): string
    {
        return $this->xml([
            'ToUserName' => (string) ($message['FromUserName'] ?? ''),
            'FromUserName' => (string) ($message['ToUserName'] ?? ''),
            'CreateTime' => (string) time(),
            'MsgType' => 'music',
            'Music' => [
                'Title' => (string) ($content['title'] ?? ''),
                'Description' => (string) ($content['description'] ?? ''),
                'MusicUrl' => (string) ($content['music_url'] ?? ''),
                'HQMusicUrl' => (string) ($content['hq_music_url'] ?? ''),
                'ThumbMediaId' => (string) ($content['thumb_media_id'] ?? ''),
            ],
        ]);
    }

    private function news(array $message, array $articles): string
    {
        $items = [];
        foreach (array_slice($articles, 0, 8) as $article) {
            if (! is_array($article)) {
                continue;
            }

            $items[] = [
                'Title' => (string) ($article['title'] ?? ''),
                'Description' => (string) ($article['description'] ?? ''),
                'PicUrl' => (string) ($article['pic_url'] ?? ''),
                'Url' => (string) ($article['url'] ?? ''),
            ];
        }

        return $this->xml([
            'ToUserName' => (string) ($message['FromUserName'] ?? ''),
            'FromUserName' => (string) ($message['ToUserName'] ?? ''),
            'CreateTime' => (string) time(),
            'MsgType' => 'news',
            'ArticleCount' => (string) count($items),
            'Articles' => [
                'item' => $items,
            ],
        ]);
    }

    private function xml(array $data): string
    {
        return '<xml>' . $this->nodes($data) . '</xml>';
    }

    private function nodes(array $data): string
    {
        $xml = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        $xml .= sprintf('<%1$s>%2$s</%1$s>', $key, $this->nodes($item));
                    }
                    continue;
                }

                $xml .= sprintf('<%1$s>%2$s</%1$s>', $key, $this->nodes($value));
                continue;
            }

            $xml .= sprintf('<%1$s><![CDATA[%2$s]]></%1$s>', $key, $this->cdata((string) $value));
        }

        return $xml;
    }

    private function cdata(string $value): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
