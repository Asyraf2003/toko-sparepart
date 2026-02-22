@if (session('error'))
    <x-v2.alert type="danger">
        <b>Error:</b> {{ session('error') }}
    </x-v2.alert>
@endif

@if ($errors->any())
    <x-v2.alert type="danger">
        <b>Validation:</b>
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </x-v2.alert>
@endif