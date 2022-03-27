@extends('export.layout')

@section('content')
<table width="100%" class="utama">
  <thead>
    <tr>
      <th colspan="3" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>LABA RUGI</strong></div>
      </th>
    </tr>
    <tr>
      <td style="width: 15%;"><strong>Cabang :</strong><br>{{$company->name or 'Semua Cabang'}}</td>
      <td></td>
      <td style="width: 25%;" colspan="1"><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
    </tr>
  </thead>
  <tbody>
    <?php
    $labarugi=0;
    $jmlamountotal=0;
    $jmlamount=0;
    $jenis=1;
    $nama="";
    $nama2="";
    $kedalaman=1;
    ?>
   @foreach($data as $i => $value)
   <?php
   $labarugi+=$value['amount'];
   $kedalaman=$value['deep'];
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
     <td>
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
   </tr>
   <?php $next=(isset($data[$i+1])?$data[$i+1]:null); ?>
   @if(isset($next))
    @if($next['is_base']==1 && $kedalaman>0)
    <tr>
      <td></td>
      <td class="bold">Jumlah {{$nama}}</td>
      <td class="bold">{{formatNumber($jmlamount)}}</td>
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
      <td class="bold">{{formatNumber($jmlamountotal)}}</td>
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
     <td class="bold">{{formatNumber($jmlamount)}}</td>
   </tr>
   @endif
   @endforeach
   <tr>
     <td></td>
     <td class="bold">Jumlah {{$labarugi>=0?"LABA":"RUGI"}}</td>
     <td class="bold">{{formatNumber($labarugi)}}</td>
   </tr>
  </tbody>
</table>
@endsection
