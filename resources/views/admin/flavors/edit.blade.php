@extends('admin.layout')

@section('title', 'Editar sabor')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Editar sabor</h2>
    @include('admin.flavors.form')
  </div>
@endsection
