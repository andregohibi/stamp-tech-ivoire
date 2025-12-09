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

            Actions\Action::make('qrcodes')
                ->label('Gestion des QR Codes')
                ->icon('heroicon-o-qr-code')
                ->url(SignatoryResource::getUrl('qrcodes'))
                ->extraAttributes([
                    'style'=>  '
                            background-color:#4FC3F7;
                            color:white;
                            border-color:#4FC3F7;
                            border-radius:8px;
                        ',
                     'class' => 'hover:bg-[#29B6F6]',
                ])
                ->button(),


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
