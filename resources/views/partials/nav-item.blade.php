@php
    $hasChildren = !empty($item['children']);
    $isTopLevel = ($level === 0);
    $isSecondLevel = ($level === 1);

    // Choose a default icon for second level if none provided.
    // Replace with whatever KeenThemes icon you prefer.
    $defaultSecondLevelIcon = 'ki-outline ki-abstract-26';
    $iconClass = !empty($item['icon']) ? $item['icon'] : $defaultSecondLevelIcon;

    // Route for leaf items
    $href = route('apps.base', [
        'appModuleId' => $nav_appModuleId,
        'navigationMenuId' => $item['id'],
    ]);
@endphp

@if($isTopLevel)
    {{-- TOP LEVEL --}}
    @if(!$hasChildren)
        <div class="menu-item menu-here-bg menu-lg-down-accordion me-0 me-lg-2">
            <a class="menu-link" href="{{ $href }}">
                <span class="menu-title">{{ $item['title'] }}</span>
            </a>
        </div>
    @else
        <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
             data-kt-menu-placement="bottom-start"
             class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">

            <span class="menu-link">
                <span class="menu-title">{{ $item['title'] }}</span>
                <span class="menu-arrow d-lg-none"></span>
            </span>

            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                @foreach($item['children'] as $child)
                    @include('partials.nav-item', ['item' => $child, 'level' => $level + 1, 'nav_appModuleId' => $nav_appModuleId])
                @endforeach
            </div>
        </div>
    @endif

@else
    {{-- NESTED LEVELS --}}
    @if($hasChildren)
        <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
             data-kt-menu-placement="right-start"
             class="menu-item menu-lg-down-accordion">

            <span class="menu-link">
                {{-- Second level must always have an icon --}}
                @if($isSecondLevel)
                    <span class="menu-icon">
                        <i class="{{ $iconClass }} fs-2"></i>
                    </span>
                @else
                    {{-- Third+ level: keep your existing logic (icon if present, else bullet) --}}
                    @if(!empty($item['icon']))
                        <span class="menu-icon">
                            <i class="{{ $item['icon'] }}"></i>
                        </span>
                    @else
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                    @endif
                @endif

                <span class="menu-title">{{ $item['title'] }}</span>
                <span class="menu-arrow"></span>
            </span>

            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                @foreach($item['children'] as $child)
                    @include('partials.nav-item', ['item' => $child, 'level' => $level + 1, 'nav_appModuleId' => $nav_appModuleId])
                @endforeach
            </div>
        </div>

    @else
        <div class="menu-item">
            <a class="menu-link" href="{{ $href }}">
                {{-- Second level leaf: always icon --}}
                @if($isSecondLevel)
                    <span class="menu-icon">
                        <i class="{{ $iconClass }} fs-2"></i>
                    </span>
                @else
                    {{-- Third+ leaf: keep bullet (or icon if you want) --}}
                    <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                    </span>
                @endif

                <span class="menu-title">{{ $item['title'] }}</span>
            </a>
        </div>
    @endif
@endif
