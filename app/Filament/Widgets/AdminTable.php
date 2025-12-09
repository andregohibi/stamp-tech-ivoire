<?php

namespace App\Filament\Widgets;

use App\Models\Signatory;
use App\Models\Company;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;


class AdminTable extends BaseWidget
{
   

    protected static ?string $heading = 'Signataires avec QR codes';

    protected int | string | array $columnSpan = 'full';



    public function table(Table $table): Table
    {
        return $table
            ->query(Signatory::query())
            ->columns([
                // Informations du signataire
                TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->getStateUsing(fn (Signatory $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Poste')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department')
                    ->label('Département')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->sortable(),

                // Relation Company
                TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable(),

                // Statut du signataire
                BadgeColumn::make('status')
                    ->label('Statut signataire')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'fired',
                        'warning' => 'suspended',
                        'secondary' => 'inactive',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-x-circle' => 'fired',
                        'heroicon-m-exclamation-circle' => 'suspended',
                        'heroicon-m-minus-circle' => 'inactive',
                    ])
                    ->sortable(),

                // Relation QR Stamp
                TextColumn::make('qrStamp.unique_code')
                    ->label('Code QR'),

                BadgeColumn::make('qrStamp.status')
                    ->label('Statut QR')
                    ->colors([
                        'success' => 'active',
                        'secondary' => 'inactive',
                        'danger' => 'revoked',
                        'warning' => 'expired',
                        'gray' => 'invalid',

                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-minus-circle' => 'inactive',
                        'heroicon-m-x-circle' => 'revoked',
                        'heroicon-m-clock' => 'expired',
                        'heroicon-m-question-mark-circle' => 'invalid',
                    ])
                    ->sortable(),

                TextColumn::make('qrStamp.issued_at')
                    ->label('QR généré le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('qrStamp.expires_at')
                    ->label('QR expire le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                // Attributs du signataire
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // === FilterForm-style filters ===
                SelectFilter::make('company')
                    ->label('Entreprise')
                    ->relationship('company', 'name')
                    ->preload()
                    ->multiple()
                    ->columnSpan(2),

                SelectFilter::make('status')
                    ->label('Statut du signataire')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'suspended' => 'Suspendu',
                        'fired' => 'Licencié',
                    ]) 
                    ->multiple()
                    ->columnSpan(2),

                Filter::make('has_active_qr')
                    ->label('Avec QR actif')
                    ->toggle()
                    ->columnSpanFull()
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('qrStamp', function (Builder $q) {
                            $q->where('status', 'active')
                                ->where(function ($q2) {
                                    $q2->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                                });
                        });
                    }),

                Filter::make('no_qr')
                    ->label('Sans QR code')
                    ->toggle()
                    ->columnSpanFull()
                    ->query(function (Builder $query): Builder {
                        return $query->whereDoesntHave('qrStamp');
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtrer'),
            )
            ->defaultSort('created_at', 'desc')
            ->paginated();
    }
}
