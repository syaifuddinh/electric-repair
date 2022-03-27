@extends('export.head')
@section('title','Laporan Laba Rugi Perbandingan')
@section('content')
<table style="width: 100%;">
  <thead>
    <tr>
      <td width="60%" colspan="3" scope="col">
        <div align="center"><strong>LABA RUGI PERBANDINGAN</strong></div>
      </td>
    </tr>
  </thead>
</table>
<table width="100%" style="border:0px;" cellspacing="0" cellpadding="0">
  <thead >
    <tr>
      <th class="th"></th>
      <th class="th"></th>
      <th class="th" style="width: 25%;" colspan="1"><b>Cabang :</b>{{$company->name or 'Semua Cabang'}}<br><b>Periode:</b> <br>{{$request->start_date}} s/d {{$request->end_date}}</th>
      <th class="th"><b>Cabang :</b>{{$company_perbandingan->name or 'Semua Cabang'}}<br><b>Periode:</b> <br>{{$request->start_date_perbandingan}} s/d {{$request->end_date_perbandingan}}</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $labarugi=0;
    $labarugipembanding=0;
    $jmlamountotal=0;
    $jmlamountotalpembanding=0;
    $jmlamount=0;
    $jmlamountpembanding=0;
    $jenis=1;
    $nama="";
    $nama2="";
    $kedalaman=1;
    ?>
   @foreach($data as $i => $value)
   <?php
   $labarugi+=$value['amount'];
   $kedalaman=$value['deep'];
   $labarugipembanding+=$perbandingan[$i]['amount'];
   ?>
   <tr>
     <td class="{{$value['is_base']==1?'bold':''}}">{{$value['code']}}
       <?php
       if ($value['is_base']==1) {
         $nama=$value['name'];
       }
        ?>
     </td>
     <td class="{{$value['is_base']==1?'bold':''}}">
       <?php
       if ($value['is_base']==1) {
         echo menjorok($value['deep']).$value['name'];
         $nama=$value['name'];
         if ($kedalaman==0) {
           $jenis=$value['jenis'];
           $nama2=$value['name'];
         }
       } else {
         echo menjorok($value['deep']).$value['name'];
       }
       ?>
     </td>
     <td style="text-align:right">
       <?php
       if ($value['is_base']==1) {
         echo "";
       } else {
         echo formatNumber($value['amount']);
         $jmlamountotal+=$value['amount'];
         $jmlamount+=$value['amount'];
       }
       ?>
     </td>
     <td style="text-align:right">
       <?php
       if ($value['is_base']==1) {
         echo "";
       } else {
         echo formatNumber($perbandingan[$i]['amount']);
         $jmlamountotalpembanding+=$perbandingan[$i]['amount'];
         $jmlamountpembanding+=$perbandingan[$i]['amount'];
       }
       ?>
     </td>
   </tr>
   <?php $next=(isset($data[$i+1])?$data[$i+1]:null); ?>
   @if(isset($next))
    @if($next['is_base']==1 && $kedalaman>0)
    <tr>
      <td></td>
      <td class="bold">Jumlah {{$nama}}</td>
      <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamount)}}</td>
      <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamountpembanding)}}</td>
    </tr>
    <?php
    $jmlamount=0;
    $nama="";
     ?>
    @endif
    @if($next['is_base']==1 && $next['deep']==0 && $i>1)
    <tr>
      <td></td>
      <td class="bold">Jumlah {{$nama2}}</td>
      <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamountotal)}}</td>
      <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamountotalpembanding)}}</td>
    </tr>
    <?php
    $jmlamountotal=0;
    $nama2="";
     ?>
    @endif
   @else
   <tr>
     <td></td>
     <td class="bold">Jumlah {{$nama}}</td>
     <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamount)}}</td>
     <td class="border-top bold" style="text-align:right">{{formatNumber($jmlamountpembanding)}}</td>
   </tr>
   @endif
   @endforeach
   <tr>
     <td></td>
     <td >Jumlah {{$labarugi>=0?"LABA":"RUGI"}}</td>
     <td class="border-top bold" style="text-align:right">{{formatNumber($labarugi)}}</td>
     <td class="border-top bold" style="text-align:right">{{formatNumber($labarugipembanding)}}</td>
   </tr>
  </tbody>
</table>
@endsection
