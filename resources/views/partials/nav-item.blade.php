@php
    $hasChildren = !empty($item['children']);
    $isTopLevel = ($level === 0);

    $defaultIcon = 'ki-duotone ki-abstract-26';
    $iconClass = !empty($item['icon']) ? $item['icon'] : $defaultIcon;

    $href = route('apps.base', [
        'appId' => $nav_app_id,
        'navigationMenuId' => $item['id'],
    ]);
@endphp

{{-- ========================= --}}
{{-- ITEM WITH CHILDREN --}}
{{-- ========================= --}}
@if($hasChildren)
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">

        <!-- Menu Link -->
        <span class="menu-link">
            
            {{-- ICON: only for top level --}}
            @if($isTopLevel)
                <span class="menu-icon">
                    <i class="{{ $iconClass }} fs-2"></i>
                </span>
            @else
                <span class="menu-bullet">
                    <span class="bullet bullet-dot"></span>
                </span>
            @endif

            <span class="menu-title">{{ $item['title'] }}</span>
            <span class="menu-arrow"></span>
        </span>

        <!-- Sub Menu -->
        <div class="menu-sub menu-sub-accordion">
            @foreach($item['children'] as $child)
                @include('partials.nav-item', [
                    'item' => $child,
                    'level' => $level + 1,
                    'nav_app_id' => $nav_app_id
                ])
            @endforeach
        </div>
    </div>

{{-- ========================= --}}
{{-- LEAF ITEM --}}
{{-- ========================= --}}
@else
    <div class="menu-item">
        <a class="menu-link" href="{{ $href }}">

            {{-- ICON: only for top level --}}
            @if($isTopLevel)
                <span class="menu-icon">
                    <i class="{{ $iconClass }} fs-2"></i>
                </span>
            @else
                <span class="menu-bullet">
                    <span class="bullet bullet-dot"></span>
                </span>
            @endif

            <span class="menu-title">{{ $item['title'] }}</span>
        </a>
    </div>
@endif