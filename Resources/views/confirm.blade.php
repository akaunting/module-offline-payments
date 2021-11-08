<h2>{{ $setting['name'] }}</h2>

@if ($setting['description'])
    <div class="well well-sm mt-2 blockquote">
        {{ $setting['description'] }}
    </div>
@endif
