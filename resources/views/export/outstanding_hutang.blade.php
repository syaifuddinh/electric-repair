@extends('export.head')
@section('title','Laporan Outstanding Hutang ')
@section('content')
<table style="width: 100%;" style="border:none">
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
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">No.</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;"  >Tanggal</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Tgl Jatuh Tempo</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Umur Hutang</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">No Transaksi</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Sumber Transaksi</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Company</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Supplier</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;">Total Hutang</th>
    </tr>
  </thead>
  <tbody>
    @foreach($company as $data)
    @php
    $payables = $data->payables;
    if(!empty($request->start_date))
      $payables = $payables->filter(function ($item) use ($request) {
          return (data_get($item, 'date_transaction') > date('Y-m-d',strtotime($request->start_date)));
      });
    if(!empty($request->end_date))
      $payables = $payables->filter(function ($item) use ($request) {
          return  (data_get($item, 'date_transaction') < date('Y-m-d',strtotime($request->end_date)));
      });
    
    if(!empty($request->customer_id))
      $payables = $payables->where('contact_id',$request->customer_id);
    
    @endphp
        @foreach($payables->sortBy('date_transaction') as $dt)
        @php
          $date1=date_create(date('Y-m-d'));
          $date2=date_create(date('Y-m-d',strtotime($dt->date_tempo)));
          $diff=date_diff($date1,$date2);
        @endphp
        <tr>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$loop->iteration}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{date('d-m-Y',strtotime($dt->date_transaction))}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{date('d-m-Y',strtotime($dt->date_tempo))}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$diff->days}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->code}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;">{{$dt->type_transaction()->first()->name}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$data->name}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$dt->contact->name}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($dt->credit-$dt->debet,0,',','.')}}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan=8 style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:center">{{$loop->iteration}}</td>
            <td style="border-left-width:1px;border-collapse: collapse;border-style: solid;text-align:right">{{number_format($payables->sum('credit')-$payables->sum('debet'),0,',','.')}}</td>
        </tr>
    @endforeach
  </tbody>
</table>
@endsection
