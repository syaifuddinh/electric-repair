@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="4" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>EKUITAS PERBANDINGAN</strong></div>
      </th>
    </tr>
    <tr>
      <td rowspan="2" style="width: 15%;" class="bold">Kode Akun</td>
      <td rowspan="2" class="bold">Nama Akun</td>
      <td style="width: 20%;" colspan="1"><strong>Cabang :</strong><br>{{$company->name or 'Semua Cabang'}}</td>
      <td style="width: 20%;" colspan="1"><strong>Cabang :</strong><br>{{$company2->name or 'Semua Cabang'}}</td>
    </tr>
    <tr>
      <td style="width: 20%;" colspan="1"><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
      <td style="width: 20%;" colspan="1"><b>Periode:</b><br>{{$start2}} s/d {{$end2}}</td>
    </tr>
  </thead>
  <tbody>
    @php
      $total_all = 0;
      $total_all2 = 0;
    @endphp
    @foreach($data as $key => $value)
    <tr>
      <td class=" {{$value['is_base']==1?'bold':''}} text-left">{{$value['code']}}</td>
      <td class=" {{$value['is_base']==1?'bold':''}}">{!! menjorok($value['deep']).$value['name'] !!}</td>
      <td class="text-right">
        {{formatNumber($value['amount'])}}
      </td>
      <td class="text-right">
        {{formatNumber($value['amount2'])}}
      </td>
    </tr>
    <?php
    $total_all+=$value['amount'];
    $total_all2+=$value['amount2'];
    ?>
    @endforeach
    <tr>
      <td></td>
      <td class="bold">Saldo Akhir</td>
      <td class="text-right">{{formatNumber($total_all)}}</td>
      <td class="text-right">{{formatNumber($total_all2)}}</td>
    </tr>
  </tbody>
</table>
@endsection
