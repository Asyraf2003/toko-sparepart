@if (session('error'))
    <x-ui.alert type="danger">
        <b>Error:</b> {{ session('error') }}
    </x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert type="danger">
        <b>Validation:</b>
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif