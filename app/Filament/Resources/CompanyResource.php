<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\Indicator;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
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
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\RelationManagers\SignatariesRelationManager;

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
            ->searchPlaceholder('Rechercher une entreprise')
            ->columns([
               TextColumn::make('name')->label('Nom')
                ->searchable()
                ->url(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
                ->sortable()
                ->openUrlInNewTab(false),
                TextColumn::make('legal_name')->label('Raison Sociale')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('phone')->label('Tel')->searchable()->sortable(),
                TextColumn::make('country')->label('Pays')->searchable()->sortable(),
                TextColumn::make('sector')->label('Secteur')->badge()->searchable(),
                TextColumn::make('status')
                ->label('Statut')
                ->badge()
                ->searchable()
                ->sortable()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'active' => 'Active',
                    'disabled' => 'Désactivée',
                    'revoked' => 'Révoquée',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'disabled' => 'warning',
                    'revoked' => 'danger',
                    default => 'gray',
                }),

            ])
            ->filters([
                SelectFilter::make('trashed')
                    ->label('État')
                    ->options([
                        'without' => 'Actifs',
                        'with' => 'Tous',
                        'only' => 'Supprimés',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'with' => $query->withTrashed(),
                            'only' => $query->onlyTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    })
                    ->columnSpanFull(),

                // Filtre par secteur d'activité - occupe toute la largeur
                SelectFilter::make('sector')
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
                    ->multiple()
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        return $query->whereIn('sector', $data['values']);
                    }),

                // Filtre par pays - occupe toute la largeur avec tous les pays
                SelectFilter::make('country')
                    ->label('Pays')
                    ->options(self::getCountries())
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        return $query->whereIn('country', $data['values']);
                    }),

                // Filtre par statut - occupe toute la largeur
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Désactivée',
                        'revoked' => 'Révoquée',
                    ])
                    ->multiple()
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        return $query->whereIn('status', $data['values']);
                    }),

                // Filtre par type d'abonnement - occupe toute la largeur
                SelectFilter::make('subscription_type')
                    ->label('Type d\'abonnement')
                    ->options([
                        'free' => 'Gratuit',
                        'basic' => 'Basique',
                        'premium' => 'Premium',
                        'enterprise' => 'Entreprise',
                    ])
                    ->multiple()
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        return $query->whereIn('subscription_type', $data['values']);
                    }),
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
             SignatariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/creer-une-entreprise'),
            'edit' => Pages\EditCompany::route('/modifier-entreprise/{record:id}'),
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

    /*  public static function getCountries(): array
    {
        // Liste complète des pays (ISO 3166-1 alpha-2)
        return [
            'AF' => 'Afghanistan',
            'ZA' => 'Afrique du Sud',
            'AL' => 'Albanie',
            'DZ' => 'Algérie',
            'DE' => 'Allemagne',
            'AD' => 'Andorre',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctique',
            'AG' => 'Antigua-et-Barbuda',
            'SA' => 'Arabie saoudite',
            'AR' => 'Argentine',
            'AM' => 'Arménie',
            'AW' => 'Aruba',
            'AU' => 'Australie',
            'AT' => 'Autriche',
            'AZ' => 'Azerbaïdjan',
            'BS' => 'Bahamas',
            'BH' => 'Bahreïn',
            'BD' => 'Bangladesh',
            'BB' => 'Barbade',
            'BY' => 'Bélarus',
            'BE' => 'Belgique',
            'BZ' => 'Belize',
            'BJ' => 'Bénin',
            'BM' => 'Bermudes',
            'BT' => 'Bhoutan',
            'BO' => 'Bolivie',
            'BA' => 'Bosnie-Herzégovine',
            'BW' => 'Botswana',
            'BR' => 'Brésil',
            'BN' => 'Brunei',
            'BG' => 'Bulgarie',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodge',
            'CM' => 'Cameroun',
            'CA' => 'Canada',
            'CV' => 'Cap-Vert',
            'CL' => 'Chili',
            'CN' => 'Chine',
            'CY' => 'Chypre',
            'CO' => 'Colombie',
            'KM' => 'Comores',
            'CG' => 'Congo-Brazzaville',
            'CD' => 'Congo-Kinshasa',
            'KP' => 'Corée du Nord',
            'KR' => 'Corée du Sud',
            'CR' => 'Costa Rica',
            'CI' => 'Côte d\'Ivoire',
            'HR' => 'Croatie',
            'CU' => 'Cuba',
            'CW' => 'Curaçao',
            'DK' => 'Danemark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominique',
            'EG' => 'Égypte',
            'AE' => 'Émirats arabes unis',
            'EC' => 'Équateur',
            'ER' => 'Érythrée',
            'ES' => 'Espagne',
            'EE' => 'Estonie',
            'SZ' => 'Eswatini',
            'VA' => 'État de la Cité du Vatican',
            'FM' => 'États fédérés de Micronésie',
            'US' => 'États-Unis',
            'ET' => 'Éthiopie',
            'FJ' => 'Fidji',
            'FI' => 'Finlande',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GM' => 'Gambie',
            'GE' => 'Géorgie',
            'GS' => 'Géorgie du Sud-et-les Îles Sandwich du Sud',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Grèce',
            'GD' => 'Grenade',
            'GL' => 'Groenland',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernesey',
            'GN' => 'Guinée',
            'GQ' => 'Guinée équatoriale',
            'GW' => 'Guinée-Bissau',
            'GY' => 'Guyana',
            'GF' => 'Guyane française',
            'HT' => 'Haïti',
            'HN' => 'Honduras',
            'HU' => 'Hongrie',
            'BV' => 'Île Bouvet',
            'CX' => 'Île Christmas',
            'IM' => 'Île de Man',
            'NF' => 'Île Norfolk',
            'AX' => 'Îles Åland',
            'KY' => 'Îles Caïmans',
            'CC' => 'Îles Cocos',
            'CK' => 'Îles Cook',
            'FO' => 'Îles Féroé',
            'HM' => 'Îles Heard-et-MacDonald',
            'FK' => 'Îles Malouines',
            'MP' => 'Îles Mariannes du Nord',
            'MH' => 'Îles Marshall',
            'UM' => 'Îles mineures éloignées des États-Unis',
            'PN' => 'Îles Pitcairn',
            'SB' => 'Îles Salomon',
            'TC' => 'Îles Turques-et-Caïques',
            'VG' => 'Îles Vierges britanniques',
            'VI' => 'Îles Vierges des États-Unis',
            'IN' => 'Inde',
            'ID' => 'Indonésie',
            'IR' => 'Iran',
            'IQ' => 'Irak',
            'IE' => 'Irlande',
            'IS' => 'Islande',
            'IL' => 'Israël',
            'IT' => 'Italie',
            'JM' => 'Jamaïque',
            'JP' => 'Japon',
            'JE' => 'Jersey',
            'JO' => 'Jordanie',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KG' => 'Kirghizistan',
            'KI' => 'Kiribati',
            'KW' => 'Koweït',
            'RE' => 'La Réunion',
            'LA' => 'Laos',
            'LS' => 'Lesotho',
            'LV' => 'Lettonie',
            'LB' => 'Liban',
            'LR' => 'Libéria',
            'LY' => 'Libye',
            'LI' => 'Liechtenstein',
            'LT' => 'Lituanie',
            'LU' => 'Luxembourg',
            'MK' => 'Macédoine du Nord',
            'MG' => 'Madagascar',
            'MY' => 'Malaisie',
            'MW' => 'Malawi',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malte',
            'MA' => 'Maroc',
            'MQ' => 'Martinique',
            'MU' => 'Maurice',
            'MR' => 'Mauritanie',
            'YT' => 'Mayotte',
            'MX' => 'Mexique',
            'MD' => 'Moldavie',
            'MC' => 'Monaco',
            'MN' => 'Mongolie',
            'ME' => 'Monténégro',
            'MS' => 'Montserrat',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar (Birmanie)',
            'NA' => 'Namibie',
            'NR' => 'Nauru',
            'NP' => 'Népal',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigéria',
            'NU' => 'Niue',
            'NO' => 'Norvège',
            'NC' => 'Nouvelle-Calédonie',
            'NZ' => 'Nouvelle-Zélande',
            'OM' => 'Oman',
            'UG' => 'Ouganda',
            'UZ' => 'Ouzbékistan',
            'PK' => 'Pakistan',
            'PW' => 'Palaos',
            'PA' => 'Panama',
            'PG' => 'Papouasie-Nouvelle-Guinée',
            'PY' => 'Paraguay',
            'NL' => 'Pays-Bas',
            'BQ' => 'Pays-Bas caribéens',
            'PE' => 'Pérou',
            'PH' => 'Philippines',
            'PL' => 'Pologne',
            'PF' => 'Polynésie française',
            'PR' => 'Porto Rico',
            'PT' => 'Portugal',
            'QA' => 'Qatar',
            'HK' => 'R.A.S. chinoise de Hong Kong',
            'MO' => 'R.A.S. chinoise de Macao',
            'CF' => 'République centrafricaine',
            'DO' => 'République dominicaine',
            'CZ' => 'République tchèque',
            'RO' => 'Roumanie',
            'GB' => 'Royaume-Uni',
            'RU' => 'Russie',
            'RW' => 'Rwanda',
            'EH' => 'Sahara occidental',
            'BL' => 'Saint-Barthélemy',
            'KN' => 'Saint-Christophe-et-Niévès',
            'SM' => 'Saint-Marin',
            'MF' => 'Saint-Martin',
            'SX' => 'Saint-Martin',
            'PM' => 'Saint-Pierre-et-Miquelon',
            'VC' => 'Saint-Vincent-et-les-Grenadines',
            'SH' => 'Sainte-Hélène',
            'LC' => 'Sainte-Lucie',
            'SV' => 'Salvador',
            'WS' => 'Samoa',
            'AS' => 'Samoa américaines',
            'ST' => 'Sao Tomé-et-Principe',
            'SN' => 'Sénégal',
            'RS' => 'Serbie',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapour',
            'SK' => 'Slovaquie',
            'SI' => 'Slovénie',
            'SO' => 'Somalie',
            'SD' => 'Soudan',
            'SS' => 'Soudan du Sud',
            'LK' => 'Sri Lanka',
            'SE' => 'Suède',
            'CH' => 'Suisse',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard et Jan Mayen',
            'SY' => 'Syrie',
            'TJ' => 'Tadjikistan',
            'TW' => 'Taïwan',
            'TZ' => 'Tanzanie',
            'TD' => 'Tchad',
            'TF' => 'Terres australes françaises',
            'IO' => 'Territoire britannique de l\'océan Indien',
            'PS' => 'Territoires palestiniens',
            'TH' => 'Thaïlande',
            'TL' => 'Timor oriental',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinité-et-Tobago',
            'TN' => 'Tunisie',
            'TM' => 'Turkménistan',
            'TR' => 'Turquie',
            'TV' => 'Tuvalu',
            'UA' => 'Ukraine',
            'UY' => 'Uruguay',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'WF' => 'Wallis-et-Futuna',
            'YE' => 'Yémen',
            'ZM' => 'Zambie',
            'ZW' => 'Zimbabwe',
        ];
    } */


    public static function getCountries(): array
    {
    // Complete list of countries (ISO 3166-1 alpha-2)
        return [
            'AF' => 'Afghanistan',
            'ZA' => 'South Africa',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'DE' => 'Germany',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'SA' => 'Saudi Arabia',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'CL' => 'Chile',
            'CN' => 'China',
            'CY' => 'Cyprus',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo-Brazzaville',
            'CD' => 'Congo-Kinshasa',
            'KP' => 'North Korea',
            'KR' => 'South Korea',
            'CR' => 'Costa Rica',
            'CI' => 'Ivory Coast',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CW' => 'Curaçao',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'EG' => 'Egypt',
            'AE' => 'United Arab Emirates',
            'EC' => 'Ecuador',
            'ER' => 'Eritrea',
            'ES' => 'Spain',
            'EE' => 'Estonia',
            'SZ' => 'Eswatini',
            'VA' => 'Vatican City State',
            'FM' => 'Federated States of Micronesia',
            'US' => 'United States',
            'ET' => 'Ethiopia',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GD' => 'Grenada',
            'GL' => 'Greenland',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GQ' => 'Equatorial Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'GF' => 'French Guiana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HU' => 'Hungary',
            'BV' => 'Bouvet Island',
            'CX' => 'Christmas Island',
            'IM' => 'Isle of Man',
            'NF' => 'Norfolk Island',
            'AX' => 'Åland Islands',
            'KY' => 'Cayman Islands',
            'CC' => 'Cocos (Keeling) Islands',
            'CK' => 'Cook Islands',
            'FO' => 'Faroe Islands',
            'HM' => 'Heard Island and McDonald Islands',
            'FK' => 'Falkland Islands',
            'MP' => 'Northern Mariana Islands',
            'MH' => 'Marshall Islands',
            'UM' => 'United States Minor Outlying Islands',
            'PN' => 'Pitcairn Islands',
            'SB' => 'Solomon Islands',
            'TC' => 'Turks and Caicos Islands',
            'VG' => 'British Virgin Islands',
            'VI' => 'United States Virgin Islands',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IS' => 'Iceland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KG' => 'Kyrgyzstan',
            'KI' => 'Kiribati',
            'KW' => 'Kuwait',
            'RE' => 'Réunion',
            'LA' => 'Laos',
            'LS' => 'Lesotho',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MK' => 'North Macedonia',
            'MG' => 'Madagascar',
            'MY' => 'Malaysia',
            'MW' => 'Malawi',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MA' => 'Morocco',
            'MQ' => 'Martinique',
            'MU' => 'Mauritius',
            'MR' => 'Mauritania',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar (Burma)',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NO' => 'Norway',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'OM' => 'Oman',
            'UG' => 'Uganda',
            'UZ' => 'Uzbekistan',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'NL' => 'Netherlands',
            'BQ' => 'Caribbean Netherlands',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PF' => 'French Polynesia',
            'PR' => 'Puerto Rico',
            'PT' => 'Portugal',
            'QA' => 'Qatar',
            'HK' => 'Hong Kong SAR China',
            'MO' => 'Macao SAR China',
            'CF' => 'Central African Republic',
            'DO' => 'Dominican Republic',
            'CZ' => 'Czech Republic',
            'RO' => 'Romania',
            'GB' => 'United Kingdom',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'EH' => 'Western Sahara',
            'BL' => 'Saint Barthélemy',
            'KN' => 'Saint Kitts and Nevis',
            'SM' => 'San Marino',
            'MF' => 'Saint Martin',
            'SX' => 'Sint Maarten',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'SH' => 'Saint Helena',
            'LC' => 'Saint Lucia',
            'SV' => 'El Salvador',
            'WS' => 'Samoa',
            'AS' => 'American Samoa',
            'ST' => 'São Tomé and Príncipe',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SO' => 'Somalia',
            'SD' => 'Sudan',
            'SS' => 'South Sudan',
            'LK' => 'Sri Lanka',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen',
            'SY' => 'Syria',
            'TJ' => 'Tajikistan',
            'TW' => 'Taiwan',
            'TZ' => 'Tanzania',
            'TD' => 'Chad',
            'TF' => 'French Southern Territories',
            'IO' => 'British Indian Ocean Territory',
            'PS' => 'Palestinian Territories',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TM' => 'Turkmenistan',
            'TR' => 'Turkey',
            'TV' => 'Tuvalu',
            'UA' => 'Ukraine',
            'UY' => 'Uruguay',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'WF' => 'Wallis and Futuna',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];
    }
}
