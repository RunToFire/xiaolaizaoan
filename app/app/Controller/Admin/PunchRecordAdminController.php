<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\HeartQuote;
use App\Model\MaterialImage;
use App\Model\PunchRecord;
use App\Model\WechatUser;
use Psr\Http\Message\ResponseInterface;

class PunchRecordAdminController extends AbstractController
{
    public function index(): ResponseInterface
    {
        $query = PunchRecord::query()->orderByDesc('id');

        $accountId = (int) $this->request->input('account_id', 0);
        if ($accountId > 0) {
            $query->where('account_id', $accountId);
        }

        $records = $query->limit(200)->get();
        $users = WechatUser::query()
            ->whereIn('id', $records->pluck('user_id')->merge($records->pluck('parent_user_id'))->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');
        $images = MaterialImage::query()
            ->whereIn('id', $records->pluck('material_image_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');
        $quotes = HeartQuote::query()
            ->whereIn('id', $records->pluck('heart_quote_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');

        return $this->json([
            'items' => $records->map(static function (PunchRecord $record) use ($users, $images, $quotes): array {
                $row = $record->toArray();
                $user = $users->get($record->user_id);
                $parent = $record->parent_user_id ? $users->get($record->parent_user_id) : null;
                $image = $record->material_image_id ? $images->get($record->material_image_id) : null;
                $quote = $record->heart_quote_id ? $quotes->get($record->heart_quote_id) : null;
                $row['openid'] = $user?->openid;
                $row['parent_openid'] = $parent?->openid;
                $row['material_image_title'] = $image?->title;
                $row['material_image_url'] = $image?->file_url;
                $row['quote_content'] = $quote?->content;
                return $row;
            }),
        ]);
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
