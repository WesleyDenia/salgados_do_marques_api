@extends('admin.layout')

@section('title', 'Editar lista de preços')

@section('content')
  <div class="card"><h2 style="margin-top:0;">Editar lista de preços</h2>@include('admin.price-lists.form')</div>
@endsection
