<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use Filament\Actions;
use App\Services\QrStampService;
use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SignatoryResource;
use Filament\Forms\Components\DateTimePicker;

class EditSignatory extends EditRecord
{
    protected static string $resource = SignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->color('danger'),

            Action::make('update_qr_code')
                ->label('Mettre à jour QR Code')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->hasQrStamp())
                ->modalHeading('Mettre à jour le QR Code')
                ->modalDescription('Cette action mettra à jour le QR Code avec les informations actuelles du signataire.')
                ->modalSubmitActionLabel('Mettre à jour')
                ->modalCancelActionLabel('Annuler')
                ->form([
                    DateTimePicker::make('expires_at')
                        ->label('Nouvelle date d\'expiration (optionnelle)')
                        ->helperText('Laisser vide pour conserver la date actuelle')
                        ->native(false)
                        ->minDate(now())
                        ->default(fn () => $this->record->qrStamp?->expires_at),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(QrStampService::class);
                        
                        // Mettre à jour le QR avec les nouvelles données
                        $qr = $service->updateQrStamp($this->record, $data);

                        Notification::make()
                            ->success()
                            ->title('QR Code mis à jour')
                            ->body('Le QR Code a été mis à jour avec succès avec les nouvelles informations.')
                            ->send();

                        // Recharger la page pour voir les changements
                        return redirect()->route('filament.admin.resources.signatories.edit', $this->record);

                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erreur de mise à jour')
                            ->body('Impossible de mettre à jour le QR Code: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-exclamation-triangle'),
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

      protected function afterSave(): void
    {
        // Si le signataire a un QR Code, proposer de le mettre à jour
        if ($this->record->hasQrStamp()) {
            Notification::make()
                ->info()
                ->title('QR Code existant détecté')
                ->body('N\'oubliez pas de mettre à jour le QR Code si nécessaire avec le bouton "Mettre à jour QR Code".')
                ->persistent()
                ->send();
        }
    }
}
