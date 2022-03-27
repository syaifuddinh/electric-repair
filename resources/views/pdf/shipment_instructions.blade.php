@extends('export.layout')

@section('content')
<div style="position: fixed; width: 98%; height: 9%;">
</div>
<div style="margin:auto; width:80%;">
  <div style="height:9%"> </div>
  <div style="text-align:center;">
    <span style="font-size: 25px; text-decoration: underline;" class="font-bold">SHIPPING INSTRUCTION</span><br><span class="font-bold">{{$code}}</span>
  </div>
  <div style="height:9%"> </div>
  <table class="table table-borderless va--top" style="width:100%;">
    <tbody>
      <tr>
        <td width="40%" >TO</td>
        <td width="5px">:</td>
        <td style="text-align:left;">{{@$to->name}}</td>
      </tr>
      <tr>
        <td>ATTN</td>
        <td>:</td>
        <td>{{$attn}}</td>
      </tr>
      <tr>
        <td>SHIPPER</td>
        <td>:</td>
        <td>{{@$shipper->name}} <br> {{@$shipper->address}}</td>
      </tr>
      <tr>
        <td>CONSIGNEE</td>
        <td>:</td>
        <td>{{@$consignee->name}} <br> {{@$consignee->address}}</td>
      </tr>
      <tr>
        <td style="height:20px;"></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>NOTIFY PARTY</td>
        <td>:</td>
        <td>{{$notify_party}}</td>
      </tr>
      <tr>
        <td style="height:20px;"></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>PORT OF LOADING</td>
        <td>:</td>
        <td>{{@$pol->name}}</td>
      </tr>
      <tr>
        <td>PORT OF DISCHARGE</td>
        <td>:</td>
        <td>{{@$pod->name}}</td>
      </tr>
      <tr>
        <td>DESCRIPTION OF GOODS</td>
        <td>:</td>
        <td>{{@$commodity->name}}</td>
      </tr>
      <tr>
        <td>WEIGHT</td>
        <td>:</td>
        <td><span>N.W. = {{formatNumber($nw)}}</span><br><span>G.W. = {{formatNumber($gw)}}</span> </td>
      </tr>
      <tr>
        <td>MEASSUREMENT</td>
        <td>:</td>
        <td>{{$meassurement}}</td>
      </tr>
      <tr>
        <td>FREIGHT</td>
        <td>:</td>
        <td>{{$freight}}</td>
      </tr>
      <tr>
        <td>VESSEL</td>
        <td>:</td>
        <td>{{@$vessel->name}}</td>
      </tr>
      <tr>
        <td>ETD</td>
        <td>:</td>
        <td>{{$etd}}</td>
      </tr>
      <tr>
        <td>ETA</td>
        <td>:</td>
        <td>{{$eta}}</td>
      </tr>
      <tr>
        <td>PARTY</td>
        <td>:</td>
        <td>{{$qty}} X {{@$container_type->full_name}}</td>
      </tr>
      <tr>
        <td>TGL STUFFING</td>
        <td>:</td>
        <td>{{$stuffing_date}}</td>
      </tr>
      <tr>
        <td colspan="3" style="height:50px;"></td>
      </tr>
    </tbody>
  </table>
  <table style="width: 100%">
    <tr>
      <td style="width: 50%">
        <!-- Cabang Invoice, Tanggal Invoice -->
        {{$auth->company->city->name}}, {{date('d F Y')}} <br><span class="font-bold">SOLOG</span><br><br><br><br><br><br>
        {{$auth->name}}<br>
      </td>
      <td style="width: 20%"></td>
      <td style="width: 30%">
      </td>
    </tr>
  </table>
</div>
@endsection
