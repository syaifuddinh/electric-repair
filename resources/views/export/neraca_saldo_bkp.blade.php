@extends('export.layout')

@section('content')
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
        if ($value->jenis==1 && $value->tot_db>=0) {
          echo number_format(abs($value->tot_db));
          $totdb+=abs($value->tot_db);
        } elseif ($value->jenis==2 && $value->tot_cr<0) {
          echo number_format(abs($value->tot_cr));
          $totdb+=abs($value->tot_cr);
        } else {
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value->jenis==2 && $value->tot_cr>=0) {
          echo number_format(abs($value->tot_cr));
          $totcr+=abs($value->tot_cr);
        } elseif ($value->jenis==1 && $value->tot_db<0) {
          echo number_format(abs($value->tot_db));
          $totcr+=abs($value->tot_db);
        } else {
          echo 0;
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
