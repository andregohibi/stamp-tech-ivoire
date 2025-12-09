<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Company;
use App\Models\QrStamp;
use App\Models\Signatory;
use Illuminate\Support\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AdminOverview extends BaseWidget
{
    protected function getColumns(): int
    {
        return 3;
    }
    protected function getStats(): array
    {
        $today = now();

       
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $companiesWithExpiredSubscription = Company::whereNotNull('subscription_expires_at')->where('subscription_expires_at', '<', $today)->count();

        $totalSignatories = Signatory::count();
        $signatoriesWithQr = Signatory::whereHas('qrStamp')->count();
        $signatoriesWithActiveQr = Signatory::whereHas('qrStamp', function ($q) {
            $q->active();
        })->count();
        $signatoriesActiveWithActiveQr = Signatory::where('status', 'active')
            ->whereHas('qrStamp', function ($q) {
                $q->active();
            })->count();
        $signatoriesActive = Signatory::where('status', 'active')->count();
        $signatoriesInactive = Signatory::where('status', 'inactive')->count();
        $signatoriesSuspended = Signatory::where('status', 'suspended')->count();
        $signatoriesFired = Signatory::where('status', 'fired')->count();

        $totalQr = QrStamp::count();
        $activeQr = QrStamp::active()->count();
        $revokedQr = QrStamp::where('status', 'revoked')->count();
        $expiredQr = QrStamp::whereNotNull('expires_at')->where('expires_at', '<', $today)->count();

        $qrGeneratedLast30 = QrStamp::where('created_at', '>=', $today->copy()->subDays(30))->count();
        $qrsVerifiedToday = QrStamp::whereDate('last_verified_at', $today->toDateString())->count();

        return [
            Stat::make('Signataires', $totalSignatories)
                ->description('Nouveaux signataires ce mois-ci : ' . Signatory::where('created_at', '>=', Carbon::now()->startOfMonth())->count())
                ->descriptionIcon('heroicon-o-user-group', IconPosition::Before)
                ->chart([10, 15, 12, 20, 18, 25, 30])
                ->color('success'),


            Stat::make('Entreprises', $totalCompanies)
                ->description('Abonnements expirés : ' . $companiesWithExpiredSubscription)
                ->descriptionIcon('heroicon-o-building-office', IconPosition::Before)
                ->chart([5, 8, 6, 10, 12, 15, 18])
                ->color('primary'),


            Stat::make('Signataires actifs + QR actif', $signatoriesActiveWithActiveQr)
                ->description('Signataires dont le statut est actif et le QR est actif')
                ->descriptionIcon('heroicon-o-user', IconPosition::Before)
                ->color('success'),

           
        ];
    }
}
