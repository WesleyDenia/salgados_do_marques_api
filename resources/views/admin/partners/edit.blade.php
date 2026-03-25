@extends('admin.layout')

@section('title', 'Editar Parceiro')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Editar parceiro</h2>
    @include('admin.partners.form')
  </div>
@endsection
