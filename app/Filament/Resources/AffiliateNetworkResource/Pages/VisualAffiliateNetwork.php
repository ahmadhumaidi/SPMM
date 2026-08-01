<?php

namespace App\Filament\Resources\AffiliateNetworkResource\Pages;

use App\Filament\Resources\AffiliateNetworkResource;
use App\Models\AffiliateNetwork;
use App\Models\ReferralPartner;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class VisualAffiliateNetwork extends Page
{
    protected static string $resource = AffiliateNetworkResource::class;

    protected static string $view = 'filament.resources.affiliate-network-resource.pages.visual-affiliate-network';

    protected static ?string $title = 'Visual Affiliate Network';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('list')
                ->label('Daftar Network')
                ->icon('heroicon-o-table-cells')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getViewData(): array
    {
        $networks = static::getResource()::getEloquentQuery()
            ->with(['upline', 'downline'])
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $partners = $this->partnerMap($networks);
        $children = $networks
            ->filter(fn (AffiliateNetwork $network): bool => filled($network->upline_referral_partner_id) && filled($network->downline_referral_partner_id))
            ->groupBy('upline_referral_partner_id');

        $downlineIds = $networks->pluck('downline_referral_partner_id')->filter()->unique();
        $uplineIds = $networks->pluck('upline_referral_partner_id')->filter()->unique();

        $rootIds = $networks
            ->filter(fn (AffiliateNetwork $network): bool => blank($network->upline_referral_partner_id) && filled($network->downline_referral_partner_id))
            ->pluck('downline_referral_partner_id')
            ->merge($uplineIds->diff($downlineIds))
            ->filter()
            ->unique()
            ->values();

        if ($rootIds->isEmpty() && $partners->isNotEmpty()) {
            $rootIds = collect([$partners->keys()->first()]);
        }

        return [
            'networks' => $networks,
            'partners' => $partners,
            'children' => $children,
            'rootIds' => $rootIds,
            'stats' => [
                'total' => $networks->count(),
                'active' => $networks->where('status', 'active')->count(),
                'draft' => $networks->where('status', 'draft')->count(),
                'inactive' => $networks->where('status', 'inactive')->count(),
                'roots' => $rootIds->count(),
            ],
        ];
    }

    /**
     * @param Collection<int, AffiliateNetwork> $networks
     * @return Collection<int, ReferralPartner>
     */
    private function partnerMap(Collection $networks): Collection
    {
        return $networks
            ->flatMap(fn (AffiliateNetwork $network): array => [$network->upline, $network->downline])
            ->filter()
            ->unique('id')
            ->keyBy('id');
    }
}