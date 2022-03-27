@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="4" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>LABA RUGI</strong></div>
      </th>
    </tr>
    <tr>
      <td style="width: 15%;"><strong>Cabang :</strong><br>{{$company->name or 'Semua Cabang'}}</td>
      <td></td>
      <td colspan="2" style="width: 25%;" colspan="1"><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
    </tr>
  </thead>
  <tbody>
    @php
      $total_all = 0;
      $lastEl = end($data);
      $tot_textSub="";
      $totSub=0;
    @endphp
    @foreach($data as $key => $value)
    <?php
    if ($value['is_base']==1 && $value['deep']==0) {
      $tot=0;
      $tot_text=$value['name'];
    }
    if ($value['is_base']==1 && $value['deep']>0) {
      $totSub=0;
      $tot_textSub=$value['name'];
    }

    ?>
    <tr>
      <td class=" {{$value['is_base']==1?'bold':''}} text-left">{{$value['code']}}</td>
      <td class=" {{$value['is_base']==1?'bold':''}}">{!! menjorok($value['deep']).$value['name'] !!}</td>
      <td>
        {{ abs($value['amount']) }}
      </td>
      <td></td>
    </tr>
    <?php
    $total_all+=$value['amount'];
    $tot+=$value['amount'];
    $totSub+=$value['amount'];
    ?>
    @if(isset($data[$key+1]) && $data[$key+1]['is_base']==1 && $data[$key+1]['deep']==0 )
    <tr>
      <td></td>
      <td colspan="2" class="bold">Total {{$tot_text}}</td>
      <td>{{formatNumber($tot)}}</td>
    </tr>
    @elseif($value['id']==end($data)['id'])
    <tr>
      <td></td>
      <td colspan="2" class="bold">Total {{$tot_text}}</td>
      <td>{{formatNumber($tot)}}</td>
    </tr>
    @endif
    @endforeach
    <tr>
      <td></td>
      <td colspan="2" class="bold">TOTAL {{$total_all<0?"RUGI":"LABA"}}</td>
      <td>{{formatNumber($total_all)}}</td>
    </tr>
  </tbody>
</table>
@endsection
