<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SignatoryResource;

class CreateSignatory extends CreateRecord
{
    protected static string $resource = SignatoryResource::class;

    public function getBreadcrumb(): string
    {
        return "Ajouter un signataire";
    }

    public function getTitle(): string
    {
        return "Ajouter un signataire";
    }

    
    protected function getFormActions(): array
    {
        return [
           Action::make('save')
                ->label('Enregistrer')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-m-check-circle'),

            Action::make('save_and_create_another')
                ->label('Enregistrer & Nouveau')
                ->submit('saveAndCreateAnother')
                ->color('primary')
                ->icon('heroicon-m-plus-circle'),

            Action::make('cancel')
                ->label('Annuler')
                ->url(static::getResource()::getUrl())
                ->color('gray')
                ->icon('heroicon-m-x-circle'),
        ];
    }
}
