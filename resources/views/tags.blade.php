<script type="importmap" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>
    @json($importmaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
</script>

@foreach ($preloadedModules as $preloadedModule)
    <link rel="modulepreload" href="{{ $preloadedModule }}"@if ($nonce) nonce="{{ $nonce }}"@endif />
@endforeach

<script type="module" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>import '{{ $entrypoint }}';</script>
