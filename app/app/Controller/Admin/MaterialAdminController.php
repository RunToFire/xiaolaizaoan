<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\HeartQuote;
use App\Model\MaterialGroup;
use App\Model\MaterialImage;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

class MaterialAdminController extends AbstractController
{
    public function groups(): ResponseInterface
    {
        $items = MaterialGroup::query()
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get();

        return $this->json(['items' => $items]);
    }

    public function storeGroup(): ResponseInterface
    {
        $data = $this->groupPayload();
        if (($error = $this->validateGroup($data)) !== null) {
            return $this->json(['message' => $error], 422);
        }

        $item = MaterialGroup::query()->create($data);

        return $this->json(['item' => $item], 201);
    }

    public function updateGroup(int $id): ResponseInterface
    {
        $item = MaterialGroup::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'material group not found'], 404);
        }

        $data = $this->groupPayload(false);
        if (($error = $this->validateGroup($data, false)) !== null) {
            return $this->json(['message' => $error], 422);
        }

        $item->fill($data);
        $item->save();

        return $this->json(['item' => $item->refresh()]);
    }

    public function destroyGroup(int $id): ResponseInterface
    {
        $item = MaterialGroup::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'material group not found'], 404);
        }

        MaterialImage::query()->where('group_id', $id)->update(['group_id' => null]);
        HeartQuote::query()->where('group_id', $id)->update(['group_id' => null]);
        $item->delete();

        return $this->json(['message' => 'deleted']);
    }

    public function images(): ResponseInterface
    {
        $items = MaterialImage::query()->orderByDesc('id')->get();
        $groups = $this->groupMap();

        return $this->json([
            'items' => $items->map(static function (MaterialImage $image) use ($groups): array {
                $row = $image->toArray();
                $row['group_name'] = $groups[(int) ($image->group_id ?? 0)] ?? '未分组';
                return $row;
            }),
        ]);
    }

    public function storeImage(): ResponseInterface
    {
        $file = $this->request->file('image');
        if (! $file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json(['message' => 'image is required'], 422);
        }

        $mimeType = (string) $file->getClientMediaType();
        $extension = $this->imageExtension($mimeType);
        if ($extension === null) {
            return $this->json(['message' => 'only jpg, png, webp images are supported'], 422);
        }

        $dir = BASE_PATH . '/runtime/materials/images';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $absolutePath = $dir . '/' . $filename;
        $relativePath = 'runtime/materials/images/' . $filename;
        $file->moveTo($absolutePath);

        $size = @getimagesize($absolutePath) ?: [0, 0];
        $item = MaterialImage::query()->create([
            'group_id' => $this->nullableInt('group_id'),
            'title' => $this->request->input('title') ?: pathinfo((string) $file->getClientFilename(), PATHINFO_FILENAME),
            'file_path' => $relativePath,
            'file_url' => '',
            'mime_type' => $mimeType,
            'file_size' => (int) filesize($absolutePath),
            'width' => (int) ($size[0] ?? 0),
            'height' => (int) ($size[1] ?? 0),
            'is_active' => (int) $this->request->input('is_active', 1),
            'remark' => $this->request->input('remark'),
        ]);

        $item->file_url = '/admin-api/materials/images/' . $item->id . '/file';
        $item->save();

        return $this->json(['item' => $item->refresh()], 201);
    }

    public function updateImage(int $id): ResponseInterface
    {
        $item = MaterialImage::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'material image not found'], 404);
        }

        $item->fill([
            'group_id' => $this->nullableInt('group_id'),
            'title' => $this->request->input('title'),
            'is_active' => (int) $this->request->input('is_active', 1),
            'remark' => $this->request->input('remark'),
        ]);
        $item->save();

        return $this->json(['item' => $item->refresh()]);
    }

    public function destroyImage(int $id): ResponseInterface
    {
        $item = MaterialImage::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'material image not found'], 404);
        }

        $path = BASE_PATH . '/' . $item->file_path;
        if (is_file($path)) {
            unlink($path);
        }
        $item->delete();

        return $this->json(['message' => 'deleted']);
    }

    public function imageFile(int $id): ResponseInterface
    {
        $item = MaterialImage::query()->find($id);
        if ($item === null) {
            return $this->response->raw('not found')->withStatus(404);
        }

        $path = BASE_PATH . '/' . $item->file_path;
        if (! is_file($path)) {
            return $this->response->raw('not found')->withStatus(404);
        }

        return $this->response
            ->withHeader('Content-Type', $item->mime_type ?: 'image/jpeg')
            ->withBody(new SwooleStream((string) file_get_contents($path)));
    }

    public function quotes(): ResponseInterface
    {
        $items = HeartQuote::query()->orderByDesc('id')->get();
        $groups = $this->groupMap();

        return $this->json([
            'items' => $items->map(static function (HeartQuote $quote) use ($groups): array {
                $row = $quote->toArray();
                $row['group_name'] = $groups[(int) ($quote->group_id ?? 0)] ?? '未分组';
                return $row;
            }),
        ]);
    }

    public function storeQuote(): ResponseInterface
    {
        $data = $this->quotePayload();
        if (($error = $this->validateQuote($data)) !== null) {
            return $this->json(['message' => $error], 422);
        }

        $item = HeartQuote::query()->create($data);

        return $this->json(['item' => $item], 201);
    }

    public function updateQuote(int $id): ResponseInterface
    {
        $item = HeartQuote::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'heart quote not found'], 404);
        }

        $data = $this->quotePayload(false);
        if (($error = $this->validateQuote($data, false)) !== null) {
            return $this->json(['message' => $error], 422);
        }

        $item->fill($data);
        $item->save();

        return $this->json(['item' => $item->refresh()]);
    }

    public function destroyQuote(int $id): ResponseInterface
    {
        $item = HeartQuote::query()->find($id);
        if ($item === null) {
            return $this->json(['message' => 'heart quote not found'], 404);
        }

        $item->delete();

        return $this->json(['message' => 'deleted']);
    }

    private function groupPayload(bool $withDefaults = true): array
    {
        $input = $this->request->all();
        $data = [
            'name' => $this->request->input('name'),
            'type' => $this->request->input('type', $withDefaults ? 'image' : null),
            'sort_order' => $this->request->input('sort_order', $withDefaults ? 0 : null),
            'is_active' => $this->request->input('is_active', $withDefaults ? 1 : null),
            'remark' => $this->request->input('remark'),
        ];

        if (array_key_exists('sort_order', $input) || $withDefaults) {
            $data['sort_order'] = (int) $data['sort_order'];
        }

        if (array_key_exists('is_active', $input) || $withDefaults) {
            $data['is_active'] = (int) $data['is_active'];
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function quotePayload(bool $withDefaults = true): array
    {
        $input = $this->request->all();
        $data = [
            'group_id' => $this->nullableInt('group_id'),
            'content' => $this->request->input('content'),
            'author' => $this->request->input('author'),
            'is_active' => $this->request->input('is_active', $withDefaults ? 1 : null),
            'remark' => $this->request->input('remark'),
        ];

        if (array_key_exists('is_active', $input) || $withDefaults) {
            $data['is_active'] = (int) $data['is_active'];
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function validateGroup(array $data, bool $creating = true): ?string
    {
        if ($creating && trim((string) ($data['name'] ?? '')) === '') {
            return 'name is required';
        }

        if (isset($data['type']) && ! in_array($data['type'], ['image', 'quote'], true)) {
            return 'unsupported group type';
        }

        return null;
    }

    private function validateQuote(array $data, bool $creating = true): ?string
    {
        if ($creating && trim((string) ($data['content'] ?? '')) === '') {
            return 'content is required';
        }

        return null;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->request->input($key);
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function imageExtension(string $mimeType): ?string
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ][$mimeType] ?? null;
    }

    private function groupMap(): array
    {
        return MaterialGroup::query()
            ->pluck('name', 'id')
            ->all();
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
