<center><h3>LEMBAR LAMPIRAN TRUCKING<br>NO: {{$item->code}}</h3></center>
<br>
<br>
  <?php
    $grand_total_manifest=0;
    $manifestsChunk = $manifests->chunk(45);
    $lastChunk = count($manifestsChunk) - 1;
  ?>
  @foreach($manifestsChunk as $index => $manifests)
  <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
    <tr>
      <th class="border-bottom">No Urut.</th>
      <th class="border-bottom">No Container</th>
      <th class="text-center border-bottom">Size</th>
      <th class="text-center border-bottom">POL</th>
      <th class="text-center border-bottom">POD</th>
      <th class="text-center border-bottom">TARIF</th>
    </tr>
    @foreach($manifests as $key => $manifest)
    <?php $manifestDetail = $manifest->details; ?>
      @foreach($manifestDetail as $detail)
      <?php $grand_total_manifest+=$detail->job_order_detail->total_price; ?>
      <tr>
        <td class="text-center border-bottom">{{$key+1}}</td>
        <td class="text-center border-bottom">{{@$manifest->container->container_no}}</td>
        <td class="text-center border-bottom">{{@$manifest->container->container_type->size}}</td>
        <td class="text-center border-bottom">{{@$manifest->route->from->name}}</td>
        <td class="text-center border-bottom">{{@$manifest->route->to->name}}</td>
        <td class="text-right border-bottom">{{ formatNumber($detail->job_order_detail->total_price) }}</td>
      </tr>
      @endforeach
    @endforeach
  @if($lastChunk == $index)
  <tr style="font-weight: bold">
    <td class="text-right" colspan="1">Total</td>
    <td class="text-right" colspan="4"></td>
    <td class="text-right">{{ formatNumber($grand_total_manifest) }}</td>
  </tr>
  @endif
  </table>
  <div class="page-break"></div>
  @endforeach
</table>
