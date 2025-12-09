<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\QrStamp;
use App\Models\Signatory;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use App\Filament\Resources\SignatoryResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class Qrcodes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Gestion des signataires';

    protected static ?string $navigationLabel = 'Codes QR générés';

    protected static ?string $title = 'Gestion des Codes QR';

    protected static string $resource = SignatoryResource::class;

    protected static string $view = 'filament.resources.signatory-resource.pages.qrcodes';

    public function table(Table $table): Table
    {
        return $table
            ->query(QrStamp::query()->with(['signatory', 'company']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('N°')
                    ->rowIndex()
                    ->sortable(false)
                    ->weight('bold')
                    ->alignment('center'),
                    

                ImageColumn::make('qr_image_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->width(80)
                    ->height(80),

                TextColumn::make('signatory.full_name')
                    ->label('Signataire')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A'),

                TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'revoked' => 'danger',
                        'expired' => 'warning',
                        'invalid' => 'primary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'revoked' => 'Révoqué',
                        'expired' => 'Expiré',
                        'invalid' => 'Invalide',
                        default => ucfirst($state),
                    }),

                TextColumn::make('issued_at')
                    ->label('Date d\'émission')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('expires_at')
                    ->label('Date d\'expiration')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn (?string $state): ?string => match (true) {
                        $state && now()->isAfter($state) => 'danger',
                        $state && now()->diffInDays($state) <= 7 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-calendar-days')
                    ->formatStateUsing(fn (?string $state) => $state ?? 'Jamais'),

                TextColumn::make('verification_count')
                    ->label('Vérifications')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-check-badge')
                    ->alignment('center'),

                TextColumn::make('last_verified_at')
                    ->label('Dernière vérification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->formatStateUsing(fn (?string $state) => $state ?? 'Jamais vérifiée'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrer par statut')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'revoked' => 'Révoqué',
                        'expired' => 'Expiré',
                        'invalid' => 'Invalide',
                    ])
                    ->placeholder('Tous les statuts'),

                SelectFilter::make('company')
                    ->label('Filtrer par entreprise')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('Toutes les entreprises'),

                SelectFilter::make('signatory')
                    ->label('Filtrer par signataire')
                    ->relationship('signatory', 'first_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les signataires'),

                Filter::make('expires_at')
                    ->label('En expiration prochainement')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ),

                Filter::make('expired')
                    ->label('Codes expirés')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('preview')
                    ->label('Prévisualiser')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn (QrStamp $record) => route('qr.preview', $record))
                    ->openUrlInNewTab(false),

                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (QrStamp $record) => route('signatories.qr.download', $record->signatory))
                    ->openUrlInNewTab(false),

                Action::make('view_signatory')
                    ->label('Voir signataire')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('warning')
                    ->url(fn (QrStamp $record) => 
                        route('filament.admin.resources.signatories.edit', $record->signatory)
                    )
                    ->openUrlInNewTab(false),

                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (QrStamp $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Révoquer le QR Code')
                    ->modalDescription('Cette action est irréversible. Le QR Code ne sera plus valide.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('revocation_reason')
                            ->label('Raison de la révocation')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (QrStamp $record, array $data) {
                        $record->update([
                            'status' => 'revoked',
                            'revoked_at' => now(),
                            'revocation_reason' => $data['revocation_reason'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('QR Code révoqué')
                            ->body('Le QR Code a été révoqué avec succès.')
                            ->send();

                        return redirect()->route('filament.admin.resources.signatories.qrcodes');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
