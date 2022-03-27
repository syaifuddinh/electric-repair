<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <style>
    * {
      font-family: Baskerville, "Palatino Linotype", Palatino, "Century Schoolbook L", "Times New Roman", "serif"
    }

    pre {
      font-size: 3mm;
      margin: 0
    }

    thead td {
      font-size: 1mm
    }
  </style>
  <style type="text/css" media="print">
    @page {
      size: auto;
      margin: 0;
    }
  </style>
</head>

<body>
  <div style="width:100%;text-align:right">
    <strong>
      <u>BUKTI SERAH TERIMA BARANG</u><br>
      No : {{ $item->prefix }} {{ $item->suffix }}
    </strong>
  </div>
  <header style='display:flex;justify-content: space-between'>
    <div>
    <br><br>
    <pre style="font-size: 4mm">
        <span style="display:inline-block;width: 35mm">Pengirim</span>: {{ @$item->sender }}
        <span style="display:inline-block;width: 35mm">Alamat</span>: {{ @$item->customer->address ?? null }}
        <span style="display:inline-block;width: 35mm">Nama / HP</span>: ....................
        {{-- <span style="display:inline-block;width: 35mm">Tipe Kemasan</span>: {{ @$item->package }} --}}
    </pre>
    </div>
    <div>
      <pre style="font-size: 4mm"><br>
        <span style="display:inline-block;width: 25mm">Penerima</span>: {{ @$item->receiver }}
        <span style="display:inline-block;width: 25mm">Alamat</span>: {{ @$item->city_to == 'undefined' ? '' : @$item->city_to }}
        <span style="display:inline-block;width: 25mm">Nama / HP</span>: ....................
      </pre>
    </div>
  </header>
  <table border='1' style="width: 100%; border-left: transparent; border-right :transparent" cellspacing='0'>
    <tr>
      <td rowspan="2"><b>No.</b></td>
      <td rowspan="2"><b>Jenis Barang</b></td>
      <td rowspan="2"><b>Koli</b></td>
      <td rowspan="2"><b>Kemasan</b></td>
      <td colspan="4" style="text-align :center"><b>Ukuran / Berat</b></td>
      <td>&nbsp;</td>
      <td rowspan="2"><b>Keterangan</b></td>
    </tr>
    <tr>
      <td colspan="1" style="text-align :center">P</td>
      <td colspan="1" style="text-align :center">L</td>
      <td colspan="1" style="text-align :center">T</td>
      <td colspan="1" style="text-align :center">Ton</td>
      <td colspan="1" style="text-align :center">M3</td>
    </tr>
    <?php
        $total = 0;
        $volume = 0;
        $totalVolume =0;
      ?>
      @foreach($detail as $x => $unit)
      <?php
        $total += $unit->qty;
        $volume_base = $unit->long*$unit->wide*$unit->high*$unit->qty / 1000000;
        $volume = sprintf('%.9F', $volume_base);
        $totalVolume += $volume_base;
      ?>
    <tr>
      <td>
        <pre style="font-size: 4mm">{{ $x + 1 }}</pre>
      </td>
      <td>{{ $unit->item_name }}</td>
      <td>{{ formatNumber($unit->qty)}}</td>
      <td>{{ $unit->kemasan }}</td>
      <td style="text-align :center">{{ $unit->long }}</td>
      <td style="text-align :center">{{ $unit->wide }}</td>
      <td style="text-align :center">{{ $unit->high }}</td>
      <td style="text-align :center">{{ $unit->weight }}</td>
      <td style="text-align :center">{{ round($volume, 3) }} m<sup>3</sup></td>
      <td>&nbsp;</td>
    </tr>
    @endforeach
    <tr>
      <td colspan="4" style="text-align :center; padding-left: 20mm"><b>Total {{ formatNumber($total) }} Koli</b></td>
      <td colspan="8" style="text-align :center; padding-left :15mm"><b>{{ sprintf('%.3F', $totalVolume) }} m<sup>3</sup></b></td>
    </tr>
  </table>

  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 1mm">
    <tbody>
      <tr style="font-weight: bold" style="border-bottom: transparent">
        <td colspan="1" style='font-size: 4mm;'><u>Pembawa</u></td>
        <td scope="col" style='font-size: 4mm'><u>Kepala Gudang</u></td>
        <td scope="col" style='text-align: center;font-size: 4mm'><u>Tgl: {{ dateView($item->receive_date) }}</u>
        </td>
        <td scope="col" style='text-align: center;font-size: 4mm'><u>Tallyman</u></td>
      </tr>
      <tr style="font-size: 4mm">
        <td>Nama : {{ @$item->driver }}
        </td>
        <td rowspan="3" valign="top" style="width: 5cm">
          <div style="width: 4cm"> TTD </div>
          @if($item->ttd)
          <img src="{{ $item->ttd }}" style="width:50mm;height:auto">
          @endif
        </td>
        <td rowspan="3" style="text-align:center">Jam Masuk: {{ explode(' ', $item->receive_date)[1] }}<br>
          Jam Keluar: {{ $item->stripping_done ? explode(' ', $item->stripping_done)[1] : '' }}
        </td>
      </tr>
      <tr style="font-size: 4mm">
        <td>
          No HP : {{ $item->phone_number }}
        </td>
        <td rowspan="3" style="text-align:center">{{ $item->staff->name }}
        </td>
      </tr>
      <tr style="font-size: 4mm">
        <td>Nopol : {{ $item->nopol }}</td>
      </tr>

    </tbody>
  </table>
  <hr>

  <table width='100%' border="0" style="font-size: 12px">
    <tr>
      <td>Isi barang tidak di periksa. Pengirim telah setuju dengan KSAB yang tertera di balik halaman TTB</td>
    </tr>
    <tr>
      <td width='50%'>Kirim Pertanyaan Anda ke SMS/ WA Center <b style="font-size: 15px">{{ @$item->company->phone }}</b></td>
      <td>printed: {{ now() }}</td>
      <td style="text-align: right">Halaman 1 dari 1</td>
    </tr>
  </table>

  <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
  <script src="{{asset('js/jSignature.js')}}"></script>
  <script src="{{asset('js/jSignature.CompressorBase30.js')}}"></script>
  <script src="{{asset('js/jSignature.CompressorSVG.js')}}"></script>
  <script>
    $(".signature").jSignature({
      height: 200
    });
    $(".signature").jSignature('setData', {
      !!$item - > ttd!!
    }, 'native');
    var base64 = $('.signature').jSignature('getData', 'default');
    var img = $('<img>');
    img.attr('src', base64);
    img.css('width', '5cm');
    img.css('height', 'auto');
    $('.signature').replaceWith(img);
  </script>
</body>

</html>
<script>
  window.print();
</script>