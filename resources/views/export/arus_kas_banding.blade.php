@extends('export.layout')

@section('content')
<table width="100%" class="table-borderless mepet">
  <thead>
    <tr>
      <th colspan="5" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>ARUS KAS PERBANDINGAN</strong></div>
      </th>
    </tr>
    <tr>
      <td style=""></td>
      <td style="width: 15%;"><br><br> </td>
      <td style="width: 15%;" class="text-right"><strong>Cabang :</strong>{{$company->name or 'Semua Cabang'}}<br><b>Periode:</b><br>{{$start}} s/d {{$end}}</td>
      <td style="width: 15%;"><br><br> </td>
      <td style="width: 15%;" class="text-right"><strong>Cabang :</strong>{{$company2->name or 'Semua Cabang'}}<br><b>Periode:</b><br>{{$start2}} s/d {{$end2}}</td>
    </tr>
  </thead>
  <tbody>
    @php
    $totalMutasi = 0;
    $totalMutasi2 = 0;
    $mutasiRow=0;
    $mutasiRow2=0;
    $mutasiAll=0;
    $mutasiAll2=0;
    @endphp
    @foreach($data as $i => $value)
    <tr>
      <td class=" {{$value->is_base==1?'bold':''}}">{{$value->name}}</td>
      <td class="text-right">
        <?php
        if ($value->is_base==1) {
          echo "";
        } else {
          if ($value->jenis==1 && $value->total>=0) {
            echo formatNumber($value->total);
            $mutasiRow+=$value->total;
            $mutasiAll+=$value->total;
          } elseif ($value->jenis==2 && $value->total<0) {
            echo formatNumber(abs($value->total));
            $mutasiRow+=abs($value->total);
            $mutasiAll+=abs($value->total);
          } else {
            echo formatNumber(0);
          }
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($value->is_base==1) {
          echo "";
        } else {
          if ($value->jenis==2 && $value->total>=0) {
            echo formatNumber($value->total);
            $mutasiRow-=$value->total;
            $mutasiAll-=$value->total;
          } elseif ($value->jenis==1 && $value->total<0) {
            echo formatNumber(abs($value->total));
            $mutasiRow-=abs($value->total);
            $mutasiAll-=abs($value->total);
          } else {
            echo formatNumber(0);
          }
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        $vls=$data2[$i];
        if ($vls->is_base==1) {
          echo "";
        } else {
          if ($vls->jenis==1 && $vls->total>=0) {
            echo formatNumber($vls->total);
            $mutasiRow2+=$vls->total;
            $mutasiAll2+=$vls->total;
          } elseif ($vls->jenis==2 && $vls->total<0) {
            echo formatNumber(abs($vls->total));
            $mutasiRow2+=abs($vls->total);
            $mutasiAll2+=abs($vls->total);
          } else {
            echo formatNumber(0);
          }
        }
        ?>
      </td>
      <td class="text-right">
        <?php
        if ($vls->is_base==1) {
          echo "";
        } else {
          if ($vls->jenis==2 && $vls->total>=0) {
            echo formatNumber($vls->total);
            $mutasiRow2-=$vls->total;
            $mutasiAll2-=$vls->total;
          } elseif ($vls->jenis==1 && $vls->total<0) {
            echo formatNumber(abs($vls->total));
            $mutasiRow2-=abs($vls->total);
            $mutasiAll2-=abs($vls->total);
          } else {
            echo formatNumber(0);
          }
        }
        ?>
      </td>
    </tr>
    @if( ( isset($data[$i+1]) && $data[$i+1]->kategori!=$value->kategori ) || end($data)->id==$value->id)
    <tr>
      <td class="bold">Kas Tersedia dari {{$aktivitas[$value->kategori]}}</td>
      <td style="border-top: 1px solid;border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-top: 1px solid;border-bottom: 2px solid;">{{formatNumber($mutasiRow)}}</td>
      <td style="border-top: 1px solid;border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-top: 1px solid;border-bottom: 2px solid;">{{formatNumber($mutasiRow2)}}</td>
    </tr>
    <?php
    $mutasiRow=0;
    $mutasiRow2=0;
     ?>
    @endif
    @endforeach
    <tr>
      <td class="bold">Kenaikan (Penurunan) Kas Dan Setara Kas</td>
      <td style="border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-bottom: 2px solid;">{{formatNumber($mutasiAll)}}</td>
      <td style="border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-bottom: 2px solid;">{{formatNumber($mutasiAll2)}}</td>
    </tr>
    <tr>
      <td class="bold" colspan="5">Kas dan Setara Kas Awal Periode</td>
    </tr>
    <?php $totalSetara=0;$totalSetara2=0; ?>
    @foreach($data_kas as $i => $value)
    <?php
    $vls=$data_kas[$i];
     ?>
    <tr>
      <td>{{$value->name}}</td>
      <td class="text-right">{{formatNumber($value->total)}}</td>
      <td></td>
      <td class="text-right">{{formatNumber($vls->total)}}</td>
      <td></td>
    </tr>
    <?php $totalSetara+=$value->total;$totalSetara2+=$vls->total; ?>
    @endforeach
    <tr>
      <td class="bold">Jumlah Awal Periode</td>
      <td style="border-top: 1px solid;"></td>
      <td class="text-right bold" style="border-top: 1px solid;">{{formatNumber($totalSetara)}}</td>
      <td style="border-top: 1px solid;"></td>
      <td class="text-right bold" style="border-top: 1px solid;">{{formatNumber($totalSetara2)}}</td>
    </tr>
    <tr>
      <td class="bold">Kas Dan Setara Kas Akhir Periode</td>
      <td style="border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-bottom: 2px solid;">{{formatNumber($totalSetara+$mutasiAll)}}</td>
      <td style="border-bottom: 2px solid;"></td>
      <td class="text-right bold" style="border-bottom: 2px solid;">{{formatNumber($totalSetara2+$mutasiAll2)}}</td>
    </tr>

  </tbody>
</table>
@endsection
