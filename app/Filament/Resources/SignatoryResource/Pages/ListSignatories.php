<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use App\Filament\Resources\SignatoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignatories extends ListRecords
{
    protected static string $resource = SignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Créer un signataire')
                ->icon('heroicon-m-plus')
                ->color('success'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Liste des signataires';
    }

    public function getTitle(): string
    {
        return 'Liste des signataires';
    }
}
