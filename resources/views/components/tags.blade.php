@props(['entrypoint' => 'app', 'nonce' => null, 'importmap' => null])

@php
    $resolver = new \Tonysm\ImportmapLaravel\AssetResolver();

    $importmaps = $importmap?->asArray($resolver) ?? \Tonysm\ImportmapLaravel\Facades\Importmap::asArray($resolver);
    $preloadedModules = $importmap?->preloadedModulePaths($resolver) ?? \Tonysm\ImportmapLaravel\Facades\Importmap::preloadedModulePaths($resolver);
@endphp

<script type="importmap" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>
    @json($importmaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
</script>

@foreach ($preloadedModules as $preloadedModule)
    <link rel="modulepreload" href="{{ $preloadedModule }}"@if ($nonce) nonce="{{ $nonce }}"@endif />
@endforeach

<script type="module" data-turbo-track="reload"@if ($nonce) nonce="{{ $nonce }}" @endif>import '{{ $entrypoint }}';</script>
