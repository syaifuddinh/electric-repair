@extends('export.layout')

@section('content')
<table width="100%" class="borderless">
  <thead>
    <tr>
      <th colspan="3" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>EKUITAS</strong></div>
      </th>
    </tr>
    <tr>
      <td style="width: 15%;"><strong>Cabang :</strong><br>{{$company->name or 'Semua Cabang'}}</td>
      <td></td>
      <td style="width: 15%;" colspan="1"><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
    </tr>
    <tr>
      <td>Kode Akun</td>
      <td>Nama Akun</td>
      <td>Nominal</td>
    </tr>
  </thead>
  <tbody>
    @php
      $total_all = 0;
    @endphp
    @foreach($data as $key => $value)
    <tr>
      <td class=" {{$value['is_base']==1?'bold':''}} text-left">{{$value['code']}}</td>
      <td class=" {{$value['is_base']==1?'bold':''}}">{!! menjorok($value['deep']).$value['name'] !!}</td>
      <td class="text-right">
        {{formatNumber($value['amount'])}}
      </td>
    </tr>
    <?php
    $total_all+=$value['amount'];
    ?>
    @endforeach
    <tr>
      <td></td>
      <td class="bold">Saldo Akhir</td>
      <td class="text-right">{{formatNumber($total_all)}}</td>
    </tr>
  </tbody>
</table>
@endsection
