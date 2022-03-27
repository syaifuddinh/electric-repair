@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="6" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>POSISI KEUANGAN</strong></div>
      </th>
    </tr>
    <tr>
      <th><b>Cabang :</b><br>{{$company->name or 'Semua Cabang'}} </th>
      <th colspan="4"></th>
      <th>Periode:<br>{{$start}} s/d {{$end}} </th>
    </tr>
  </thead>
  <tbody>
    <?php $total_aktiva=0;$total_pasiva=0; ?>
    <tr>
      <td width="15%" class="bold">Kode</td>
      <td class="bold">Nama Akun</td>
      <td width="15%" class="bold">Nominal</td>
      <td width="15%" class="bold">Kode</td>
      <td class="bold">Nama Akun</td>
      <td width="15%" class="bold">Nominal</td>
    </tr>
    @for($i=0; $i < 1000; $i++)
    <?php if (empty($data['aktiva'][$i]) && empty($data['pasiva'][$i])) {
      break;
    } ?>
    <tr>
      @if(isset($data['aktiva'][$i]))
      <td class="{{$data['aktiva'][$i]['is_base']==1?'bold':''}}">{{$data['aktiva'][$i]['code']}}</td>
      <td class="{{$data['aktiva'][$i]['is_base']==1?'bold':''}}">{!! menjorok($data['aktiva'][$i]['deep']).$data['aktiva'][$i]['name'] !!}</td>
      <td>{{$data['aktiva'][$i]['is_base']!=1?formatNumber($data['aktiva'][$i]['amount']):''}}</td>
      <?php $total_aktiva+=$data['aktiva'][$i]['amount']; ?>
      @else
      <td></td>
      <td></td>
      <td></td>
      @endif
      @if(isset($data['pasiva'][$i]))
      <td class="{{$data['pasiva'][$i]['is_base']==1?'bold':''}}">{{$data['pasiva'][$i]['code']}}</td>
      <td class="{{$data['pasiva'][$i]['is_base']==1?'bold':''}}">{!! menjorok($data['pasiva'][$i]['deep']).$data['pasiva'][$i]['name'] !!}</td>
      <td>{{$data['pasiva'][$i]['is_base']!=1?formatNumber($data['pasiva'][$i]['amount']):''}}</td>
      <?php $total_pasiva+=$data['pasiva'][$i]['amount']; ?>
      @else
      <td></td>
      <td></td>
      <td></td>
      @endif
    </tr>
    @endfor
    <tr>
      <td colspan="2">JUMLAH AKTIVA</td>
      <td>{{formatNumber($total_aktiva)}}</td>
      <td colspan="2">JUMLAH PASIVA</td>
      <td>{{formatNumber($total_pasiva)}}</td>
    </tr>
  </tbody>
</table>

@endsection
