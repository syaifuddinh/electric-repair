<?php
$item2=$lamp['item'];
$addon=$lamp['details'][0]['addon'] ?? (object) [];
$detail=$lamp['details'][0] ??  [];
$pol_pod=$lamp['pol_pod'] ?? (object) [];
?>
<center><h3>LAMPIRAN INVOICE<br>{{$item2->code}}</h3></center>
<br>
<br>
<table style="width: 100%">
	<tr>
		<td style="width: 10%">To</td>
		<td style="width: 1%">:</td>
		<td style="width: 45%;">{{$item2->customer->name}}</td>
		<td style="width: 15%">Lampiran</td>
		<td style="width: 1%">:</td>
		<td style="width: 30%">{{ $lampkey+1 }}</td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td style="padding-right: 50px;">{{$item2->customer->address}}</td>
		<td>Date</td>
		<td>:</td>
		<td>{{Carbon\Carbon::parse($item2->date_invoice)->format('d M Y')}}</td>
	</tr>
	<tr>
		<td>Attn</td>
		<td>:</td>
		<td>Finance</td>
		<td>No. WO</td>
		<td>:</td>
		<td>{{ $addon->code_wo ?? '' }}</td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td>No. BL</td>
		<td>:</td>
		<td>{{$addon->bl ?? ''}}</td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td>No. AJU</td>
		<td>:</td>
		<td>{{$addon->aju ?? ''}}</td>
	</tr>
	<?php if ($pol_pod->vessel ?? null): ?>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td>Vessel</td>
			<td>:</td>
			<td>{{$pol_pod->vessel.' '.$pol_pod->voyage}}</td>
		</tr>
	<?php endif; ?>
