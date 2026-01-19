@extends('admin.layout')

@section('title', 'Novo sabor')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Novo sabor</h2>
    @include('admin.flavors.form')
  </div>
@endsection
