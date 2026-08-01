<x-filament-panels::page>
    <div class="affiliate-network-visual">
        <div class="network-summary-grid">
            <div class="network-summary-card">
                <span>Total relasi</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="network-summary-card is-active">
                <span>Aktif</span>
                <strong>{{ number_format($stats['active']) }}</strong>
            </div>
            <div class="network-summary-card is-draft">
                <span>Draft</span>
                <strong>{{ number_format($stats['draft']) }}</strong>
            </div>
            <div class="network-summary-card is-root">
                <span>Root network</span>
                <strong>{{ number_format($stats['roots']) }}</strong>
            </div>
        </div>

        <div class="network-toolbar">
            <div class="network-legend">
            <span><i class="dot active"></i> Aktif</span>
            <span><i class="dot draft"></i> Draft</span>
            <span><i class="dot inactive"></i> Nonaktif</span>
        </div>
            <button type="button" class="network-replay-button" onclick="replayAffiliateNetworkAnimation(this)">Putar Animasi</button>
        </div>

        <div class="network-canvas">
            @if ($rootIds->isEmpty())
                <div class="network-empty">
                    <strong>Belum ada data network.</strong>
                    <p>Tambahkan relasi upline dan downline dulu dari menu Affiliate Network.</p>
                </div>
            @else
                <div class="network-tree">
                    <ul class="network-root-list">
                        @foreach ($rootIds as $rootId)
                            @include('filament.resources.affiliate-network-resource.pages.partials.network-node', [
                                'partnerId' => $rootId,
                                'partners' => $partners,
                                'children' => $children,
                                'networks' => $networks,
                                'visited' => [],
                                'depth' => 0,
                            ])
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <style>
        .affiliate-network-visual {
            display: grid;
            gap: 1rem;
        }

        .network-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
        }

        .network-summary-card,
        .network-canvas {
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
            border-radius: 18px;
        }

        .network-summary-card {
            padding: 1rem 1.1rem;
        }

        .network-summary-card span {
            display: block;
            color: #64748b;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .network-summary-card strong {
            display: block;
            margin-top: .4rem;
            color: #020617;
            font-size: 1.75rem;
            line-height: 1;
        }

        .network-summary-card.is-active strong { color: #047857; }
        .network-summary-card.is-draft strong { color: #b45309; }
        .network-summary-card.is-root strong { color: #2563eb; }

        .network-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .network-legend {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            color: #475569;
            font-weight: 700;
            font-size: .85rem;
        }

        .network-legend span {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 999px;
            background: rgba(255, 255, 255, .72);
            padding: .35rem .65rem;
        }

        .dot {
            width: .6rem;
            height: .6rem;
            border-radius: 999px;
            display: inline-block;
        }

        .dot.active { background: #10b981; }
        .dot.draft { background: #f59e0b; }
        .dot.inactive { background: #94a3b8; }
        .network-canvas {
            min-height: 520px;
            overflow: auto;
            padding: 1.25rem;
            background:
                radial-gradient(circle at 10% 10%, rgba(56, 189, 248, .16), transparent 28%),
                radial-gradient(circle at 90% 5%, rgba(34, 197, 94, .12), transparent 24%),
                rgba(248, 250, 252, .9);
        }

        .network-tree {
            min-width: max-content;
            padding: .5rem;
        }

        .network-tree ul {
            display: flex;
            justify-content: center;
            position: relative;
            list-style: none;
            margin: 0;
            padding: 1.25rem 0 0;
        }

        .network-tree li {
            position: relative;
            list-style: none;
            text-align: center;
            padding: 1.25rem .45rem 0;
        }

        .network-tree li::before,
        .network-tree li::after {
            content: '';
            position: absolute;
            top: 0;
            width: 50%;
            height: 1.25rem;
            border-top: 2px solid rgba(51, 65, 85, .68);
        }

        .network-tree li::before,
        .network-tree li::after {
            opacity: 0;
            animation: networkLineHorizontal .7s ease forwards;
            animation-delay: calc(var(--depth, 0) * .12s);
        }

        .network-tree li::before {
            right: 50%;
        }

        .network-tree li::before {
            transform: scaleX(0);
            transform-origin: right center;
        }

        .network-tree li::after {
            left: 50%;
            border-left: 2px solid rgba(51, 65, 85, .68);
        }

        .network-tree li::after {
            transform: scaleX(0);
            transform-origin: left center;
        }

        .network-tree li:first-child::before,
        .network-tree li:last-child::after {
            border: 0;
        }

        .network-tree li:last-child::before {
            border-right: 2px solid rgba(51, 65, 85, .68);
            border-radius: 0 8px 0 0;
        }

        .network-tree li:first-child::after {
            border-radius: 8px 0 0 0;
        }

        .network-tree li:only-child {
            padding-top: 0;
        }

        .network-tree li:only-child::before,
        .network-tree li:only-child::after {
            display: none;
        }

        .network-tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            height: 1.25rem;
            border-left: 2px solid rgba(51, 65, 85, .68);
        }

        .network-tree ul ul::before {
            opacity: 0;
            transform: scaleY(0);
            transform-origin: top center;
            animation: networkLineVertical .55s ease forwards;
            animation-delay: calc(var(--depth, 0) * .12s + .08s);
        }

        .network-root-list {
            gap: 2rem;
            align-items: flex-start;
        }

        .network-root-list > li::before,
        .network-root-list > li::after {
            display: none;
        }

        .network-node-card {
            display: inline-block;
            opacity: 0;
            transform: translateY(8px) scale(.96);
            animation: networkNodeIn .45s ease forwards;
            animation-delay: calc(var(--depth, 0) * .12s + .16s);
            text-align: left;
            min-width: 160px;
            max-width: 190px;
            border: 1px solid rgba(37, 99, 235, .16);
            border-radius: 12px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            padding: .62rem .68rem;
            position: relative;
        }

        .network-node-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            border-radius: 12px 0 0 12px;
            background: #94a3b8;
        }

        .network-node-card.status-active::before { background: #10b981; }
        .network-node-card.status-draft::before { background: #f59e0b; }
        .network-node-card.status-inactive::before { background: #94a3b8; }

        .network-node-card .node-label {
            color: #64748b;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .network-node-card .node-name {
            margin-top: .25rem;
            color: #020617;
            font-size: .84rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .network-node-card .node-code {
            margin-top: .28rem;
            display: inline-flex;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            padding: .16rem .42rem;
            font-size: .68rem;
            font-weight: 800;
        }

        .network-meta {
            display: flex;
            gap: .25rem;
            flex-wrap: wrap;
            margin-top: .42rem;
        }

        .network-meta span {
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: .62rem;
            font-weight: 700;
            padding: .14rem .36rem;
        }

        .network-cycle,
        .network-empty {
            border: 1px dashed rgba(148, 163, 184, .5);
            border-radius: 14px;
            background: rgba(255, 255, 255, .72);
            color: #64748b;
            padding: .85rem 1rem;
            font-weight: 700;
        }

        html.dark .network-summary-card,
        html.dark .network-canvas,
        html.dark .network-node-card,
        html.dark .network-legend span,
        html.dark .network-cycle,
        html.dark .network-empty {
            background: rgba(15, 23, 42, .68);
            border-color: rgba(148, 163, 184, .18);
        }

        html.dark .network-tree li::before,
        html.dark .network-tree li::after {
            border-color: rgba(203, 213, 225, .74);
        }

        html.dark .network-tree ul ul::before {
            border-color: rgba(203, 213, 225, .74);
        }

        html.dark .network-summary-card strong,
        html.dark .network-node-card .node-name {
            color: #f8fafc;
        }

        html.dark 
        .network-canvas {
            background:
                radial-gradient(circle at 10% 10%, rgba(56, 189, 248, .18), transparent 28%),
                radial-gradient(circle at 90% 5%, rgba(34, 197, 94, .13), transparent 24%),
                rgba(2, 6, 23, .72);
        }
        @keyframes networkLineHorizontal {
            from {
                opacity: 0;
                transform: scaleX(0);
            }
            to {
                opacity: 1;
                transform: scaleX(1);
            }
        }

        @keyframes networkLineVertical {
            from {
                opacity: 0;
                transform: scaleY(0);
            }
            to {
                opacity: 1;
                transform: scaleY(1);
            }
        }

        @keyframes networkNodeIn {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        

        @media (prefers-reduced-motion: reduce) {
            .network-tree li::before,
            .network-tree li::after,
            .network-tree ul ul::before,
            .network-node-card {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
        

        @media (max-width: 900px) {
            .network-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .network-summary-grid {
                grid-template-columns: 1fr;
            }
        .network-canvas {
                padding: 1rem;
            }
        }
    </style>
</x-filament-panels::page>