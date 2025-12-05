<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Signatory;
use Filament\Tables\Table;
use App\Services\QrStampService;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Resources\SignatoryResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\SignatoryResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SignatoryResource\RelationManagers;
use App\Filament\Resources\SignatoryResource\Pages\EditSignatory;
use App\Filament\Resources\SignatoryResource\Pages\ViewSignatory;
use App\Filament\Resources\SignatoryResource\Pages\CreateSignatory;
use App\Filament\Resources\SignatoryResource\Pages\ListSignatories;

class SignatoryResource extends Resource
{
    protected static ?string $model = Signatory::class;

    protected static ?string $navigationGroup = 'Gestion des signataires';

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    protected static ?string $navigationLabel = 'Signataires';

    protected static ?string $recordTitleAttribute = 'Signataire';

    protected static ?string $modelLabel = 'Signataire';
    
    protected static ?string $pluralModelLabel = 'Signataires';

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations personnelles')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Prénom')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('last_name')
                                    ->label('Nom de famille')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Adresse email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('exemple@entreprise.com')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->autocomplete('email')
                                    ->helperText('Utilisé pour les notifications'),

                                TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->required()
                                    ->placeholder('+225 07 XX XX XX XX')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->mask('+225 99 99 99 99 99')
                                    ->helperText('Format ivoirien'),
                            ]),

                        Textarea::make('address')
                            ->label('Adresse complète')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Adresse physique du signataire'),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columns(1)
                    ->aside(),

                Section::make('Informations professionnelles')
                    ->schema([
                        Select::make('company_id')
                            ->label('Entreprise')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('position')
                                    ->label('Fonction/Poste')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('department')
                                    ->label('Département/Service')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columns(1)
                    ->aside(),

                Section::make('Signature et autorisations')
                    ->schema([
                        FileUpload::make('signature_image')
                            ->label('Image de signature')
                            ->image()
                            ->directory('signatures')
                            ->visibility('private')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Statut du signataire')
                                    ->options([
                                        'active' => 'Actif',
                                        'inactive' => 'Inactif',
                                        'suspended' => 'Suspendu',
                                        'fired' => 'Révoqué',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false),

                                Toggle::make('can_generate_qr')
                                    ->label('Génération QR Code')
                                    ->default(true),
                            ]),

                        Textarea::make('notes')
                            ->label('Notes et observations')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columns(1)
                    ->aside(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold')
                    ->getStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('position')
                    ->label('Poste')
                    ->icon('heroicon-o-briefcase')
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->weight('medium'),

                TextColumn::make('department')
                    ->label('Département')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-library')
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Téléphone copié!')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->color('secondary'),

               ActionGroup::make([
        // Generate QR (visible uniquement si PAS de QR)
                    Action::make('generate_qr')
                    ->label('Générer QR')
                    ->icon('heroicon-m-qr-code')
                    ->color('success')
                    ->visible(fn (Signatory $record) => 
                        (bool) $record->can_generate_qr && !$record->hasQrStamp()
                    )
                    ->modalHeading('Créer le QR Code')
                    ->modalDescription('Les données actuelles du signataire seront encodées dans le QR.')
                    ->form([
                        DateTimePicker::make('expires_at')
                            ->label('Date d\'expiration')
                            ->helperText('Laisser vide pour une validité d\'un an')
                            ->native(false)
                            ->minDate(now()),
                    ])
                    ->action(function (Signatory $record, array $data) {
                        try {
                            $service = app(QrStampService::class);
                            $qr = $service->createQrStamp($record, $data);

                            Notification::make()
                                ->success()
                                ->title('QR Code généré')
                                ->body('Le QR Code a été créé avec succès.')
                                ->send();

                            // Recharger la table pour afficher les nouveaux boutons
                            return redirect()->route('filament.admin.resources.signatories.index');

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Erreur')
                                ->body('Impossible de générer le QR: ' . $e->getMessage())
                                ->send();
                        }
                    }),

                    // Download QR (visible uniquement si QR existe)
                     Action::make('download_qr')
                        ->label('Télécharger QR')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('primary')
                        ->visible(fn (Signatory $record) => $record->hasQrStamp())
                        ->url(fn (Signatory $record) => route('signatories.qr.download', $record))
                        ->openUrlInNewTab(false),
                
                    // Preview QR (visible uniquement si QR existe)
                    Action::make('preview_qr')
                        ->label('Prévisualiser QR')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->visible(fn (Signatory $record) => $record->hasQrStamp())
                        ->url(fn (Signatory $record) => route('signatories.qr.preview', $record))
                        ->openUrlInNewTab(true),



        
        ])
        ->label('Actions QR')
        ->icon('heroicon-m-ellipsis-vertical')
        ->size('sm')
        ->color('gray')
        ->button(),
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignatories::route('/'),
            'create' => Pages\CreateSignatory::route('/create'),
            'edit' => Pages\EditSignatory::route('/modifier/{record:id}'),
            'view' => Pages\ViewSignatory::route('/details/{record:id}'),
            
        ];
    }
}