</table>
<br>
<table style="width: 100%; border-collapse: collapse; border: 1px solid black; border-left: none; border-right: none">
	<tr>
		<th class="border-bottom"></th>
		<th style="width: 5%;" class="border-bottom"></th>
		<th style="width: 25%;" class="text-center border-bottom">Description</th>
		<th class="text-center border-bottom">Qty</th>
		<th class="text-center border-bottom">Price</th>
		<th class="text-center border-left border-bottom">AMOUNT IDR</th>
	</tr>
	<tr>
		<td colspan="5" style="height:5px;" class="border-right"></td>
		<td></td>
	</tr>

	<!-- freight forwading = (service_type_id != 3), (trucking = 3 & container) -->
	<?php $ppn=0;$subtotal=0;$discount=0;$total=0;$counting=1; ?>
	<?php if (count($detail['detail_ff'] ?? [])>0): ?>
		<tr>
			<td class="">A</td>
			<td colspan="2" class="font-bold">Freight Forwarding</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left"></td>
		</tr>
		<?php foreach ($detail['detail_ff'] as $key => $value): ?>
			<tr>
				<td class="text-center">{{$counting}}</td>
				<td colspan="2" class="">{{$value->service}}</td>
				<td class="text-center">{{$value->qty.' x '.$value->piece}}</td>
				<td class="text-right">{{formatNumber($value->price)}}</td>
				<td class="text-right border-left">{{formatNumber($value->total_price)}}</td>
			</tr>
			<?php $ppn+=$value->ppn;$subtotal+=$value->total_price;$discount+=$value->discount;$counting++; ?>
		<?php endforeach; ?>
		<?php if (array_key_exists('detail_other',$detail)): ?>
			<?php foreach ($detail['detail_other'] as $key => $value): ?>
				<?php if (like_match('fee port%', $value->name)): ?>
					<tr>
						<td class="text-center">{{$counting}}</td>
						<td colspan="2" class="">{{$value->name}}</td>
						<td class="text-right"></td>
						<td class="text-right">{{formatNumber($value->total_price)}}</td>
						<td class="text-right border-left">{{formatNumber($value->total_price)}}</td>
					</tr>
					<?php $ppn+=$value->ppn;$subtotal+=$value->total_price;$counting++; ?>
				<?php endif; ?>
			<?php endforeach; ?>

		<?php endif; ?>
		<tr>
			<td class=""></td>
			<td colspan="2" class="font-bold">Sub Total FF</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left border-top">{{formatNumber($subtotal)}}</td>
		</tr>
		<tr>
			<td class=""></td>
			<td colspan="2" class="">PPN</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left">{{formatNumber($ppn)}}</td>
		</tr>
		<tr>
			<td class=""></td>
			<td colspan="2" class="">Diskon</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left border-bottom">{{formatNumber($discount)}}</td>
		</tr>
		<?php $total+=$subtotal+$ppn-$discount; ?>
		<tr>
			<td> </td>
			<td colspan="2" class="font-bold">Total FF</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left">{{formatNumber($total)}}</td>
		</tr>
		<tr>
			<td colspan="5" style="height:5px;" class="border-right"></td>
			<td></td>
		</tr>

	<?php endif; ?>
	<?php $total_trucking=0; ?>
	<?php if (count($detail['detail_trucking'] ?? [])>0): ?>
		<tr>
			<td class="">B</td>
			<td colspan="2" class="font-bold">Trucking</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left"></td>
		</tr>
		<?php foreach ($detail['detail_trucking'] as $key => $value): ?>
			<tr>
				<td class="text-center">{{$key+1}}</td>
				<td colspan="2" class="">{{$value->service.' '.$value->trayek}}</td>
				<td class="text-center">{{$value->qty.' x '.($value->ctype?:$value->vtype)}}</td>
				<td class="text-right">{{formatNumber($value->total_price/$value->qty)}}</td>
				<td class="text-right border-left">{{formatNumber($value->total_price+$value->ppn)}}</td>
			</tr>
			<?php $total_trucking+=($value->total_price+$value->ppn) ?>
		<?php endforeach; ?>
		<tr>
			<td> </td>
			<td colspan="2" class="font-bold">Total Trucking</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left border-top">{{formatNumber($total_trucking)}}</td>
		</tr>
		<tr>
			<td colspan="5" style="height:5px;" class="border-right"></td>
			<td></td>
		</tr>

	<?php endif; ?>
	<?php $total_reimbursement=0; ?>
	<?php if (array_key_exists('detail_other', $detail)): ?>
		<tr>
			<td class=""></td>
			<td colspan="2" class="font-bold">Reimbursement</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left"></td>
		</tr>
		<?php $ppnLain=0; ?>
		<?php foreach ($detail['detail_other'] as $key => $value): ?>
			<?php if (like_match('fee port%',$value->name)) {
				continue;
			} ?>
			<tr>
				<td class="text-center">{{$key+1}}</td>
				<td colspan="2" class="">{{$value->name}}</td>
				<td class="text-right"></td>
				<td class="text-right"></td>
				<td class="text-right border-left">{{formatNumber($value->total_price+$value->ppn)}}</td>
			</tr>
			<?php $total_reimbursement+=($value->total_price+$value->ppn);$ppnLain+=$value->ppn; ?>

		<?php endforeach; ?>
		<tr>
			<td class=""></td>
			<td colspan="2" class="font-bold">Total Reimbursement</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left border-top">{{formatNumber($total_reimbursement)}}</td>
		</tr>
	<?php endif; ?>
	<?php $totalAll=$total+$total_trucking+$total_reimbursement; ?>
	<tr>
		<td colspan="2" style="height:20px;" class="font-bold">Grand Total</td>
		<td class=""></td>
		<td class='text-right'></td>
		<td class="text-right"></td>
		<td class="text-right border-left font-bold">{{formatNumber($totalAll)}}</td>
	</tr>

	<tr>
		<td colspan="5" style="height:20px;"> </td>
		<td class="border-left"> </td>
	</tr>
	<tr>
		<td class="" colspan="4" style="padding-right:15px;"> <b>In Word :&nbsp;<i>{{penyebut($totalAll)}} rupiah</i></b> </td>
		<td>
			<b></b>
		</td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td colspan="4">Enclosed :&nbsp;Original Doc</td>
		<td></td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td colspan="4">Due Date :&nbsp;{{Carbon\Carbon::parse($item2->due_date)->format('d M Y')}}</td>
		<td></td>
		<!-- due_date -->
		<td class="border-left" colspan="1"></td>
	</tr>
</table>
<br>
<br>
<table style="width: 100%">
	<tr>
		<td style="width: 50%">
			Transfer Payment To<br>
			<b>Rekening</b>
			<!-- Rekening Cabang -->
			<br>
			<br>
			A/N &nbsp;&nbsp; : {{$remark->person}}<br>
			A/C &nbsp;&nbsp; : {{$remark->account}}<br>
			Bank &nbsp;: {{$remark->bank}}<br>
		</td>
		<td style="width: 20%"></td>
		<td style="width: 30%">
			<!-- Cabang Invoice, Tanggal Invoice -->
			{{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}<br><br><br><br><br><br><br><br><br>
			{{$remark->signature}}<br>
			<i>{{$remark->position}}</i>
		</td>
	</tr>
</table>
