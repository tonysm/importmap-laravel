@foreach ($preloadedModules as $preloadedModule)
    <link rel="modulepreload" href="{{ $preloadedModule }}">
@endforeach

@if (config('importmap.use_shim', true))
<script async src="https://ga.jspm.io/npm:es-module-shims@1.3.6/dist/es-module-shims.js"></script>
@endif

<script type="importmap">
@json($importmaps)
</script>

<script type="module">
    import '{{ $entrypoint }}';
</script>
