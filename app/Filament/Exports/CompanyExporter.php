<?php

namespace App\Filament\Exports;

use App\Models\Company;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CompanyExporter extends Exporter
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name'),
            ExportColumn::make('legal_name'),
            ExportColumn::make('registration_number'),
            ExportColumn::make('sector'),
            ExportColumn::make('address'),
            ExportColumn::make('city'),
            ExportColumn::make('country'),
            ExportColumn::make('phone'),
            ExportColumn::make('email'),
            ExportColumn::make('logo'),
            ExportColumn::make('website'),
            ExportColumn::make('status'),
            ExportColumn::make('subscription_type'),
            ExportColumn::make('subscription_expires_at'),
            ExportColumn::make('qr_quota'),
            ExportColumn::make('qr_used'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('deleted_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your company export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
