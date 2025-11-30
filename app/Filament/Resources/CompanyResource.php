<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use App\Filament\Resources\CompanyResource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\CompanyResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;

class CompanyResource extends BaseResource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Entreprises';

    protected static ?string $recordTitleAttribute = 'Entreprise';

    protected static ?string $modelLabel = 'Entreprise';
    
    protected static ?string $pluralModelLabel = 'Entreprises';

    protected static bool $shouldSkipAuthorization = true;




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
             Section::make("Informations de l'entreprise")
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Select::make('sector')
                        ->label('Secteur d\'activité')
                        ->options([
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
                        ])
                        ->searchable()
                        ->preload()
                        ->placeholder('Choisissez un secteur')
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label('Nom commercial')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Entrez le nom commercial')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set) {
                            if (empty($get('legal_name'))) {
                                $set('legal_name', $state);
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('legal_name')
                        ->label('Raison sociale')
                        ->maxLength(255)
                        ->placeholder('Raison sociale officielle')
                        ->helperText('Sera automatiquement rempli avec le nom commercial si laissé vide'),

                    TextInput::make('registration_number')
                        ->label('Numéro RCCM/NIU')
                        ->maxLength(255)
                        ->placeholder('Ex: CI-ABJ-2024-1234 ou RC-####-####'),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make("Coordonnées de l'entreprise")
                ->icon('heroicon-o-phone-arrow-up-right')
                ->schema([
                    TextInput::make('email')
                        ->label('Email professionnel')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->placeholder('contact@entreprise.com')
                        ->prefixIcon('heroicon-o-envelope-open')
                        ->columnSpanFull(),

                    TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->placeholder('+225 07 00 00 00 00')
                        ->prefixIcon('heroicon-o-phone'),

                    TextInput::make('website')
                        ->label('Site web')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('www.entreprise.com')
                        ->prefix('https://')
                        ->prefixIcon('heroicon-o-globe-alt'),

                    TextInput::make('address')
                        ->label('Adresse')
                        ->maxLength(255)
                        ->placeholder('Rue, Avenue...')
                        ->prefixIcon('heroicon-o-home')
                        ->columnSpanFull(),

                    TextInput::make('city')
                        ->label('Ville')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Abidjan')
                        ->prefixIcon('heroicon-o-map-pin'),
                        
                        Select::make('country')
                                ->label('Pays')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(self::getCountries())
                                ->getSearchResultsUsing(fn (string $search) => 
                                    collect(self::getCountries())
                                        ->filter(fn ($name) => str_contains(strtolower($name), strtolower($search)))
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn ($value) => self::getCountries()[$value] ?? $value)
                                ->dehydrateStateUsing(function ($state) {
                                    $countries = self::getCountries();
                                    return $countries[$state] ?? $state;
                                })
                                ->placeholder('Sélectionnez un pays')
                                ->prefixIcon('heroicon-o-globe-europe-africa')
                                ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Logo de l\'entreprise')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                            '16:9',
                        ])
                        ->maxSize(2048)
                        ->directory('company-logos')
                        ->visibility('public')
                        ->nullable()
                        ->helperText('Format: PNG, JPG. Taille max: 2 Mo. Recommandé: 200x200px')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make("Configuration de l'abonnement")
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Select::make('subscription_type')
                        ->label("Type d'abonnement")
                        ->required()
                        ->options([
                            'free' => 'Gratuit',
                            'basic' => 'Basique',
                            'premium' => 'Premium',
                            'enterprise' => 'Entreprise',
                        ])
                        ->default('free')
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            $quota = match($state) {
                                'free' => 10,
                                'basic' => 50,
                                'premium' => 500,
                                'enterprise' => 5000,
                                default => 10,
                            };
                            $set('qr_quota', $quota);
                            
                            // Définir la date d'expiration selon le type
                            $expirationDate = match($state) {
                                'free' => now()->addDays(30),
                                'basic' => now()->addMonths(3),
                                'premium' => now()->addYear(),
                                'enterprise' => now()->addYears(2),
                                default => now()->addDays(30),
                            };
                            $set('subscription_expires_at', $expirationDate);
                        })
                        ->helperText('Le quota de QR codes sera automatiquement défini'),

                    TextInput::make('qr_quota')
                        ->label('Quota de QR codes')
                        ->numeric()
                        ->required()
                        ->default(10)
                        ->minValue(0)
                        ->suffix('codes')
                        ->helperText('Nombre total de QR codes disponibles'),

                    TextInput::make('qr_used')
                        ->label('QR codes utilisés')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('codes')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Nombre de QR codes déjà générés'),

                    DatePicker::make('subscription_expires_at')
                        ->label('Date d\'expiration')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addYear())
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->helperText('Date de fin de l\'abonnement'),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Statut et options')
                ->icon('heroicon-o-cog-6-tooth')
                ->iconColor('gray')
                ->schema([
                    Select::make('status')
                        ->label('Statut de l\'entreprise')
                        ->options([
                            'active' => 'Active',
                            'disabled' => 'Désactivée',
                            'revoked' => 'Révoquée',
                        ])
                        ->required()
                        ->default('active')
                        ->native(false)
                        ->helperText('Une entreprise désactivée ne peut plus générer de QR codes')
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Notes internes')
                        ->maxLength(1000)
                        ->rows(3)
                        ->placeholder('Informations supplémentaires, remarques, historique...')
                        ->helperText('Ces notes sont internes et ne sont pas visibles par l\'entreprise.')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

                 Hidden::make('created_by')
                    ->default(auth()->user()->name),

                Hidden::make('qr_used')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('name')->label('Nom')
                ->searchable()
                ->url(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
                ->sortable()
                ->openUrlInNewTab(false),
                TextColumn::make('legal_name')->label('Raison Sociale')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('phone')->label('Tel')->searchable()->sortable(),
                TextColumn::make('country')->label('Pays')->searchable()->sortable()

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'view' => ViewCompany::route('/details/{record:id}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getCountries(): array
    {
        return [
            // Afrique
            'DZ' => 'Algérie',
            'AO' => 'Angola',
            'BJ' => 'Bénin',
            'BW' => 'Botswana',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CM' => 'Cameroun',
            'CV' => 'Cap-Vert',
            'CF' => 'République centrafricaine',
            'TD' => 'Tchad',
            'KM' => 'Comores',
            'CG' => 'Congo',
            'CD' => 'République démocratique du Congo',
            'CI' => 'Côte d\'Ivoire',
            'DJ' => 'Djibouti',
            'EG' => 'Égypte',
            'GQ' => 'Guinée équatoriale',
            'ER' => 'Érythrée',
            'SZ' => 'Eswatini',
            'ET' => 'Éthiopie',
            'GA' => 'Gabon',
            'GM' => 'Gambie',
            'GH' => 'Ghana',
            'GN' => 'Guinée',
            'GW' => 'Guinée-Bissau',
            'KE' => 'Kenya',
            'LS' => 'Lesotho',
            'LR' => 'Libéria',
            'LY' => 'Libye',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'ML' => 'Mali',
            'MR' => 'Mauritanie',
            'MU' => 'Maurice',
            'MA' => 'Maroc',
            'MZ' => 'Mozambique',
            'NA' => 'Namibie',
            'NE' => 'Niger',
            'NG' => 'Nigéria',
            'RW' => 'Rwanda',
            'ST' => 'Sao Tomé-et-Principe',
            'SN' => 'Sénégal',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SO' => 'Somalie',
            'ZA' => 'Afrique du Sud',
            'SS' => 'Soudan du Sud',
            'SD' => 'Soudan',
            'TZ' => 'Tanzanie',
            'TG' => 'Togo',
            'TN' => 'Tunisie',
            'UG' => 'Ouganda',
            'ZM' => 'Zambie',
            'ZW' => 'Zimbabwe',

            // Europe
            'FR' => 'France',
            'BE' => 'Belgique',
            'CH' => 'Suisse',
            'LU' => 'Luxembourg',
            'DE' => 'Allemagne',
            'ES' => 'Espagne',
            'IT' => 'Italie',
            'GB' => 'Royaume-Uni',
            'NL' => 'Pays-Bas',
            'PT' => 'Portugal',

            // Amériques
            'US' => 'États-Unis',
            'CA' => 'Canada',
            'BR' => 'Brésil',

            // Asie
            'CN' => 'Chine',
            'JP' => 'Japon',
            'IN' => 'Inde',

            // Autres
            'AU' => 'Australie',
            'NZ' => 'Nouvelle-Zélande',
        ];
    }
}
