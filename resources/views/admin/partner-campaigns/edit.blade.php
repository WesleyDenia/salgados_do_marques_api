@extends('admin.layout')

@section('title', 'Editar Campanha')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Editar campanha de parceiro</h2>
    @include('admin.partner-campaigns.form')
  </div>
@endsection
