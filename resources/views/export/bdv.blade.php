@extends('export.head')
@section('title','BDV')
@section('content')
<table style="width: 100%;" style="border:none">
  <thead>
    <tr>
      <td width="60%" colspan="3" scope="col">
        <div align="center"><strong>BANK DISBURSMENT VOUCHER</strong></div>
      </td>
    </tr>
  </thead>
</table>
<table style="width: 100%;border-width:1px;border-collapse: collapse;border-style: solid;">
    <thead style="text-align:center">
    <tr>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;" width="5%">No.</th>
      <th colspan="2"  style="border-width:1px;border-collapse: collapse;border-style: solid;" width="80%" >Description</th>
      <th style="border-width:1px;border-collapse: collapse;border-style: solid;text-align:right;padding-right:5px;">IDR</th>
    </tr>
  </thead>
  <tbody>
    @foreach($cost_detail as $dt)
    <tr>
        <td class="td" style="text-align:center">{{$loop->iteration}}</td>
        <td colspan="2" class="td">{{ ($dt->cost_type ? $dt->cost_type->name : '') }}</td>
        <td class="td" style="text-align:right;padding-right:5px;">{{number_format($dt->total_price,0,',','.')}}</td>
    </tr>
    @endforeach

    <tr>
        <td class="td"></td>
        <td colspan="2"></td>
        <td class="td" style="border-width: 1px;border-collapse: collapse;border-style: solid;text-align:right;padding-right:5px;">{{number_format($cost_detail->sum('total_price'),0,',','.')}}</td>

    </tr>
    <tr>
        <td style="border-width: 1px;border-collapse: collapse;border-style: solid;"></td>
        <td style="border-width: 1px;border-collapse: collapse;border-style: solid;">Informasi Saldo</td>
        <td style="border-width: 1px;border-collapse: collapse;border-style: solid;text-align:right;padding-right:5px;">IDR</td>
        <td style="border-width: 1px;border-collapse: collapse;border-style: solid;"></td>
    </tr>
    @foreach($informasi_saldo as $dt)
    <tr>
        <td></td>
        <td class="td" style="text-align:left">{{ ($dt ? $dt->name : '') }}</td>
        <td class="td" style="text-align:right;padding-right:5px;">{{number_format(($dt->jmldebet-$dt->jmlkredit),0,',','.')}}</td>
        <td>
    </tr>
    @endforeach
    <tr>
        <td colspan='3' style="border-width: 1px 0px 1px 1px;border-collapse: collapse;border-style: solid;text-align:center">Otoritas Pembayaran</td>
        <td style="border-collapse: collapse;border-style: solid;border-width:1px 1px 1px 0px;">Cilegon, {{date('d-m-Y')}}</td>
    </tr>
    <tr>
        <td></td>
        <td class="td" colspan="2" style="text-align:center">
            Disetujui Oleh,
            <br>
            <br>
            <br>
             {{-- ( $cost_detail->first() ? ($cost_detail->first()->submission_cost->approve_by_user()->first()?$cost_detail->first()->submission_cost->approve_by_user()->first()->name : '___________________' ):'_____') --}}
            ( {{($cost_detail->first()?($cost_detail->first()->submission_cost->approve_by_user->name??'___________________'):'___________________' )}} )
        </td>
        <td class="td" style="text-align:center">
            Diajukan Oleh,
            <br>
            <br>
            <br>
            {{-- $cost_detail->first() ? $cost_detail->first()->submission_cost->created_by_user()->name : '_________' --}}
            ( {{($cost_detail->first()?($cost_detail->first()->submission_cost->created_by_user->name??'_________'):'_________' )}} )
        </td>
    </tr>
  </tbody>
</table>
@endsection
