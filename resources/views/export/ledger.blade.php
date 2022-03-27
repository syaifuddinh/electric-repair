@extends('export.layout')

@section('content')
@foreach($data as $value)
<div>
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="6" scope="col">
        <div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>BUKU BESAR </strong></div>
        <div align="center"><strong>{{@$value['account']['name']}}</strong></div>
      </th>
    </tr>
    <tr>
      <th><strong>Cabang :</strong> {{@$company->name or 'Semua Cabang'}}</th>
      <th colspan="4"></th>
      <th><strong>Periode :</strong> {{@$start}} s/d {{@$end}}</th>
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
        if ($value['account']['jenis']==1 && $value['saldo']>=0) {
          echo formatNumber(@$value['saldo']);
        } elseif ($value['account']['jenis']==2 && $value['saldo']<0) {
          echo formatNumber(abs(@$value['saldo']));
        } else {
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value['account']['jenis']==2 && $value['saldo']>=0) {
          echo formatNumber(@$value['saldo']);
        } elseif ($value['account']['jenis']==1 && $value['saldo']<0) {
          echo formatNumber(abs(@$value['saldo']));
        } else {
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">{{ formatNumber(@$value['saldo']) }}</td>
    </tr>
    <?php $totalsaldo=$value['saldo'];$totD=0;$totK=0; ?>
    @foreach($value['detail'] as $val)
    <tr>
      <td>{{date('d-m-Y', strtotime(@$val->date_transaction))}}</td>
      <td>{{@$val->code}}</td>
      <td>{{@$value['account']['description']}}</td>
      <td class="text-right">{{formatNumber(@$val->debet)}}</td>
      <td class="text-right">{{formatNumber(@$val->credit)}}</td>
      <td class="text-right">
        <?php
        if ($value['account']['jenis']==1) {
          $totalsaldo+=($val->debet-$val->credit);
        } else {
          $totalsaldo+=($val->credit-$val->debet);
        }
        $totD+=$val->debet;
        $totK+=$val->credit;
        echo formatNumber($totalsaldo);
        ?>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="3"><strong>Total {{@$value['account']['name']}}</strong></td>
      <td class="text-right">{{formatNumber(@$totD)}}</td>
      <td class="text-right">{{formatNumber(@$totK)}}</td>
      <td class="text-right">{{formatNumber(@$totalsaldo)}}</td>
    </tr>
  </tbody>
</table>
<br/>
<hr>
<br/>
</div>
@endforeach
@endsection
