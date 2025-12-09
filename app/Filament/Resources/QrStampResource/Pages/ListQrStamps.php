<?php

namespace App\Filament\Resources\QrStampResource\Pages;

use App\Filament\Resources\QrStampResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQrStamps extends ListRecords
{
    protected static string $resource = QrStampResource::class;

    


    public function getBreadcrumb(): string
    {
        return 'Liste des qr codes';
    }

    public function getTitle(): string
    {
        return 'Qr codes';
    }

    
}
