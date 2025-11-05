@extends('admin.layout')

@section('title', 'Novo produto')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Novo produto</h2>
    @include('admin.products.form')
  </div>
@endsection
