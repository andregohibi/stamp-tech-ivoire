<?php

namespace App\Filament\Widgets;

use App\Models\QrStamp;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AdminLineChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'QR codes générés';

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

        $trend = Trend::query(QrStamp::query())
            ->dateColumn('created_at')
            ->between(start: $start, end: $end)
            ->perMonth()
            ->count();

        $labels = $trend->map(fn ($value) => Carbon::parse($value->date)->format('M Y'))->toArray();
        $values = $trend->map(fn ($value) => $value->aggregate)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'QR générés',
                    'data' => $values,
                    'backgroundColor' => '#2563EB',
                    'borderColor' => '#93C5FD',
                    'fill' => false,
                    'tension' => 0.2,
                ],
            ],
            'labels' => $labels,
            // Optional ChartJS options can be returned here and used by the widget
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'tooltip' => ['mode' => 'index', 'intersect' => false],
                ],
                'scales' => [
                    'x' => ['grid' => ['display' => false]],
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
