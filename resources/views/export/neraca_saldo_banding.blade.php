@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="6" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>NERACA SALDO PERBANDINGAN</strong></div>
      </th>
    </tr>
    <tr>
      <td style="width: 15%;" rowspan="2" class="bold">Kode Akun</td>
      <td style="width: 40%;" rowspan="2" class="bold">Nama Akun</td>
      <td colspan="2"><b><strong>Cabang :</strong>{{$company1->name}}</td>
      <td colspan="2"><b><strong>Cabang :</strong>{{$company2->name}}</td>
    </tr>
    <tr>
      <td colspan="2"><b>Periode:</b><br>{{$start1}} s/d {{$end1}}</td>
      <td colspan="2"><b>Periode:</b><br>{{$start2}} s/d {{$end2}}</td>
    </tr>
  </thead>
  <tbody>
    @php
    $totdb = 0;
    $totcr = 0;
    $totdb2 = 0;
    $totcr2 = 0;
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
      <td class="text-right">
        <?php
        if ($value->jenis==1 && $value->tot_db2>=0) {
          echo number_format(abs($value->tot_db2));
          $totdb2+=abs($value->tot_db2);
        } elseif ($value->jenis==2 && $value->tot_cr2<0) {
          echo number_format(abs($value->tot_cr2));
          $totdb2+=abs($value->tot_cr2);
        } else {
          echo 0;
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value->jenis==2 && $value->tot_cr2>=0) {
          echo number_format(abs($value->tot_cr2));
          $totcr2+=abs($value->tot_cr2);
        } elseif ($value->jenis==1 && $value->tot_db2<0) {
          echo number_format(abs($value->tot_db2));
          $totcr2+=abs($value->tot_db2);
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
      <td class="text-right">{{number_format($totdb2)}}</td>
      <td class="text-right">{{number_format($totcr2)}}</td>
    </tr>
  </tbody>
</table>
@endsection
