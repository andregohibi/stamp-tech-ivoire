<?php

namespace App\Filament\Resources\QrStampResource\Pages;

use App\Filament\Resources\QrStampResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQrStamp extends EditRecord
{
    protected static string $resource = QrStampResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
