@extends('shared.layouts.app')

@section('title', 'Telegram Bot')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Telegram Bot</h3>
            <p class="text-muted mb-0">Pairing bot Telegram untuk admin dan akses menu laporan.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-light" href="{{ url('/admin') }}">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Status Link</h5>

                    @if($linked)
                        <div class="mb-1">
                            <span class="badge bg-success">LINKED</span>
                            <span class="ms-2">chat_id: <span class="fw-semibold">{{ $linked->chat_id }}</span></span>
                        </div>
                        <div class="text-muted small">Linked at: {{ $linked->linked_at }}</div>
                    @else
                        <div class="mb-1">
                            <span class="badge bg-secondary">NOT LINKED</span>
                        </div>
                        <div class="text-muted small">
                            Buat token pairing lalu di Telegram ketik: <span class="fw-semibold">/link &lt;TOKEN&gt;</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Generate Pairing Token</h5>

                    @if($lastToken)
                        <div class="border rounded p-3 bg-body-tertiary text-body">
                            <div class="fw-semibold">Token (sekali tampil):</div>
                            <div class="mt-1">
                                <span class="fw-semibold">{{ $lastToken }}</span>
                            </div>
                            <div class="text-muted mt-2">
                                Ketik di Telegram: <span class="fw-semibold">/link {{ $lastToken }}</span>
                            </div>
                        </div>
                    @endif

                    <form method="post" action="{{ url('/admin/telegram/pairing-token') }}" class="d-flex gap-2 mt-2">
                        @csrf
                        <button class="btn btn-primary" type="submit">Generate Token</button>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/telegram/payment-proofs') }}">Bukti Bayar (PENDING)</a>
                    </form>

                    @if(is_iterable($pendingTokens) && count($pendingTokens) > 0)
                        <hr>
                        <div class="text-muted small mb-2">Token aktif (belum dipakai):</div>
                        <ul class="mb-0">
                            @foreach($pendingTokens as $t)
                                <li class="small">Expire: {{ $t->expires_at }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
#tes
