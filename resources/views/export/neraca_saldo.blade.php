@extends('export.layout')

@section('content')
<style type="text/css">
  table tr th, td {
    padding: 5px;
  }
</style>
<table width="100%" class="table-borderless">
  <thead>
    <tr>
      <th colspan="4" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>NERACA SALDO</strong></div>
      </th>
    </tr>
    <tr>
      <td style="width: 15%;"><strong>Cabang :</strong><br>{{$company->name or 'Semua Cabang'}}</td>
      <td style="width: 50%;"><br><br> </td>
      <td colspan="2" class="text-right"><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
    </tr>
  </thead>
  <tbody>
    @php
    $totdb = 0;
    $totcr = 0;
    @endphp
    @foreach($data as $value)
    <tr>
      <td class=" {{$value->is_base==1?'bold':''}} text-left">{{$value->code}}</td>
      <td class=" {{$value->is_base==1?'bold':''}}">{!! menjorok($value->deep).$value->name !!}</td>
      <td class="text-right">
        <?php
        if ($value->jenis==1 && $value->total>=0) {
          echo formatNumber($value->total);
          $totdb+=$value->total;
        } elseif ($value->jenis==2 && $value->total<0) {
          echo formatNumber(abs($value->total));
          $totdb+=abs($value->total);
        } else {
          echo formatNumber(0);
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value->jenis==2 && $value->total>=0) {
          echo formatNumber($value->total);
          $totcr+=$value->total;
        } elseif ($value->jenis==1 && $value->total<0) {
          echo formatNumber(abs($value->total));
          $totcr+=abs($value->total);
        } else {
          echo formatNumber(0);
        }
        ?>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="2" class="">Total</td>
      <td class="text-right">{{number_format($totdb)}}</td>
      <td class="text-right">{{number_format($totcr)}}</td>
    </tr>
  </tbody>
</table>
@endsection
