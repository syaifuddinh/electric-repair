@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="7" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>JURNAL UMUM </strong></div>
      </th>
    </tr>
    <tr>
      <td colspan="2"><strong>Cabang :</strong> {{$company->name or 'Semua Cabang'}}</td>
      <td colspan="3"></td>
      <td colspan="2">Periode {{$start}} s/d {{$end}}</td>
    </tr>
  </thead>
  <tbody>
    @php
    $totD=0;$totK=0;
    @endphp
    @foreach($data as $value)
    <tr>
      <td colspan="2"><strong>Tanggal :</strong> {{date('d-m-Y', strtotime($value->date_transaction))}}</td>
      <td><strong>Kode :</strong> {{$value->code}} </td>
      <td><strong>ID Reff :</strong> </td>
      <td><strong>Tipe Jurnal :</strong> {{$value->type_transaction->name}}</td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td style="font-weight:bold; width: 20px;">No</td>
      <td style="font-weight:bold;">Kode Akun</td>
      <td style="font-weight:bold;" colspan="2">Nama Akun</td>
      <td style="font-weight:bold;">Keterangan</td>
      <td style="font-weight:bold;">Debet</td>
      <td style="font-weight:bold;">Kredit</td>
    </tr>
    @foreach($value->details as $key => $val)
    <tr>
      <td>{{$key+1}}.</td>
      <td>{{$val->account->code}}</td>
      <td colspan="2">{{$val->account->name}}</td>
      <td>{{$val->description}}</td>
      <td class="text-right">{{formatNumber($val->debet)}}</td>
      <td class="text-right">{{formatNumber($val->credit)}}</td>
    </tr>
    @php
    $totD+=$val->debet;
    $totK+=$val->credit;
    @endphp
    @endforeach
    <tr>
      <td colspan="5"><strong>Keterangan :</strong> {{$value->description}}</td>
      <td class="text-right">{{formatNumber($value->details->sum('debet'))}}</td>
      <td class="text-right">{{formatNumber($value->details->sum('credit'))}}</td>
    </tr>
    @endforeach
    <tr>
      <td colspan="5"><h4> Total :</h4></td>
      <td class="text-right"><h4>{{formatNumber($totD)}}</h4></td>
      <td class="text-right"><h4>{{formatNumber($totK)}}</h4></td>
    </tr>
  </tbody>
</table>
@endsection
