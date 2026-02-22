@if (session('success'))
    <x-ui.alert type="success" :message="session('success')" />
@endif

@if (session('status'))
    <x-ui.alert type="success" :message="session('status')" />
@endif

@if (session('error'))
    <x-ui.alert type="danger" :message="session('error')" />
@endif

@if ($errors->any())
    <x-ui.alert type="danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif