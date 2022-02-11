@foreach ($preloadedModules as $preloadedModule)
    <link rel="modulepreload" href="{{ $preloadedModule }}"@if ($nonce) nonce="{{ $nonce }}"@endif />
@endforeach

@if (config('importmap.use_shim'))
@if ($nonce) <script type="esms-options" nonce="{{ $nonce }}">{"nonce":"{{ $nonce }}"}</script> @endif
<script async src="https://ga.jspm.io/npm:es-module-shims@1.3.6/dist/es-module-shims.js" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}"@endif></script>
@endif

<script type="importmap" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>
@json($importmaps)
</script>

<script type="module" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>
    import {{ $entrypoint }} as '{{ $entrypoint }}';
</script>
