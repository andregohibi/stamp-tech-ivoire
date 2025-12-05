<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use App\Filament\Exports\CompanyExporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CompanyResource;
use Filament\Actions\Exports\Enums\ExportFormat;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Créer une entreprise')
                ->icon('heroicon-m-plus')
                ->color('success'),

            ExportAction::make()
            ->label('Exporter les entreprises')
            ->color('primary')
            ->icon('heroicon-s-arrow-up-on-square')
            ->exporter(CompanyExporter::class)
            ->formats([
                ExportFormat::Csv,
                ExportFormat::Xlsx,
            ]),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Liste des entreprises';
    }

    public function getTitle(): string
    {
        return 'Liste des entreprises';
    }

    
}
