@extends('shared.layouts.base')

@section('title', 'Work Order')

@section('body')
    <div class="container" style="max-width: 800px; margin: 20px auto;">
        <div class="card">
            <div class="card-body">
                <h3 class="mb-3">WORK ORDER</h3>

                <div>No: <b>{{ $tx->transaction_number }}</b></div>
                <div>Tanggal: {{ $tx->business_date }}</div>
                <div>Status: {{ $tx->status }}</div>

                <hr>

                <h5>Customer</h5>
                <div>Nama: {{ $tx->customer_name ?? '-' }}</div>
                <div>HP: {{ $tx->customer_phone ?? '-' }}</div>
                <div>No Polisi: {{ $tx->vehicle_plate ?? '-' }}</div>

                <hr>

                <h5>Service</h5>
                @if($services->count() === 0)
                    <p class="mb-0">-</p>
                @else
                    <ol class="mb-0">
                        @foreach($services as $s)
                            <li>{{ $s->description }}</li>
                        @endforeach
                    </ol>
                @endif

                <hr>

                <h5>Sparepart (dipakai/ditahan)</h5>
                @if($parts->count() === 0)
                    <p class="mb-0">-</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nama</th>
                                <th>Qty</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($parts as $p)
                                <tr>
                                    <td>{{ $p->sku }}</td>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ $p->qty }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <hr>

                <div>Catatan: ____________________________</div>
                <div class="mt-3">Tanda tangan: _______________________</div>
            </div>
        </div>
    </div>
@endsection