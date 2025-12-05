<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Signatory;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class SignatariesRelationManager extends RelationManager
{
    protected static string $relationship = 'signatories';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $title = 'Signataires';

    protected static ?string $modelLabel = 'Signataire';

    protected static ?string $pluralModelLabel = 'Signataires';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('position')
                            ->label('Poste')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('department')
                            ->label('Département')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Coordonnées')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\FileUpload::make('signature_image')
                            ->label('Image de signature')
                            ->image()
                            ->directory('signatures')
                            ->maxSize(5120)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active' => 'Actif',
                                'inactive' => 'Inactif',
                                'suspended' => 'Suspendu',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Checkbox::make('can_generate_qr')
                            ->label('Peut générer des QR codes'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
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
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'suspended',
                    ])
                    ->sortable(),

                BadgeColumn::make('can_generate_qr')
                    ->label('Génération QR')
                    ->getStateUsing(fn($record) => $record->can_generate_qr ? 'Oui' : 'Non')
                    ->colors([
                        'success' => 'true',
                        'danger' => 'false',
                    ]),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'suspended' => 'Suspendu',
                    ]),

                Tables\Filters\TernaryFilter::make('can_generate_qr')
                    ->label('Peut générer des QR codes'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Afficher les supprimés'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un signataire')
                    ->icon('heroicon-m-plus'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Modifier')
                    ->icon('heroicon-m-pencil'),

                DeleteAction::make()
                    ->label('Supprimer')
                    ->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer les sélectionnés'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
