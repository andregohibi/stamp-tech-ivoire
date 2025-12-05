<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SignatoryResource;

class EditSignatory extends EditRecord
{
    protected static string $resource = SignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return "Modifier {$this->record->first_name} {$this->record->last_name}";
    }

    
     public function getBreadcrumb(): string
    {
        return 'Modification';
    }

    protected function getFormActions(): array
    {
        return [
           Action::make('save')
                ->label('Enregistrer les modifications')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-m-check-circle'),


            Action::make('cancel')
                ->label('Annuler')
                ->url(static::getResource()::getUrl())
                ->color('gray')
                ->icon('heroicon-m-x-circle'),
        ];
    }
}
