<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\SignatoryResource;
use Filament\Infolists\Components\ImageEntry;

class ViewSignatory extends ViewRecord
{
    protected static string $resource = SignatoryResource::class;


    public function getBreadcrumb(): string
    {
        return "{$this->record->first_name} {$this->record->last_name}";
    }

    public function getTitle(): string
    {
        return $this->record->first_name . ' ' . $this->record->last_name;
    }

   public function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
           Tabs::make('Tabs')
           
               ->columnSpanFull() 
                ->tabs([
                    Tab::make('Informations entreprise')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                           Grid::make(3)
                                ->schema([
                                     TextEntry::make('company.name')
                                            ->label("Nom de l'entreprise"),
                                    
                                    TextEntry::make('company.legal_name')
                                            ->label("Nom juridique"),

                                    TextEntry::make('company.address')
                                            ->label("Adresse"),

                                    TextEntry::make('company.city')
                                            ->label("Ville"),

                                    TextEntry::make('company.country')
                                            ->label("Pays"),

                                    TextEntry::make('company.email')
                                            ->label("Email"),

                                    TextEntry::make('company.phone')
                                            ->label("Tel"),

                                    TextEntry::make('company.registration_number')
                                            ->label("Numéro de registration")
                                            ->color('primary'),

                                    TextEntry::make('company.website')
                                            ->label('Site web')
                                            ->copyable()
                                            ->copyMessage('Copié!')
                                            ->copyMessageDuration(1500),

                                    TextEntry::make('company.sector')
                                            ->label("Secteur d'activité")
                                            ->badge()
                                            ->color('success'), 
                                ])
                            
                            ]),

                    Tab::make('Informations personnelles')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('first_name')
                                            ->label("Nom"),

                                    TextEntry::make('last_name')
                                            ->label("Prenom"), 

                                    TextEntry::make('email')
                                            ->label("Email"),

                                    TextEntry::make('phone')
                                            ->label("Tel"),

                                    TextEntry::make('address')
                                            ->label("Adresse"),
                                        

                                    TextEntry::make('position')
                                            ->label("Position")
                                            ->color('success'),

                                    TextEntry::make('department')
                                            ->label("Departement")
                                            ->color('secondary'),

                                    TextEntry::make('status')
                                            ->label("Statut")
                                            ->badge()
                                            ->color('success'),
                                           

                                     IconEntry::make('can_generate_qr')
                                            ->label("Qr Code statut")
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-badge')
                                            ->falseIcon('heroicon-o-x-mark'),
                                        
                                      ImageEntry::make('signature_image')
                                            ->label('Signature')
                                            ->defaultImageUrl(asset('images/default-signature.png'))
                                            ->width(200)
                                            ->height(70),
                                ])
                        ]),


                        Tab::make('Qr Code generé')
                                ->icon('heroicon-o-qr-code')
                                ->schema([
                                        Grid::make(3)
                                        ->schema([
                                        
                                        TextEntry::make('qrStamp.unique_code')
                                                ->label('ID Code QR'),
                                                
                                             
                                        ImageEntry::make('qrStamp.qr_image_path')
                                                ->label('QR Code')
                                                ->disk('public')
                                                ->size(250)
                                                ->hidden(fn ($record) => !$record->qrStamp)
                                                ->visibility('public')
                                                ->columnSpan(1), 
                                        TextEntry::make('qrStamp.status')
                                                ->label('Statut')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'inactive' => 'gray',
                                                        'revoked' => 'danger',
                                                        'expired' => 'warning',
                                                        default => 'secondary',
                                                }),
                                        
                                        TextEntry::make('qrStamp.issued_at')
                                                ->label('Date de génération')
                                                ->dateTime('d/m/Y H:i'),
                                                
                                        TextEntry::make('qrStamp.expires_at')
                                                ->label('Date d\'expiration')
                                                ->dateTime('d/m/Y H:i')
                                                ->placeholder('Aucune expiration'),

                                        TextEntry::make('qrStamp.verification_count')
                                                ->label('Nombre de vérifications')
                                                ->default(0)
                                                ->badge()
                                                ->color('info'),
                                                
                                        TextEntry::make('qrStamp.created_by')
                                                ->label('Créé par')
                                                ->placeholder('Non spécifié'),

                                         Section::make()
                                                ->schema([
                                                               
                                                        TextEntry::make('qrStamp.metadata.notes')
                                                                ->label('Notes')
                                                                ->inlineLabel()
                                                                ->formatStateUsing(fn ($state) => $state ?? 'Aucune note'),
                                                                
                                                        TextEntry::make('qrStamp.metadata.generated_by_user')
                                                                ->label('Généré par')
                                                                ->inlineLabel()
                                                                ->formatStateUsing(fn ($state) => $state ?? 'Non spécifié'),
                                                                
                                                        TextEntry::make('qrStamp.metadata.ip_address')
                                                                ->label('Adresse IP')
                                                                ->inlineLabel()
                                                                ->formatStateUsing(fn ($state) => $state ?? 'Non spécifiée'),
                                                                
                                                                
                                                ])
                                                ->columnSpanFull()


                                        ])
                                        
                                ]),

                        Tab::make('Abonnements')
                            ->icon('heroicon-o-bars-4')
                            ->schema([
                               
                            ]),

                       Tab::make('Historique')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                               
                            ]),

                    
 
                ])  
        ]);
}


}
