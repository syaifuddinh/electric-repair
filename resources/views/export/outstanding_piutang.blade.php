@extends('export.head')
@section('title','Laporan Outstanding Piutang ')
@section('content')
<table style="width: 100%;" style="border:none">
  <thead>
    <tr>
      <td width="60%" colspan="3" scope="col">
        <div align="center"><strong>Laporan Outstanding Piutang</strong></div>
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
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Costumer</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Tgl Jatuh Tempo</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Piutang</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Bayar</th>
      <th colspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Sisa</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Overdue</th>
      <th rowspan="2" style="border-width:1px;border-collapse: collapse;border-style: solid;">Overdue Hari</th>
      <th colspan="4" style="border-width:1px;border-collapse: collapse;border-style: solid;">Overdue Hari</th>
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
        <th style="border-width:1px;border-collapse: collapse;border-style: solid;">90></th>
        
    </tr>
  </thead>
  <tbody>
    @foreach($company as $data)
    @php
    $receivable = $data->receivables;
    if(!empty($request->start_date))
      $receivable = $receivable->filter(function ($item) use ($request) {
          return (data_get($item, 'date_transaction') > date('Y-m-d',strtotime($request->start_date)));
      });
    if(!empty($request->end_date))
      $receivable = $receivable->filter(function ($item) use ($request) {
          return  (data_get($item, 'date_transaction') < date('Y-m-d',strtotime($request->end_date)));
      });
    
    if(!empty($request->customer_id))
      $receivable = $receivable->where('contact_id',$request->customer_id);
   
    @endphp
        @foreach($receivable->sortBy('date_transaction') as $dt)
        @php
          $date1=date_create(date('Y-m-d'));
          $date2=date_create(date('Y-m-d',strtotime($dt->date_tempo)));
          $diff=date_diff($date1,$date2);
        @endphp
        <tr>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$loop->iteration}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{date('d-m-Y',strtotime($dt->date_transaction))}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->code}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$data->name}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->contact->name}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{date('d-m-Y',strtotime($dt->date_tempo))}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($dt->debet,0,',','.')}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($dt->credit,0,',','.')}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($dt->debet-$dt->credit,0,',','.')}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{(($dt->debet-$dt->credit)/$dt->debet)*100}}</td>
            @php 
               
            @endphp
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($dt->debet-$dt->credit,0,',','.')}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$diff->days>0?$diff->days:'' }}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{$diff->days <= 30?number_format($dt->debet-$dt->credit,0,',','.'):0 }}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$diff->days > 30 && $diff->days <= 60?number_format($dt->debet-$dt->credit,0,',','.'):0 }}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$diff->days <= 90 && $diff->days > 60?number_format($dt->debet-$dt->credit,0,',','.'):0 }}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$diff->days > 90?number_format($dt->debet-$dt->credit,0,',','.'):0 }}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->type_transaction_id == $type_transaction->id ? 0:0}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{date('d-m-Y',strtotime($dt->updated_at))}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->created_by_user()->first()?$dt->created_by_user()->first()->name:''}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;"></td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;">{{$dt->type_transaction()->first()->name}}</td>

        </tr>
        @endforeach
    @endforeach
  </tbody>
</table>
@endsection
