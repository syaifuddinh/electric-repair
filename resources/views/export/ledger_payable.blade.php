@extends('export.layout')

@section('content')
@foreach($data as $value)
<div>
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="6" scope="col">
        <div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>BUKU BESAR PEMBANTU HUTANG</strong></div>
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
    <tr>
      <td></td>
      <td>-</td>
      <td><strong>Saldo Awal</strong></td>
      <td class="text-right">
        <?php
        if ($value['saldo_awal'] < 0) {
          $totD=abs($value['saldo_awal']);
          echo formatNumber(abs($value['saldo_awal']));
        } else {
          $totD=0;
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value['saldo_awal']>=0) {
          $totK=abs($value['saldo_awal']);
          echo formatNumber(abs($value['saldo_awal']));
        } else {
          $totK=0;
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">{{ formatNumber($value['saldo_awal']) }}</td>
    </tr>
    <?php $totalsaldo = $value['saldo_awal']; ?>
    @foreach($value['detail'] as $val)
    <tr>
      <td>{{date('d-m-Y', strtotime($val->date_transaction))}}</td>
      <td>{{$val->code}}</td>
      <td>{{$val->description}}</td>
      <td class="text-right">{{formatNumber($val->debet)}}</td>
      <td class="text-right">{{formatNumber($val->credit)}}</td>
      <td class="text-right">
        <?php
        $totalsaldo += $val->credit - $val->debet;
        echo formatNumber($totalsaldo);
        ?>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="3"><strong>Total Hutang : {{$value['contact']['name']}}</strong></td>
      <td class="text-right">{{formatNumber($value['saldo_debet'])}}</td>
      <td class="text-right">{{formatNumber($value['saldo_credit'])}}</td>
      <td class="text-right">{{formatNumber($value['saldo_akhir'])}}</td>
    </tr>
  </tbody>
</table>
<br/>
<hr/>
<br/>
</div>
@endforeach
@endsection
