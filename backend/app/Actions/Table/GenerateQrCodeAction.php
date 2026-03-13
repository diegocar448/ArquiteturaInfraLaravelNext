<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class GenerateQrCodeAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    /**
     * Gera o QR Code como string base64 (data URI) para a mesa.
     *
     * @return array{table: Table, qrcode: string, url: string}|null
     */
    public function execute(int $id): ?array
    {
        $table = $this->repository->findById($id);

        if (! $table) {
            return null;
        }

        $menuUrl = $this->buildMenuUrl($table);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'imageBase64' => true,
        ]);

        $qrcode = (new QRCode($options))->render($menuUrl);

        return [
            'table' => $table,
            'qrcode' => $qrcode,
            'url' => $menuUrl,
        ];
    }

    private function buildMenuUrl(Table $table): string
    {
        $baseUrl = config('app.frontend_url', 'http://localhost');

        return "{$baseUrl}/menu?table={$table->uuid}";
    }
}
