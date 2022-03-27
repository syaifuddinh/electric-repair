
  <center><h3>LEMBAR LAMPIRAN TRUCKING<br>NO: {{$item->code}}</h3></center>
  <br>
  <br>
    <?php
      $grand_total_manifest=0;
      $manifestsChunk = $manifests->chunk(45);
      $lastChunk = count($manifestsChunk) - 1;
    ?>
    @foreach($manifestsChunk as $index => $manifests)
    <table style="width: 100%; border-collapse: collapse; border: 1px solid black; @if($index > 0) margin-top: 8%; @endif">
    <tr>
      <th class="border-bottom">No.</th>
      <th class="text-center border-bottom">No. SJ</th>
      <th class="text-center border-bottom">No. PO</th>
      <th class="text-center border-bottom">Nopol</th>
      <th class="text-center border-bottom">Tipe Kendaran</th>
      <th class="text-center border-bottom">Qty</th>
      <th class="text-center border-bottom">Nama Barang</th>
      <th class="text-center border-bottom">POL</th>
      <th class="text-center border-bottom">POD</th>
      <th class="text-center border-bottom">TARIF</th>
    </tr>
    @foreach($manifests as $key => $manifest)
       <tr>
        <td class="text-center">{{$key+1}}</td>
        <td class="text-center">{{$manifest->no_sj}}</td>
        <td class="text-center">{{$manifest->po_customer}}</td>
        <td class="text-center">{{$manifest->nopol}}</td>
        <td class="text-center">{{$manifest->name}}</td>
        <td class="text-center">{{$manifest->qty}}</td>
        <td class="text-center">{{$manifest->item_name}}</td>
        <td class="text-center">{{$manifest->city_start}}</td>
        <td class="text-center">{{$manifest->city_end}}</td>
        <td class="text-right">{{formatNumber($manifest->price)}}</td>
      </tr>
      <?php $grand_total_manifest+=$manifest->price; ?>
    @endforeach
    @if($lastChunk == $index)
    <tr style="font-weight: bold">
      <td class="text-right" colspan="1">Total</td>
      <td class="text-right" colspan="8"></td>
      <td class="text-right">{{ formatNumber($grand_total_manifest) }}</td>
    </tr>
    @endif
    </table>
    @if($lastChunk != $index)
    <div class="page-break"></div>
    @endif
    @endforeach
