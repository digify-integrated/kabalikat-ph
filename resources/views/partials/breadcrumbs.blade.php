<div class="d-flex align-items-center pt-1">
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold">
        <li class="breadcrumb-item text-white fw-bold lh-1">
           <a href="{{ url('/app') }}" class="text-white text-hover-primary">
                <i class="ki-outline ki-abstract-26 text-gray-700 fs-6"></i>
            </a>
        </li>

        @foreach(($bc_items ?? []) as $item)
            <li class="breadcrumb-item">
                <i class="ki-outline ki-right fs-7 text-gray-700 mx-n1"></i>
            </li>

            @php
                $isNavCrumb = !is_null($item['id']);
            @endphp

            <li class="breadcrumb-item text-white fw-bold lh-1">
                @if($isNavCrumb)
                    <a class="text-decoration-none text-white fw-bold fs-7"
                        href="{{ route('apps.base', ['appModuleId' => $bc_appModuleId, 'navigationMenuId' => $item['id']]) }}">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-white fw-bold fs-7">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>