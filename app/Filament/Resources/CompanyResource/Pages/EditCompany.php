<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\CompanyResource;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->modalHeading('Suppression de l\'entreprise')
                ->modalDescription(fn ($record) => "Êtes-vous sûr de vouloir supprimer l'entreprise « {$record->name} » ? Cette action est irréversible.")
                ->modalSubmitActionLabel('Oui, supprimer')
                ->modalCancelActionLabel('Annuler')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->successNotificationTitle('Entreprise supprimée')
                ->successNotification(function ($record) {
                    return Notification::make()
                        ->success()
                        ->title('Entreprise supprimée avec succès')
                        ->body("L'entreprise « {$record->name} » a été supprimée définitivement.")
                        ->icon('heroicon-o-check-circle')
                        ->duration(5000)
                        ->send();
                })
                 ->after(function ($record) {
                    // Notification supplémentaire pour l'admin
                    Notification::make()
                        ->warning()
                        ->title('Rappel de suppression')
                        ->body("L'entreprise {$record->name} avait {$record->qr_used} QR codes actifs.")
                        ->icon('heroicon-o-information-circle')
                        ->duration(7000)
                        ->send();
                })
                ->visible(fn () => auth()->user()->role === 'super_admin'),
            

           /*  Actions\ForceDeleteAction::make()
                ->label('Forcer la suppression')
                 ->requiresConfirmation()
                ->visible(fn () => auth()->user()->role === 'super_admin'),

            Actions\RestoreAction::make()
                ->label('Restaurer') */
        ];
    }

      public function getBreadcrumb(): string
    {
        return "modifier {$this->record->name}";
    }

    public function getTitle(): string
    {
        return "Modifier {$this->record->name}";
    }


}
