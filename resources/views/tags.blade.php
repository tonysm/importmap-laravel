<script type="importmap" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>
    @json($importmaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
</script>

@foreach ($preloadedModules as $preloadedModule)
    <link rel="modulepreload" href="{{ $preloadedModule }}"@if ($nonce) nonce="{{ $nonce }}"@endif />
@endforeach

@if (config('importmap.use_shim'))
@if ($nonce) <script type="esms-options" nonce="{{ $nonce }}">{"nonce":"{{ $nonce }}"}</script> @endif
<script async src="{{ sprintf('https://ga.jspm.io/npm:es-module-shims@%s/dist/es-module-shims.js', config('importmap.shim_version')) }}" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}"@endif></script>
@endif

<script type="module" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>import '{{ $entrypoint }}';</script>
