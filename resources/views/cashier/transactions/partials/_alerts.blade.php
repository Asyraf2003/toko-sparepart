@if (session('error'))
    <div style="border:1px solid #999;padding:8px;margin:10px 0;">
        <b>Error:</b> {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div style="border:1px solid #999;padding:8px;margin:10px 0;">
        <b>Validation:</b>
        <ul>
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif