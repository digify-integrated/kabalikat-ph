<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 pt-1">
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('apps.index') }}" class="text-muted text-hover-primary">
            Home {{ $routeName ?? '' }}
        </a>
    </li>

    @foreach(($bc_items ?? []) as $index => $item)
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-300 w-5px h-2px"></span>
        </li>

        @php
            $isNavCrumb = !is_null($item['id']);
            $isLast = $loop->last;
            // The item is clickable only if it isn't last, has an ID, and exists in navigation_menu_route
            $isClickable = !$isLast && $isNavCrumb && ($item['has_route'] ?? false);
        @endphp

        <li class="breadcrumb-item {{ $isLast ? 'text-gray-900' : 'text-muted' }}">
            @if($isClickable)
                <a href="{{ route('apps.base', ['appId' => $bc_app_id, 'navigationMenuId' => $item['id']]) }}" class="text-muted text-hover-primary">
                    {{ $item['label'] }}
                </a>
            @else
                {{ $item['label'] }}
            @endif
        </li>
    @endforeach
</ul>