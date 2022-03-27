@extends('export.layout')

@section('content')
@foreach($data as $value)
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="6" scope="col">
        <div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>BUKU BESAR PEMBANTU PIUTANG</strong></div>
        <div align="center"><strong>{{$value['contact']['name']}}</strong></div>
      </th>
    </tr>
    <tr>
      <th><strong>Cabang :</strong> {{$company->name or 'Semua Cabang'}}</th>
      <th colspan="4"></th>
      <th><strong>Periode :</strong> {{$start}} s/d {{$end}}</th>
    </tr>
    <tr>
      <th><strong>Tanggal</strong></th>
      <th><strong>ID Reff</strong></th>
      <th><strong>Keterangan</strong></th>
      <th><strong>Debet</strong></th>
      <th><strong>Kredit</strong></th>
      <th><strong>Saldo</strong></th>
    </tr>
  </thead>
  <tbody>
    <?php $totalsaldo=$value['saldo_awal'];$totD=0;$totK=0; ?>
    <tr>
      <td></td>
      <td>-</td>
      <td><strong>Saldo Awal</strong></td>
      <td>
        <?php
        if ($value['saldo_awal'] >= 0) {
          echo formatNumber($value['saldo_awal']);
          $totD+=abs($value['saldo_awal']);
        } else {
          echo 0;
        }
        ?>
      </td>
      <td>
        <?php
        if ($value['saldo_awal']<0) {
          echo formatNumber(abs($value['saldo_awal']));
          $totK+=abs($value['saldo_awal']);
        } else {
          echo 0;
        }
        ?>
      </td>
      <td>{{ formatNumber($value['saldo_awal']) }}</td>
    </tr>
    @foreach($value['detail'] as $val)
    <tr>
      <td>{{date('d-m-Y', strtotime($val->date_transaction))}}</td>
      <td>{{$val->code}}</td>
      <td>{{$val->description}}</td>
      <td>{{formatNumber($val->debet)}}</td>
      <td>{{formatNumber($val->credit)}}</td>
      <td>
        <?php
        $totalsaldo+=($val->debet-$val->credit);
        $totD+=$val->debet;
        $totK+=$val->credit;
        echo formatNumber($totalsaldo);
        ?>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="3"><strong>Total Piutang : {{$value['contact']['name']}}</strong></td>
      <td>{{formatNumber($totD)}}</td>
      <td>{{formatNumber($totK)}}</td>
      <td>{{formatNumber($value['saldo_akhir'])}}</td>
    </tr>
  </tbody>
</table>
<hr>
@endforeach
@endsection
