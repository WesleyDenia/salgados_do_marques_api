@extends('admin.layout')

@section('title', 'Editar produto')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Editar produto</h2>
    @include('admin.products.form')
  </div>
@endsection
