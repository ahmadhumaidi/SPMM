@php
    $partner = $partners->get($partnerId);
    $childNetworks = $children->get($partnerId, collect());
    $isCycle = in_array($partnerId, $visited, true);
    $visited[] = $partnerId;
@endphp

<li style="--depth: {{ $depth }};">
    @if ($partner)
        @php
            $ownNetwork = $networks->firstWhere('downline_referral_partner_id', $partnerId) ?: $childNetworks->first();
            $status = $ownNetwork?->status ?? 'active';
        @endphp
        <div class="network-node-card status-{{ $status }}">
            <div class="node-label">{{ $depth === 0 ? 'Root / Upline' : 'Level '.$depth }}</div>
            <div class="node-name">{{ $partner->name }}</div>
            <div class="node-code">{{ $partner->referral_code ?: '-' }}</div>
            <div class="network-meta">
                <span>{{ match ($status) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', default => 'Draft' } }}</span>
                @if ($ownNetwork?->position)
                    <span>{{ match ($ownNetwork->position) { 'left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan', 'free' => 'Bebas', default => $ownNetwork->position } }}</span>
                @endif
                <span>{{ $childNetworks->count() }} downline</span>
            </div>
        </div>

        @if ($isCycle)
            <ul>
                <li style="--depth: {{ $depth }};"><div class="network-cycle">Relasi berulang terdeteksi.</div></li>
            </ul>
        @elseif ($childNetworks->isNotEmpty())
            <ul>
                @foreach ($childNetworks as $network)
                    @include('filament.resources.affiliate-network-resource.pages.partials.network-node', [
                        'partnerId' => $network->downline_referral_partner_id,
                        'partners' => $partners,
                        'children' => $children,
                        'networks' => $networks,
                        'visited' => $visited,
                        'depth' => $depth + 1,
                    ])
                @endforeach
            </ul>
        @endif
    @else
        <div class="network-cycle">Affiliator tidak ditemukan.</div>
    @endif
</li>