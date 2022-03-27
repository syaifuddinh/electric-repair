<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Commodity;
use App\Model\ContactAddress;
use App\Model\Handling;
use App\Model\HandlingVehicle;
use App\Model\Warehouse;
use App\Model\Rack;
use App\Model\Contact;
use App\Model\Company;
use App\Model\City;
use App\Model\Piece;
use App\Model\VehicleType;
use App\Model\ContainerType;
use App\Model\Service;
use App\Model\WarehouseReceipt;
use App\Model\WarehouseReceiptDetail;
use App\Model\PriceList;
use App\Model\CustomerPrice;
use App\Model\WorkOrder;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\JobOrderReceiver;
use App\Model\CostType;
use App\Model\JobOrderCost;
use App\Model\KpiStatus;
use App\Model\KpiLog;
use App\Model\WorkOrderDetail;
use App\Model\WarehouseStockDetail;
use App\Model\Item;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class HandlingController extends Controller
{
		/**
		 * Display a listing of the resource.
		 *
		 * @return \Illuminate\Http\Response
		 */
		public function index()
		{
				//
		}

		/**
		 * Show the form for creating a new resource.
		 *
		 * @return \Illuminate\Http\Response
		 */
		public function create()
		{
			$data['vehicle_type'] = VehicleType::all();
			$data['container_type'] = ContainerType::all();
			$data['warehouse']=Warehouse::where('is_active', 1)->get();
			$data['rack']=Rack::join('storage_types', 'storage_type_id', '=', 'storage_types.id')->where('is_handling_area', 0)->where('is_picking_area', 0)->selectRaw('racks.*')->get();
			return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
		}

		/**
		 * Store a newly created resource in storage.
		 *
		 * @param  \Illuminate\Http\Request  $request
		 * @return \Illuminate\Http\Response
		 */
		public function store(Request $request)
		{
			// $request->validate([
			//   'customer_id' => 'required',
			//   'warehouse_id' => 'required',
			//   'type_tarif' => 'required',
			//   'work_order_id' => 'required',
			//   'shipment_date' => 'required',
			// ]);
			DB::beginTransaction();
			$service = DB::table('services')->where('name', 'Handling')->where('is_warehouse', 1)->first();
			$request->service_id = $service->id;
			if ($request->type_tarif==2) {
				$priceList=PriceList::where("service_id", $request->service_id)->first();
				if (empty($priceList)) {
					return Response::json(['message' => 'Tarif Umum dengan layanan handling tidak ditemukan'],500);
				}
				$price=$priceList->price_full;
				$total_price=$request->total_unit*$priceList->price_full;
			} else {
				$customerPrice = CustomerPrice::where('service_id', $request->service_id)->where('customer_id', $request->customer_id)->first();
				if (empty($customerPrice)) {
					return Response::json(['message' => 'Tarif customer untuk customer ini dan layanan handling tidak ditemukan'],500);
				}
			}
			$customer=Contact::find($request->customer_id);
			$worr=WorkOrder::find($request->work_order_id);
			if (isset($request->quotation_detail_id)) {
				$quot=QuotationDetail::find($request->quotation_detail_id);
			} else {
				$quot=null;
			}

			if ($request->work_order_id==0) {
				$code = new TransactionCode($worr->company_id, 'workOrder');
				$code->setCode();
				$trx_code = $code->getCode();
				$w=WorkOrder::create([
					'customer_id' => $request->customer_id,
					'quotation_id' => ($quot?$quot->header_id:null),
					'code' => $trx_code,
					'total_job_order' => 1,
					'updated_by' => auth()->id()
				]);
				$wo=$w->id;
			} else {
				WorkOrder::find($request->work_order_id)->update([
					'total_job_order' => DB::raw("total_job_order+1")
				]);
				$wo=$request->work_order_id;
			}
			$code = new TransactionCode($worr->company_id, 'jobOrder');
			$code->setCode();
			$trx_code = $code->getCode();

			$request->total_unit = 0;
			foreach ($request->detail as $key => $value) {
				if(!empty($value)) {
					continue;
				}

				$request->total_unit += $value['total_item'];
			}

			$jo=JobOrder::create([
				'company_id' => $worr->company_id,
				'customer_id' => $request->customer_id,
				'collectible_id' => $request->customer_id,
				'service_type_id' => Service::where('name', 'Handling')->where('is_warehouse', 1)->first()->service_type_id,
				'service_id' => Service::where('name', 'Handling')->where('service_type_id', 10)->first()->id,
				'quotation_id' => ($quot?$quot->header_id:null),
				'quotation_detail_id' => $request->quotation_detail_id,
				'work_order_id' => $wo,
				'work_order_detail_id' => $request->work_order_detail_id,
				'code' => $trx_code,
				'shipment_date' => dateDB($request->shipment_date),
				'description' => $request->description,
				'total_unit' => $request->total_unit,
				'no_bl' => $request->bl_no,
				'price' => 0,
				'total_price' => 0,
				'is_handling' => 1,
				'create_by' => auth()->id(),
				'aju_number' => $request->aju_number
			]);
		 
			// Perhitungan harga
			$work_order = WorkOrder::find($wo);
			$price_borongan = 0;
			$price_volume = 0;
			$price_tonase = 0;
			$price_item = 0;
			$work_order_detail = WorkOrderDetail::find($request->work_order_detail_id);
			if($work_order->is_customer_price == 1) {
				// Jika dari tarif customer
				$customer_price = CustomerPrice::find($work_order_detail->customer_price_id);
				$price_borongan = $customer_price->price_full;
				$price_volume = $customer_price->price_volume;
				$price_tonase = $customer_price->price_tonase;
				$price_item = $customer_price->price_item;
			}
			else {
				if($work_order_detail->price_list_id != null) {
					// Jika dari tarif umum
					$price_list = PriceList::find($work_order_detail->price_list_id);
					$price_borongan = $price_list->price_full;
					$price_volume = $price_list->price_volume;
					$price_tonase = $price_list->price_tonase;
					$price_item = $price_list->price_item;
				}
				else {

				}
			}

			$total_item=0;
			$total_price=0;

			// Mencari ID Rack
			$rack = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', 'storage_types.id')
			->where('is_handling_area', 1)
			->where('warehouse_id', $request->warehouse_id)
			->select('racks.id')
			->first();
			$rack_id = $rack->id;


			$item_piece = DB::table('pieces')->where('name', 'Item')->first();
			foreach ($request->detail as $key => $value) {
				if(!empty($value)) {
			        $i = Item::find($value['id']);
			        if( $i == null ) {
		                return Response::json(['message' => 'ID Barang tidak ditemukan'], 422);
		            } else {
		            	$value['item_name'] = $i->name;
		            }
					$ws = WarehouseStockDetail::whereRackId($rack_id)->whereNoSuratJalan($value['no_surat_jalan'])->whereItemId($value['id'])->first();
				    if($ws == null) {
				        return Response::json([
				            "message" => 'Stok ' . $i->name . ' tidak mencukupi',
				            'item' => [
				                'name' => $i->name,
				                'qty_stock' => 0,
				                'qty_dikeluarkan' => $value['total_item']
				            ]
				        ], 422);
				    } else {
				        if($ws->qty < $value['total_item']) {
				            return Response::json([
				                "message" => 'Stok ' . $i->name . ' tidak mencukupi',
				                'item' => [
				                    'name' => $i->name,
				                    'qty_stock' => $ws->qty,
				                    'qty_dikeluarkan' => $value['total_item']
				                ]
				            ], 422);
				        } else {
				        	$value['long'] = $i->long;
				        	$value['wide'] = $i->wide;
				        	$value['high'] = $i->height;
				        }
				    }

					if($value['imposition'] == 1) {
						// Tarif berdasarkan volume
						$price = $price_volume;
						$subtotal_price = $value['total_volume'] * $price_volume;
					}
					else if($value['imposition'] == 2) {
						// Tarif berdasarkan berat / tonase
						$price = $price_tonase;
						$subtotal_price = $value['total_tonase'] * $price_tonase; 
					}
					else if($value['imposition'] == 3) {
						// Tarif berdasarkan item
						$price = $price_item;
						$subtotal_price = $value['total_item'] * $price_item;
					}
					else {
						// Tarif borongan
						$price = $price_borongan;
						$subtotal_price = $price_borongan;
					}

					$total_price += $subtotal_price;
					JobOrderDetail::create([
						// 'piece_id' => $value['piece_id'],
						'header_id' => $jo->id,
						'quotation_id' => ($quot?$quot->header_id:null),
						'quotation_detail_id' => $request->quotation_detail_id,
						'commodity_id' => $request->commodity_id,
						'sender_id' => $request->sender_id,
						'receiver_id' => $request->receiver_id,
						'create_by' => auth()->id(),
						'is_contract' => ($quot?1:0),
						'piece_id' => $item_piece->id,
						'price' => $price,
						'total_price' => $subtotal_price,
						'qty' => $value['total_item'],
						'imposition' => $value['imposition'],
						'long' => $value['long'],
						'wide' => $value['wide'],
						'high' => $value['high'],
						'volume' => $value['total_volume'],
						'weight' => $value['total_tonase'],
						'item_name' => $value['item_name'],
						'item_id' => $value['id'],
						'rack_id' => $rack_id,
						'no_surat_jalan' => $value['no_surat_jalan'],
						
						'description' => $value['description'],
					]);
				}
				$total_item++;
			}

			if(isset($request->staff_gudang_id)) {
				$c = Contact::find($request->staff_gudang_id);
				$request->staff_gudang_name = $c->name;
			}

			$handling = Handling::create([
				'job_order_id' => $jo->id,
				'warehouse_id' => $request->warehouse_id,
				'is_overtime' => $request->is_overtime != null ? $request->is_overtime : 0,
				'start_time' => $request->start_time,
				'end_time' => $request->end_time,
				'description' => $request->description,
				'staff_gudang_name' => $request->staff_gudang_name,
			]);



			foreach ($request->detail_carrier as $key => $value) {
				HandlingVehicle::create([
					// 'piece_id' => $value['piece_id'],
					'handling_id' => $handling->id,
					
					'type' => $value['type'],
					'no_seal' => $value['no_seal'],
					
					
					
					'driver_name' => $value['driver_name'],
					'no_container' => isset($value['no_container']) ? $value['no_container'] : '-',
					'no_carrier' => isset($value['no_carrier']) ? $value['no_carrier'] : '-'
				]);
				$total_item++;
			}
			$ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
			
			$klog=KpiLog::create([
				'kpi_status_id' => $ks->id,
				'job_order_id' => $jo->id,
				'company_id' => $worr->company_id,
				'create_by' => auth()->id(),
				'date_update' => Carbon::now()
			]);
			JobOrder::find($jo->id)->update([
				'kpi_id' => $klog->kpi_status_id,
				'total_item' => $total_item,
				'total_price' => $total_price
			]);
		

			DB::commit();

			return Response::json(['message' => 'Transaksi handling berhasil di-input'], 200);
		}

		/**
		 * Display the specified resource.
		 *
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function show($id)
		{
			$jo=JobOrder::with('invoice_jual:header_id,job_order_id','invoice_jual.invoice:id,code','customer:id,name','service:id,name,service_type_id','kpi_status:id,name','service.service_type:id,name','work_order:id,code')->where('id', $id)->selectRaw('id, code, work_order_id, no_bl, customer_id, created_at, description, service_id, service_type_id, kpi_id, invoice_id, is_handling, total_price')->first();

			if($jo == null) {
				return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);
			}
			else {
				if($jo->is_handling != 1) {
					return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);

				}
			}
			$jo->service_name = $jo->service->name . ' (' . $jo->service->service_type->name . ')';

			$sql="
			SELECT
				manifests.id,
				manifests.code,
				contacts.name as driver,
				vehicles.nopol,
				vehicle_types.name as vname,
				CONCAT(container_types.code,' - ',container_types.name) as cname,
				IF(manifests.status=1,'Packing List',IF(manifests.status=2,'Berangkat',IF(manifests.status=3,'Sampai','Selesai'))) as status_name,
				voyage_schedules.eta,
				voyage_schedules.etd,
				containers.stuffing,
				containers.stripping,
				manifests.depart,
				manifests.arrive,
				manifests.container_id,
				CONCAT(IFNULL(vessels.name,''),' - ',IFNULL(voyage_schedules.voyage,'')) as voyage,
				CONCAT(IFNULL(container_types.code,''),' - ',IFNULL(containers.container_no,'')) as container
			FROM
				manifests
				LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
				LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
				LEFT JOIN contacts ON contacts.id = manifests.driver_id
				LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
				LEFT JOIN container_types ON container_types.id = manifests.container_type_id
				LEFT JOIN containers ON containers.id = manifests.container_id
				LEFT JOIN voyage_schedules ON voyage_schedules.id = containers.voyage_schedule_id
				LEFT JOIN vessels ON vessels.id = voyage_schedules.vessel_id
			WHERE
				manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $id )
			GROUP BY
				manifests.id
			";
			// $jocost=JobOrderCost::where('header_id', $id)->pluck('cost_type_id');
			$data['item']=$jo;
			// $data['wo_detail']=DB::table('work_order_details')
			// ->leftJoin('quotation_details','work_order_details.quotation_detail_id','quotation_details.id')
			// ->leftJoin('price_lists','work_order_details.price_list_id','price_lists.id')
			// ->leftJoin('services as service1','quotation_details.service_id','service1.id')
			// ->leftJoin('services as service2','price_lists.service_id','service2.id')
			// ->whereRaw("if(work_order_details.quotation_detail_id is not null,quotation_details.service_type_id,price_lists.service_type_id) = $jo->service_type_id and work_order_details.id != $jo->work_order_detail_id and work_order_details.header_id = $jo->work_order_id")
			// ->selectRaw("work_order_details.id,if(work_order_details.quotation_detail_id is not null,concat(service1.name,' - Rp.',FORMAT(ifnull(quotation_details.price_contract_full,0)+ifnull(quotation_details.price_contract_tonase,0)+ifnull(quotation_details.price_contract_volume,0)+ifnull(quotation_details.price_contract_item,0),2)),concat(service2.name,' - Rp.',format(ifnull(price_lists.price_full,0)+ifnull(price_lists.price_tonase,0)+ifnull(price_lists.price_volume,0)+ifnull(price_lists.price_item,0),2))) as name")
			// ->get();
			// $data['manifest']=DB::select($sql);
			$data['detail']=JobOrderDetail::with('piece:id,name')->where('header_id', $id)->selectRaw('id, no_surat_jalan, item_name, item_id, piece_id, imposition, if(imposition = 1, "Kubikasi", if(imposition = 2, "Tonase", if(imposition = 3, "Item", "Tonase"))) AS imposition_name, qty, weight, volume, description')->get();
			// $data['piece']=Piece::all();
			// $data['cost_type']=CostType::with('parent')->where('company_id', $data['item']->company_id)->where('parent_id','!=',null)->get();
			// $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")->select('id','name')->get();
			$data['cost_detail']=JobOrderCost::with('cost_type:id,name','vendor:id,name')->where('header_id', $id)->selectRaw('id, cost_type_id, vendor_id, price, qty, total_price, description, status, if(status = 1, "Belum Diajukan", if(status = 2, "Diajukan Keuangan", if(status = 3, "Disetujui Keuangan", if(status = 4, "Ditolak", if(status = 5, "Diposting", if(status = 6, "Revisi", if(status = 7, "Diajukan Atasan", "Disetujui"))))))) AS status_name, `type`, IF(`type` = 1, "Biaya operasional", "Reimbursement") AS type_name')->get();
			// $data['receipt_detail']=JobOrderReceiver::where('header_id', $id)->get();
			// $data['kpi_status']=KpiStatus::where('service_id', $data['item']->service_id)->orderBy('sort_number','asc')->select('id','name', 'is_done' )->get();
			$sql_date="
			SELECT
				kpi_statuses.name,
				kpi_statuses.status,
				kpi_logs.date_update
			FROM
				kpi_logs
				LEFT JOIN kpi_statuses ON kpi_statuses.id = kpi_logs.kpi_status_id
			WHERE
				kpi_logs.job_order_id = $id
				and kpi_statuses.status != 3
			ORDER BY
				kpi_logs.date_update desc,
				kpi_statuses.status desc";
			$tm=DB::select($sql_date);
			// dd($tm);
			$durasi=0;
			foreach ($tm as $key => $value) {
				// dd($tm[$key+1]??false);
				if (end($tm)==$value) {
					break;
				}
				$next=$tm[$key+1];
				$diffSecond=Carbon::parse($next->date_update)->diffInSeconds(Carbon::parse($value->date_update));
				// dd($diffSecond);
				$durasi+=$diffSecond;
			}
			// dd($durasi);
			$data['durasi']=convertSecs2($durasi);
			$handling=Handling::with('warehouse:id,name')->where('job_order_id', $id)->first();
			$data['item']->warehouse = $handling->warehouse;
			$data['item']->warehouse_id = $handling->warehouse_id;
			$data['item']->is_overtime = $handling->is_overtime;
			$data['item']->overtime_label = $handling->is_overtime == 1 ? 'Ya' : 'Tidak' ;
			$data['item']->start_time = $handling->start_time;
			$data['item']->end_time = $handling->end_time;
			$data['item']->staff_gudang_name = $handling->staff_gudang_name;
			$rack = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', 'storage_types.id')
			->where('is_handling_area', 1)
			->where('warehouse_id', $handling->warehouse_id)
			->select('racks.id')
			->first();

			$data['rack_id'] = $rack->id;
			$data['handling_inbound_vehicle']=HandlingVehicle::where('handling_id', $handling->id)->where('type', 'inbound')->selectRaw('id, no_carrier, no_container, no_seal, driver_name')->get();
			$data['handling_outbound_vehicle']=HandlingVehicle::where('handling_id', $handling->id)->where('type', 'outbond')->selectRaw('id, no_carrier, no_container, no_seal, driver_name')->get();
			return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
		}

		/**
		 * Show the form for editing the specified resource.
		 *
		 * @param  int  $idv
		 * @return \Illuminate\Http\Response
		 */
		public function edit($id)

		{
			$jo = JobOrder::find($id);
			if( $jo == null ) {
				return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);
			} else {
				if($jo->is_handling != 1) {
					return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);

				}
			}


			$data['item']=JobOrder::with('customer:id,name','service:id,name', 'quotation:id,no_contract')->where('id', $id)->selectRaw('id, customer_id, quotation_id, service_id, created_at, DATE_FORMAT(shipment_date, "%d-%m-%Y") AS shipment_date, no_bl, description')->first();
			// $data['vehicle_type']=VehicleType::select('id','name')->get();
			// $data['commodity']=Commodity::select('id','name')->get();
			// $data['address']=ContactAddress::whereRaw("contact_id = ".$data['item']->customer_id)->leftJoin('contacts','contacts.id','=','contact_addresses.contact_address_id')->select('contacts.id','contacts.name','contacts.address')->get();
			// $data['detail_jasa']=JobOrderDetail::where('header_id', $id)->first();
			// $data['staff_gudang']=Contact::whereRaw("is_staff_gudang = 1")->select('id','name','address','company_id')->get();
			// $data['warehouse']=Warehouse::where('is_active', 1)->get();
			$handling=Handling::with('warehouse:id,name')->where('job_order_id', $id)->first();
			$data['item']->warehouse = $handling->warehouse;
			$data['item']->warehouse_id = $handling->warehouse_id;
			$data['item']->is_overtime = $handling->is_overtime;
			$data['item']->start_time = $handling->start_time;
			$data['item']->end_time = $handling->end_time;
			$data['item']->staff_gudang_name = $handling->staff_gudang_name;
			$c = Contact::where('name', $handling->staff_gudang_name)->first();
			if($c == null) {

				$data['item']->staff_gudang_id = null;
			}
			else {
				$data['item']->staff_gudang_id = $c->id;

			}
			return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
		}

		
		public function add_item(Request $request, $id)
		{
			// dd($request);
			DB::beginTransaction();
			$jo=JobOrder::find($id);
			if ($request->is_edit) {
				$jod=JobOrderDetail::find($request->detail_id);
				$request->validate([
					'piece_id' => 'required',
					'item_name' => 'required',
					'total_item' => 'required|integer|min:'.$jod->transported
				],[
					'total_item.min' => 'Minimal barang adalah '.$jod->transported.' karena sebagian sudah terangkut'
				]);
				// dd($request);
				$jod->update([
					'qty' => $request->total_item,
					'volume' => $request->total_volume,
					'weight' => $request->total_tonase,
					'item_name' => $request->item_name,
					'no_reff' => $request->reff_no,
					'no_manifest' => $request->manifest_no,
					'piece_id' => $request->piece_id,
					'leftover' => ($jod->leftover+$request->total_item-$jod->qty)
				]);

				if ($jo->service_type_id==1) {
					if (empty($jo->quotation_id)) {
						$priceList=PriceList::whereRaw("service_type_id = 1 and moda_id = $jo->moda_id and route_id = $jo->route_id and vehicle_type_id = $jo->vehicle_type_id")->first();
						if (empty($priceList)) {
							return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
						}
						if ($request->imposition==1) {
							$price=$priceList->price_volume;
							$total_price=$price*$request->total_volume;
						} elseif ($request->imposition==2) {
							$price=$priceList->price_tonase;
							$total_price=$price*$request->total_tonase;
						} else {
							$price=$priceList->price_item;
							$total_price=$price*$request->total_item;
						}
						// $total_price=*$price;
					} else {
						$quotationDetail=QuotationDetail::find($jo->quotation_detail_id);
						if ($request->imposition==1) {
							$price=$quotationDetail->price_contract_volume;
							$total_price=$price*$request->total_volume;
						} elseif ($request->imposition==2) {
							$price=$quotationDetail->price_contract_tonase;
							$total_price=$price*$request->total_tonase;
						} else {
							$price=$quotationDetail->price_contract_item;
							$total_price=$price*$request->total_item;
						}
					}
					JobOrderDetail::find($request->detail_id)->update([
						'price' => $price,
						'total_price' => $total_price,
					]);
				}
				DB::commit();

				return Response::json(null,200);
			}
		 

			JobOrderDetail::create([
				// 'piece_id' => $value['piece_id'],
				'header_id' => $jo->id,
				'quotation_id' => $jo->quotation_id,
				'quotation_detail_id' => $jo->quotation_detail_id,
				'commodity_id' => $jo->commodity_id,
				'sender_id' => $jo->sender_id,
				'receiver_id' => $jo->receiver_id,
				'create_by' => auth()->id(),
				'is_contract' => ($jo->quotation_id?1:0),
				'piece_id' => $request->piece_id,
				'price' => (!in_array($jo->service_type_id,[2,3])?$price:0),
				'total_price' => (!in_array($jo->service_type_id,[2,3])?$total_price:0),
				'qty' => $request->total_item,
				'volume' => $request->total_volume,
				'weight' => $request->total_tonase,
				'item_name' => $request->item_name,
				
				'imposition' => $request->imposition,
				'description' => $request->description,
				'leftover' => $request->total_item,
			]);

			DB::commit();

			return Response::json(null);
		}

		/**
		 * Update the specified resource in storage.
		 *
		 * @param  \Illuminate\Http\Request  $request
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function update(Request $request, $id)
		{
			$jo = JobOrder::find($id);
			if( $jo == null ) {
				return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);
			} else {
				if($jo->is_handling != 1) {
					return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);

				}
			}	

			DB::beginTransaction();
			$jo=JobOrder::find($id)->update([
				'shipment_date' => dateDB($request->shipment_date),
				'description' => $request->description,
				'no_bl' => $request->no_bl
			]);
			if(isset($request->staff_gudang_id)) {
				$c = Contact::find($request->staff_gudang_id);
				$request->staff_gudang_name = $c->name;
			}
			Handling::where('job_order_id', $id)->update([
				'staff_gudang_name' => $request->staff_gudang_name,
				'is_overtime' => $request->is_overtime,
				'start_time' => $request->start_time,
				'end_time' => $request->end_time

			]);
			DB::commit();

			return Response::json(['message' => 'Transaksi stuffing berhasil di-update'], 200);
		}

		
		public function store_item_detail(Request $request, $id)
		{
			$request->validate([
				'piece_id' => 'required',
				'qty' => 'required',
				'long' => 'required',
				'wide' => 'required',
				'height' => 'required',
				'volume' => 'required',
				'weight' => 'required',
				'description' => 'required',
			]);

			DB::beginTransaction();
			JobOrderDetail::create([
						// 'piece_id' => $value['piece_id'],
					'header_id' => $id,
					'create_by' => auth()->id(),
					'piece_id' => $request->piece_id,
					'qty' => $request->total_item,
					'long' => $request->long,
					'wide' => $request->wide,
					'height' => $request->height,
					'volume' => $request->total_volume,
					'weight' => $request->total_tonase,
					'description' => $request->description,
			]);
			DB::commit();

			return Response::json(null);
		}


		public function destroy_item_detail($id)
		{
			DB::beginTransaction();
			JobOrderDetail::find($id)->delete();
			DB::commit();

			return Response::json(null);
		}


		public function store_vehicle_detail(Request $request, $id)
		{
			DB::beginTransaction();
			if(Handling::where('job_order_id', $id)->first() == null ) {
		          return Response::json(['message' => 'Transaksi handling tidak ditemukan'], 422);
		    }
			$handling_id = Handling::where('job_order_id', $id)->first()->id;
			HandlingVehicle::create([
				'handling_id' => $handling_id,
				'type' => $request->type,
				'no_carrier' => isset($request->no_carrier) ? $request->no_carrier : '-', 
				'no_container' => isset($request->no_container) ? $request->no_container : '',
				'no_seal' => $request->no_seal,
				'driver_name' => $request->driver_name
			]);
			DB::commit();
			$type_label = strtolower($request->type) == 'inbound' ? 'Masuk' : 'Keluar';
			return Response::json(['message' => "Kendaraan $type_label Berhasil Ditambahkan"],200,[],JSON_NUMERIC_CHECK);
		}
		public function update_vehicle_detail(Request $request)
		{
			DB::beginTransaction();
			$h = HandlingVehicle::find($request->id);
			if($h == null) {
				return Response::json(['message' => 'Detail kendaraan tidak ditemukan'], 422);
			}
			$h->update([
				'no_carrier' => $request->no_carrier,
				'no_container' => $request->no_container,
				'carrier_size' => $request->carrier_size,
				'no_seal' => $request->no_seal,
				'driver_name' => $request->driver_name
			]);
			DB::commit();
			$type_label = strtolower($h->type) == 'inbound' ? 'masuk' : 'keluar';
			return Response::json(['message' => "Kendaraan $type_label berhasil di-update"],200,[],JSON_NUMERIC_CHECK);
		}

		public function delete_vehicle_detail($id)
		{
			DB::beginTransaction();
			if(HandlingVehicle::find($id) == null) {
					return Response::json(['message' => 'Detail kendaraan tidak ditemukan'], 422);
			}
			$type_label = strtolower(HandlingVehicle::find($id)->type) == 'inbound' ? 'masuk' : 'keluar';
			HandlingVehicle::find($id)->delete();
			DB::commit();
			return Response::json(['message' => 'Kendaraan $type_label berhasil dihapus'],200,[],JSON_NUMERIC_CHECK);
		}
		/**
		 * Remove the specified resource from storage.
		 *
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function destroy($id)
		{
				DB::beginTransaction();
				$jo = JobOrder::find($id);
				$h = Handling::where('job_order_id', $id);
				if($h->first() == null) {
					return Response::json(['message' => 'Transaksi tidak ditemukan, mungkin sudah dihapus sebelumnya'],500);
				}
				if($jo->invoice_id != null) {
					return Response::json(['message' => 'Transaksi tidak bisa dihapus karena sudah memiliki invoice'],500);          
				}
				HandlingVehicle::where('handling_id', $h->first()->id)->delete();
				JobOrderDetail::where('header_id', $id)->delete();
				$jo->delete();
				$h->delete();
				DB::commit();

				return Response::json(['message' => 'Transaksi handling berhasil dihapus'], 200);
		}

		public function remove($id)
		{
				DB::beginTransaction();
				$jo = JobOrder::find($id);
				$h = Handling::where('job_order_id', $id);
				HandlingVehicle::where('handling_id', $h->id)->delete();
				JobOrderDetail::where('header_id', $id)->delete();
				$jo->delete();
				$h->delete();
				DB::commit();

				return Response::json(null);
		}
}
