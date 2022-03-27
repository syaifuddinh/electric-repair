@extends('export.head')
@section('title','Laporan Outstanding Hutang ')
@section('content')
<div>
<table style="width: 100%; border:none;">
  <thead>
    <tr>
      <td width="60%" colspan="3" scope="col">
        <div align="center"><strong>Laporan Outstanding Hutang</strong></div>
      </td>
    </tr>
  </thead>
</table>
<table style="width: 100%;border-width:1px;border-collapse: collapse;border-style: solid;">
    <thead style="text-align:center">
    <tr>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">No.</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;"  >Tanggal</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">No Transaksi</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Company</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Supplier</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Tgl Jatuh Tempo</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Hutang</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Bayar</th>
      <th colspan="6" style="border-width:1px;border-collapse: collapse;border-style: solid;">Sisa</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Overdue Hari</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Titipan BG</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Tgl FUP Terakhir</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">User</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Keterangan FUP</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Sumber Transaksi</th>
    </tr>
    <tr>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Rp</th>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">%</th>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">30</th>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">60</th>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">90</th>
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">> 90</th>

    </tr>
  </thead>
  <tbody>
    @foreach($source as $i => $value)
    <tr>
      <td>{{$i+1}}</td>
      <td>{{$value->date_transaction}}</td>
      <td>{{$value->code}}</td>
      <td>{{$value->company}}</td>
      <td>{{$value->contact}}</td>
      <td>{{$value->date_tempo}}</td>
      <td class="text-right">{{number_format($value->credit)}}</td>
      <td class="text-right">{{number_format($value->debet)}}</td>
      <td class="text-right">{{number_format($value->sisa)}}</td>
      <td>{{number_format($value->percent)}}</td>
      <td>{{$value->day_30}}</td>
      <td>{{$value->day_60}}</td>
      <td>{{$value->day_90}}</td>
      <td>{{$value->day_more_90}}</td>
      <td>{{$value->due_days}}</td>
      <td>{{0}}</td>
      <td>{{$value->updated_at}}</td>
      <td>{{$value->username}}</td>
      <td>{{$value->description}}</td>
      <td>{{$value->type_transaction}}</td>
    </tr>
    @endforeach
  </tbody>
</table>
</div>
@endsection
