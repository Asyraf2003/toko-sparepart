@if (session('success'))
    <x-v2.alert type="success" :message="session('success')" />
@endif

@if (session('status'))
    <x-v2.alert type="success" :message="session('status')" />
@endif

@if (session('error'))
    <x-v2.alert type="danger" :message="session('error')" />
@endif

@if ($errors->any())
    <x-v2.alert type="danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-v2.alert>
@endif