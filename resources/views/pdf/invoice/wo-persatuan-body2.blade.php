@include('pdf.invoice.invoice-header')

<br>
<div class="row">
	<div class="column">
		<table style="width: 100%" class="text-top">
			<tr>
				<td style="width: 10%">To</td>
				<td style="width: 1%">:</td>
				<td style="width: 45%;">{{$item->customer->name}}</td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td style="padding-right: 50px;">{{$item->customer->address}}</td>
			</tr>
			<tr>
				<td>Attn</td>
				<td>:</td>
				<td>{{$remark->attn}}</td>
			</tr>
		</table>

	</div>
	<div class="column">
		<table style="width: 90%" class="text-top">
			<tr>
				<td>Date</td>
				<td>:</td>
				<td>{{ Carbon\Carbon::parse($item->date_invoice)->format('d M Y') }}</td>
			</tr>
			<tr>
				<td>No. WO</td>
				<td>:</td>
				<td>{{ $detail['addon']->code_wo }}</td>
			</tr>
			<tr>
				<td>No. BL</td>
				<td>:</td>
				<td>{{$detail['addon']->bl}}</td>
			</tr>
			<tr>
				<td>No. AJU</td>
				<td>:</td>
				<td>{{$detail['addon']->aju}}</td>
			</tr>
			@if($detail['addon']->vessel)
			<tr>
				<td>Vessel</td>
				<td>:</td>
				<td>{{$detail['addon']->vessel}}</td>
			</tr>
			@endif
		</table>

	</div>
</div>
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
	<?php if (count($detail['detail_ff'])>0): ?>
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
				<td colspan="2" class="">{{$value->commodity_name}}</td>
				<td class="text-center">{{$value->qty.' '.$value->piece}}</td>
				<td class="text-right">{{formatNumber($value->price)}}</td>
				<td class="text-right border-left">{{formatNumber($value->total_price)}}</td>
			</tr>
			<?php
				$ppn+=($show_ppn == 1 ? $value->ppn : 0);
				$subtotal+=$value->total_price;
				$discount+=$value->discount;
				$counting++;
			?>
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
		@if($show_ppn == 1)
		<tr>
			<td class=""></td>
			<td colspan="2" class="">PPN</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left">{{formatNumber($ppn)}}</td>
		</tr>
		@endif
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
	<?php if (count($detail['detail_trucking'])>0): ?>
		<tr>
			<td class="">B</td>
			<td colspan="2" class="font-bold">Trucking</td>
			<td class='text-right'></td>
			<td class="text-right"></td>
			<td class="text-right border-left"></td>
		</tr>
		<?php foreach ($detail['detail_trucking'] as $key => $value): ?>
			<?php
				if($show_ppn != 1) {
					$value->ppn = 0;
				}
			 ?>
			<tr>
				<td class="text-center">{{$key+1}}</td>
				<td colspan="2" class="">{{$value->commodity_name.' '.$value->trayek}}</td>
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
			<?php
				if (like_match('fee port%',$value->name)) {
					continue;
				}

				if($show_ppn != 1) {
					$value->ppn = 0;
				}
			?>
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

	@if($show_ppn == 1)
	<tr>
		<td colspan="2" style="height:20px;" class="font-bold">PPN</td>
		<td class=""></td>
		<td class='text-right'></td>
		<td class="text-right"></td>
		<td class="text-right border-left font-bold">{{formatNumber($ppn_total)}}</td>
	</tr>
	@endif

	<tr>
		<td colspan="2" style="height:20px;" class="font-bold">Pajak Lainnya</td>
		<td class=""></td>
		<td class='text-right'></td>
		<td class="text-right"></td>
		<td class="text-right border-left font-bold">{{formatNumber($other_tax)}}</td>
	</tr>

	<?php
		if($show_ppn == 1) {
			$other_tax += $ppn_total;
		}
	 ?>

	<tr>
		<td colspan="2" style="height:20px;" class="font-bold">Grand Total</td>
		<td class=""></td>
		<td class='text-right'></td>
		<td class="text-right"></td>
        @php
            $grandtotal = $totalAll + $other_tax
        @endphp
		<td class="text-right border-left font-bold">{{formatNumber($grandtotal)}}</td>
	</tr>

	<tr>
		<td colspan="5" style="height:20px;"> </td>
		<td class="border-left"> </td>
	</tr>
	<tr>
		<td class="" colspan="5" style="padding-right:15px;"> <b>In Word :&nbsp;<i>{{penyebut($grandtotal)}} rupiah</i></b> </td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td colspan="4">Enclosed :&nbsp;Original Doc</td>
		<td></td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td colspan="4">Due Date :&nbsp;{{Carbon\Carbon::parse($item->due_date)->format('d M Y')}}</td>
		<td></td>
		<!-- due_date -->
		<td class="border-left" colspan="1"></td>
	</tr>
</table>
@if($item->description)
<p>Invoice Note : {{$item->description}}</p>
@endif
<br>
<br>
<table style="width: 100%">
	<tr>
		<td style="width: 50%; vertical-align:top;">
			Transfer Payment To<br>
			<b>Rekening</b>
			<!-- Rekening Cabang -->
			<br>
			<br>
			A/N &nbsp;&nbsp; : {{ $remark->person }}<br>
			A/C &nbsp;&nbsp; : {{ $remark->account }}<br>
			Bank &nbsp;: {{ $remark->bank }}<br>
		</td>
		<td style="width: 20%"></td>
		<td style="width: 30%;vertical-align:top;">
			<!-- Cabang Invoice, Tanggal Invoice -->
			{{@$item->company->city->name}}, {{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}<br><br><br><br><br>
			{{ $remark->signature }}<br>
			<i>{{ $remark->position }}</i>
		</td>
	</tr>
</table>
