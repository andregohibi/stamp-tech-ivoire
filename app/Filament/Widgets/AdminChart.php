<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use App\Models\Signatory;
use Illuminate\Support\Carbon;

class AdminChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected static ?string $heading = 'Statistiques des signataires par statut';

    protected int | string | array $columnSpan = '1/2';

    protected function getData(): array
    {
        // Récupérer les dates du filtre du Dashboard
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        
        // Déterminer la plage de dates
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            // Par défaut: 12 derniers mois
            $months = 12;
            $start = now()->subMonths($months - 1)->startOfMonth();
            $end = now()->endOfMonth();
        }

        $statuses = [
            'active' => 'Actifs',
            'inactive' => 'Inactifs',
            'suspended' => 'Suspendus',
            'fired' => 'Licenciés',
        ];

        $colors = [
            'active' => '#10B981', // green
            'inactive' => '#6B7280', // gray
            'suspended' => '#F59E0B', // amber
            'fired' => '#EF4444', // red
        ];

        $datasets = [];
        $labels = [];

        foreach ($statuses as $statusKey => $statusLabel) {
            // Count signatories created (created_at) per month filtered by status
            $trend = Trend::query(Signatory::query()->where('status', $statusKey))
                ->dateColumn('created_at')
                ->between(start: $start, end: $end)
                ->perMonth()
                ->count();

            $values = $trend->map(fn ($value) => $value->aggregate)->toArray();

            if (empty($labels)) {
                $labels = $trend->map(fn ($value) => Carbon::parse($value->date)->format('M Y'))->toArray();
            }

            $datasets[] = [
                'label' => $statusLabel,
                'data' => $values,
                'backgroundColor' => $colors[$statusKey] ?? '#6B7280',
                'borderColor' => $colors[$statusKey] ?? '#6B7280',
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}