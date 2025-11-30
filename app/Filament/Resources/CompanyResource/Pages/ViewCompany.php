<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Actions\EditAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\CompanyResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function getBreadcrumb(): string
    {
        return "Détails de l'entreprise {$this->record->name}";
    }

    public function getTitle(): string
    {
        return $this->record->legal_name . ' - ' . $this->record->sector . ' (' . $this->record->registration_number . ')';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-m-pencil')
                ->color('primary'),
           
            DeleteAction::make()
                ->label('Supprimer')
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->visible(fn () => auth()->user()->role === 'super_admin'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Section Logo et Informations principales
                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('logo')
                                    ->label('Logo')
                                    ->size(150)
                                    ->circular()
                                    ->defaultImageUrl(url('/images/default-company-logo.png'))
                                    ->columnSpan(1),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nom commercial')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-building-office')
                                            ->color('primary'),

                                        TextEntry::make('legal_name')
                                            ->label('Raison sociale')
                                            ->icon('heroicon-o-document-text'),

                                        TextEntry::make('registration_number')
                                            ->label('Numéro RCCM/NIU')
                                            ->icon('heroicon-o-identification')
                                            ->copyable()
                                            ->copyMessage('Numéro copié!')
                                            ->placeholder('Non renseigné'),

                                        TextEntry::make('sector')
                                            ->label('Secteur d\'activité')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Technology' => 'info',
                                                'Finance' => 'success',
                                                'Healthcare' => 'danger',
                                                'Education' => 'warning',
                                                default => 'gray',
                                            })
                                            ->icon('heroicon-o-briefcase')
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'Technology' => 'Technologie',
                                                'Finance' => 'Finance',
                                                'Healthcare' => 'Santé',
                                                'Education' => 'Éducation',
                                                'Manufacturing' => 'Industrie',
                                                'Retail' => 'Commerce de détail',
                                                'Services' => 'Services',
                                                'Construction' => 'Construction',
                                                'Transportation' => 'Transport',
                                                'Agriculture' => 'Agriculture',
                                                default => $state,
                                            }),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columns(1),

                // Section Coordonnées
                Section::make('Coordonnées')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email professionnel')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copié!')
                                    ->url(fn ($state) => "mailto:{$state}"),

                                TextEntry::make('phone')
                                    ->label('Téléphone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->copyMessage('Téléphone copié!')
                                    ->url(fn ($state) => "tel:{$state}"),

                                TextEntry::make('website')
                                    ->label('Site web')
                                    ->icon('heroicon-o-globe-alt')
                                    ->url(fn ($state) => $state ? (str_starts_with($state, 'http') ? $state : "https://{$state}") : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('Non renseigné'),

                                TextEntry::make('address')
                                    ->label('Adresse')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('Non renseignée'),

                                TextEntry::make('city')
                                    ->label('Ville')
                                    ->icon('heroicon-o-building-office-2'),

                                TextEntry::make('country')
                                    ->label('Pays')
                                    ->icon('heroicon-o-flag')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->collapsible(),

                // Section Abonnement
                Section::make('Abonnement et Quota')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subscription_type')
                                    ->label('Type d\'abonnement')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'free' => 'gray',
                                        'basic' => 'info',
                                        'premium' => 'warning',
                                        'enterprise' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon('heroicon-o-sparkles')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'free' => 'Gratuit',
                                        'basic' => 'Basique',
                                        'premium' => 'Premium',
                                        'enterprise' => 'Entreprise',
                                        default => $state,
                                    }),

                                TextEntry::make('subscription_expires_at')
                                    ->label('Date d\'expiration')
                                    ->icon('heroicon-o-calendar')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),

                                TextEntry::make('qr_quota')
                                    ->label('Quota total')
                                    ->icon('heroicon-o-queue-list')
                                    ->suffix(' codes')
                                    ->numeric(),

                                TextEntry::make('qr_used')
                                    ->label('QR codes utilisés')
                                    ->icon('heroicon-o-qr-code')
                                    ->suffix(' codes')
                                    ->numeric()
                                    ->color(fn ($record) => $record->qr_used >= $record->qr_quota ? 'danger' : 'success'),
                            ]),

                        // Barre de progression du quota
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('quota_percentage')
                                    ->label('Utilisation du quota')
                                    ->state(function ($record) {
                                        if ($record->qr_quota <= 0) return 0;
                                        return round(($record->qr_used / $record->qr_quota) * 100, 2);
                                    })
                                    ->suffix('%')
                                    ->color(fn ($state) => match(true) {
                                        $state >= 90 => 'danger',
                                        $state >= 70 => 'warning',
                                        default => 'success',
                                    })
                                    ->badge()
                                    ->icon('heroicon-o-chart-bar'),
                            ]),
                    ])
                    ->collapsible(),

                // Section Statut
                Section::make('Statut et Gestion')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'disabled' => 'warning',
                                        'revoked' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'active' => 'heroicon-o-check-circle',
                                        'disabled' => 'heroicon-o-pause-circle',
                                        'revoked' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'active' => 'Active',
                                        'disabled' => 'Désactivée',
                                        'revoked' => 'Révoquée',
                                        default => $state,
                                    }),

                                TextEntry::make('created_by')
                                    ->label('Créé par')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Non renseigné'),

                                TextEntry::make('created_at')
                                    ->label('Date de création')
                                    ->icon('heroicon-o-calendar-days')
                                    ->dateTime('d/m/Y à H:i'),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notes internes')
                            ->icon('heroicon-o-document-text')
                            ->placeholder('Aucune note')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Section Métadonnées
                Section::make('Informations système')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->label('Dernière modification')
                                    ->icon('heroicon-o-clock')
                                    ->dateTime('d/m/Y à H:i')
                                    ->since(),

                                TextEntry::make('deleted_at')
                                    ->label('Date de suppression')
                                    ->icon('heroicon-o-trash')
                                    ->dateTime('d/m/Y à H:i')
                                    ->placeholder('Non supprimé')
                                    ->visible(fn ($record) => $record->deleted_at !== null),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}