<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\QrStamp;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\QrStampResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\QrStampResource\RelationManagers;
use App\Filament\Resources\QrStampResource\Pages\EditQrStamp;
use App\Filament\Resources\QrStampResource\Pages\ListQrStamps;
use App\Filament\Resources\QrStampResource\Pages\CreateQrStamp;

class QrStampResource extends Resource
{
    protected static ?string $model = QrStamp::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $navigationGroup = 'Gestion des qr codes';

    protected static ?string $navigationLabel = 'Qr codes';

    protected static ?string $recordTitleAttribute = 'unique_code';

    protected static ?string $modelLabel = 'Qr codes';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(QrStamp::query()->with(['signatory', 'company']))
            ->poll('30s')
            ->striped()
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

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'revoked',
                        'warning' => 'expired',
                        'secondary' => 'inactive',
                        'warning' => 'invalid',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-x-circle' => 'revoked',
                        'heroicon-o-clock' => 'expired',
                        'heroicon-o-ellipsis-horizontal-circle' => 'inactive',
                        'heroicon-o-clock' => 'invalid',
                    ]),

                TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                
                TextColumn::make('signatory.full_name')
                    ->label('Signataire')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('verification_count')
                    ->label('Vérifications')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('issued_at')
                    ->label('Émis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : 'success')
                    ->toggleable(),

                 TextColumn::make('last_verified_at')
                    ->label('Dernière vérification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('revoked_at')
                    ->label('Révoqué le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('revocation_reason')
                    ->label('Raison de révocation')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'revoked' => 'Révoqué',
                        'expired' => 'Expiré',
                        'inactive' => 'Inactif',
                        'invalid' => 'Invalide',
                    ])
                    ->multiple(),

                SelectFilter::make('company_id')
                    ->label('Entreprise')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('signatory')
                    ->label('Filtrer par signataire')
                    ->relationship('signatory', 'first_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les signataires'),

                Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn (Builder $query) => $query->where('expires_at', '<', now())),

                Filter::make('active')
                    ->label('Actifs seulement')
                    ->query(fn (Builder $query) => $query->active()),

                TrashedFilter::make()
                    ->label('Supprimés'),
            ])
            ->actions([
               
            ])
            ->bulkActions([
              Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrStamps::route('/'),
            'create' => Pages\CreateQrStamp::route('/create'),
            'edit' => Pages\EditQrStamp::route('/{record}/edit'),
        ];
    }
}
