app.controller('opWarehouseStuffing', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.source = null
    $rootScope.pageTitle="Stuffing Job Order";
    $scope.isFilter = false;
    $scope.serviceStatus = [];
    $scope.formData = {}
    $scope.checkData = {}
// $scope.checkData.detail = []
// $scope.formData.customer_id = null
// $scope.formData.is_done = 1

// console.log($stateParams)
$http.get(baseUrl+'/operational/job_order').then(function(data) {
    $scope.data=data.data;
});

$scope.disableArchive=true
$scope.isCheck=function() {
    $scope.disableArchive=true
    angular.forEach($scope.checkData.detail, function(val,i) {
        if (val.value) {
            return $scope.disableArchive=false;
        }
    })
}

$scope.submitArchive=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $http.post(baseUrl+'/operational/job_order/store_archive',$scope.checkData).then(function(data) {
        $state.reload()
        toastr.success("Job Order berhasil dipindahkan ke arsip");
    });
}

oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[0,'desc']],
    dom: 'Blfrtip',
    ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/operational/job_order_datatable',
        data: function(d){
            d.customer_id = $scope.formData.customer_id;
            d.is_done = $scope.formData.is_done;
            d.start_date = $scope.formData.start_date;
            d.end_date = $scope.formData.end_date;
            d.service_id = $scope.formData.service;
// d.kpi_id = $scope.formData.status;
d.kpi_status_name = $scope.formData.kpi_status_name;
d.is_operational_done=0
d.is_stuffing=1
}
},
buttons: [
{
    'extend' : 'excel',
    'enabled' : true,
    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
    'className' : 'btn btn-default btn-sm',
    'filename' : 'Job Order - '+new Date(),
    'sheetName' : 'Data',
    'title' : 'Job Order'
},
],

columns:[
{data:"checklist",name:"id",sorting:false,className:'text-center'},
{data:"code",name:"code"},
{
    data:null,
    orderable:false,
    searhable:false,
render : resp => $filter('fullDate')(resp.shipment_date)
},
{data:"customer.name",name:"customer.name",className:"font-bold"},
{data:"no_po_customer",name:"no_po_customer", className : 'hidden'},
{data:"service.name",name:"service.name"},
{data:"trayek.name",name:"trayek.name", className : 'hidden'},
{data:"kpi_status.name",name:"kpi_status.name",className:""},

{data:"no_bl",name:"no_bl"},
{
    data:null,
    render: function(resp) {
        var action = resp.action;
        var outp = action.replace(/operational\.job_order/g, 'operational_warehouse.stuffing');

        return outp;
    },
    name:"action",
    className:"text-center"
},
],
columnDefs : [
{
    targets : 0,
    width : '5mm'
}
],
initComplete : null,
createdRow: function(row, data, dataIndex) {
    $compile(angular.element(row).contents())($scope);
}
});
oTable.buttons().container().appendTo( '#export_button' );

$scope.toggleFilter=function()
{
    $scope.isFilter = !$scope.isFilter
}
$scope.serviceChange=function()
{
    let id = $scope.formData.service
// find service
let service = $filter('filter')($scope.data.services, {id: id}, true)

$scope.serviceStatus = service[0].kpi_statuses
return
}
$scope.filterJobOrder=function()
{
    $scope.formData.is_done = null
    oTable.ajax.reload()
    return
}

$scope.notifData={}
$scope.sendNotification=function() {
    $('#notifModal').modal()
}

$scope.submitNotif=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $http.post(baseUrl+'/operational/job_order/send_notification',$scope.notifData).then(function(data) {
        toastr.success("Pesan Berhasil Dikirim!");
        $scope.notifData={}
        $('#notifModal').modal('hide')
    });
}

$scope.resetFilter=function()
{
    $scope.formData.customer_id=null
    $scope.formData.is_done=null
    $scope.formData.start_date=null
    $scope.formData.end_date=null
    $scope.formData.service=null
    $scope.formData.status=null
    $scope.formData.kpi_status_name=null
    $('#cari_data').trigger('click');
}
$scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
        $http.delete(baseUrl+'/operational/job_order/'+ids,{_token:csrfToken}).then(function success(data) {
// $state.reload();
oTable.ajax.reload();
toastr.success("Data Berhasil Dihapus!");
}, function error(data) {
    toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
});
    }
}
});
app.controller('opWarehouseStuffingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {

    $rootScope.pageTitle="Tambah Job Order";
    $scope.formData={};
    $scope.formData.total_append=0;
    $scope.detailData={};
    editedRow = null;
    $scope.div_main=false;
    $scope.work_orders=[
    {id:0,name:"Buat WO Baru"}
    ];
    $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:3,name:'Item'},
    {id:4,name:'Borongan'},
    ];

    $scope.validateForm = function() {

        var detail_length = $scope.formData.detail.length;
        var inbound_vehicle_length = inbound_datatable.data().toArray().length;

        console.log('detail_length : ' + detail_length);
        console.log('inbound_vehicle_length : ' + inbound_vehicle_length);
        if(!$scope.formData.warehouse_id || !$scope.formData.start_time || !$scope.formData.end_time || !$scope.formData.staff_gudang_name || detail_length == 0 || inbound_vehicle_length == 0) {
            $('#submitButton').attr('disabled', 'disabled')
        }
        else {
            $('#submitButton').removeAttr('disabled')
        }

        if($scope.disBtn == true) {
        }
        else {

        }
    }



    $scope.getRack = function() {

        var request = {
            'warehouse_id' : $scope.formData.warehouse_id,
            'warehouse_receipt_id' : $scope.detailData.warehouse_receipt_id
        }
        request = $.param(request);
        $http.get(baseUrl+'/inventory/item/rack?' + request ).then(function(data) {
            $scope.data.racks=data.data.rack;
        });
    }

    oTable = $('#pallet_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
            data: function(d) {
                d.warehouse_id = $scope.formData.warehouse_id
                d.customer_id = $scope.formData.customer_id
                d.no_surat_jalan = $scope.detailData.no_surat_jalan
                if($scope.detailData != undefined) {
                    if($scope.detailData.storage_type == 'PICKING')
                        d.is_picking_area = 1
                    else
                        d.rack_id = $scope.detailData.rack_id
                }
            }
        },
        columns:[
        {
            data:"action_choose",
            orderable:false,
            searchable:false,
            className:"text-center"
        },
        {data:"code",name:"code"},
        {data:"name",name:"name"},
        {data:"barcode",name:"barcode", className : 'hidden'},
        {data:"piece.name",name:"piece.name"},
        {data:"description",name:"description"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });


  $scope.adjustSizeTotal = function() {
      $scope.detailData.total_tonase = ($scope.detailData.weight || 0) * ($scope.detailData.total_item || 0)
      $scope.detailData.total_volume = ($scope.detailData.long || 0) * ($scope.detailData.high || 0) * ($scope.detailData.wide || 0) * ($scope.detailData.total_item || 0) / 1000000
  }

    $scope.cariPallet=function() {
        if (!$scope.formData.warehouse_id) {
            toastr.error("Anda harus memilih gudang terlebih dahulu!","Maaf!")
            return null;
        }
        if (!$scope.formData.customer_id) {
            toastr.error("Anda harus memilih customer terlebih dahulu!","Maaf!")
            return null;
        }

        if (!$scope.detailData.warehouse_receipt_id) {
            toastr.error("Anda harus memilih No TTB terlebih dahulu!","Maaf!")
            return null;
        }

        if (!$scope.detailData.storage_type) {
            toastr.error("Anda harus memilih tipe penyimpanan terlebih dahulu!","Maaf!")
            return null;
        }
        else {

            if ($scope.detailData.storage_type == 'RACK') {
                if (!$scope.detailData.rack_id) {
                    toastr.error("Anda harus memilih rak terlebih dahulu!","Maaf!")
                    return null;
                }
            }
        }
        $('#modalItemWarehouse').modal()
        oTable.ajax.reload();
    }



    $scope.choosePallet=function(json) {
        $('#modalItemWarehouse').modal('hide')
        $scope.detailData.item_id=json.id
        $scope.detailData.imposition=parseInt(json.imposition)
        $scope.detailData.item_name=json.name+' ('+json.code+')'
        $scope.detailData.item_code=json.code
        $scope.detailData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
        $scope.detailData.stock=parseInt(json.qty)
        $scope.detailData.total_item=parseInt(json.qty)
        $scope.detailData.barcode=json.barcode
        $scope.detailData.long=json.long
        $scope.detailData.wide=json.wide
        $scope.detailData.high=json.height
    }

    $scope.chooseSuratJalan = function() {
        var warehouse_receipt_id = $scope.detailData.warehouse_receipt_id;
        var storage_type = $scope.detailData.storage_type;
        $scope.detailData = {};
        $scope.detailData.warehouse_receipt_id = warehouse_receipt_id;
        $scope.detailData.storage_type = storage_type;
    }

    $scope.chooseRack = function() {
        var unit;
        for(x in $scope.detailData) {
            if(x != 'rack_id' && x != 'warehouse_receipt_id' && x != 'storage_type') {
                $scope.detailData[x] = null;

            }
        }
    }

    $scope.getSuratJalan = function() {
        $scope.detailData = {};

        var request = {
            warehouse_id : $scope.formData.warehouse_id,
            customer_id : $scope.formData.customer_id
        };
        request = $.param(request);
        $http.get(baseUrl+'/inventory/item/surat_jalan?' + request).then(function(data) {
            $scope.data.warehouse_receipt=data.data.warehouse_receipt;
        });
    }

    inbound_datatable = $('#inbound_datatable').DataTable({
        scrollX:false,
        'width' : '100%',
        'drawCallback' : function() {
            setTimeout(function(){

                $scope.validateForm();
            }, 400);
        },
        columns : [


        { 'data' : 'no_carrier' },
        { 'data' : 'no_container' },

        { 'data' : 'no_seal' },
        { 'data' : 'driver_name' },
        {
            'className' : 'text-center',
            'data' : function(resp) {
                var outp = '<a class="edit_carrier_btn" ng-click="editCarrierInbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                outp += '<a class="delete_carrier_btn" ng-click="deleteCarrierInbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

                return outp;
            }
        }
        ],
        rowCallback: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.handlingCreate = function(){
        $http.get(baseUrl+'/operational_warehouse/handling/create').then(function(data) {
            $scope.data_carrier = data.data;
            $scope.warehouse = data.data.warehouse;
        }, function() {
            $scope.handlingCreate()
        });
    }
    $scope.handlingCreate()

    $scope.getItem = function() {
        if($scope.formData.warehouse_id != null && $scope.formData.customer_id != null) {

            var request = {
                'warehouse_id' : $scope.formData.warehouse_id,
                'customer_id' : $scope.formData.customer_id,
                'is_picking_area' : 1
            }

            request = $.param(request);
            $http.get(baseUrl+'/inventory/item?' + request).then(function(data) {
                $scope.data.item_warehouse = data.data.item_warehouse;
                $('.ibox-content').removeClass('sk-loading');
            });
        }
    }

    $scope.appendInboundDelivery = function() {
        $scope.judulDetailPengangkut = "Tambah Detail Pengangkut";
        $scope.detailPengangkut = {};
        $scope.detailPengangkut.type = 'inbound';
        $('#modalPengangkut').modal('show');
    }

    $scope.appendOutboundDelivery = function() {
        editedRow = null;
        $scope.judulDetailPengangkut = "Tambah Detail Pengangkut";
        $scope.detailPengangkut = {};
        $scope.detailPengangkut.type = 'outbond';
        $('#modalPengangkut').modal('show');
    }

    $scope.deleteCarrierInbound = function(dom) {
        var row = inbound_datatable.row( $(dom).parents('tr')[0] ).remove().draw();
    }

    $scope.editCarrierInbound = function(dom) {
        $scope.judulDetailPengangkut = "Edit Detail Pengangkut";
        editedRow = inbound_datatable.row($(dom).parents('tr')[0]);
        var data = editedRow.data();
        $scope.detailPengangkut = data;
        $('#modalPengangkut').modal('show');
    }

    $scope.editCarrierOutbound = function(dom) {
        $scope.judulDetailPengangkut = "Edit Detail Pengangkut";
        editedRow = outbound_datatable.row($(dom).parents('tr')[0]);
        var data = editedRow.data();
        $scope.detailPengangkut = data;
        $('#modalPengangkut').modal('show');
    }

    $scope.carrierSave = function() {
        if(editedRow == null){

            if( $scope.detailPengangkut.type == 'inbound' ) {
                inbound_datatable.row.add($scope.detailPengangkut).draw();
            }
            else {
                outbound_datatable.row.add($scope.detailPengangkut).draw();
            }
        }
        else {
            console.log(editedRow);
            if( $scope.detailPengangkut.type == 'inbound' ) {
                inbound_datatable.row(editedRow).data($scope.detailPengangkut).draw();
            }
            else {
                outbound_datatable.row(editedRow).data($scope.detailPengangkut).draw();
            }
            editedRow = null;
        }
        $('#modalPengangkut').modal('hide');
    }

    var wodTable = $('#wo_datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: false,
        order:[[1,'desc']],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/marketing/work_order_detail_datatable',
            data: function(d) {
                d.customer_id=$scope.formData.customer_id;
                d.is_done=0;
                d.filter_qty=1;
                d.company_id=compId;
                d.service_type_id=13;
            }
        },
        columns:[
        {data:"action_choose",name:"action_choose",className:"text-center"},
        {data:"code",name:"code"},
        {data:"service",name:"service"},
        {data:"trayek",name:"trayek", className : 'hidden'},
        {data:"commodity",name:"commodity", className : 'hidden'},
        {
            data:null,
            render:function(resp) {
                if(parseInt(resp.is_customer_price) == 1) {
                    return 'Tarif Customer';
                }
                else {
                    return resp.type_tarif_name;
                }
            },
            name:"type_tarif_name"
        },
        {data:"satuan",name:"satuan", className : 'hidden'},
        {data:"qty_leftover",name:"qty_leftover", className : 'text-right'},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $http.get(baseUrl+'/contact/contact/create').then(function(data) {
        $scope.data2=data.data;
    });

    $scope.submitSave=function() {
        $scope.disBtn=true;

        $.ajax({
            type: "post",
            url: baseUrl+'/contact/contact?_token='+csrfToken,
            data: $scope.formSave,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                $('#modalContact').modal('hide');
                toastr.success("Data Berhasil Disimpan");
                $scope.contact_address=[]
                $http.get(baseUrl+'/operational/job_order/cari_address/'+$scope.formData.customer_id).then(function(data) {
                    angular.forEach(data.data.address,function(val,i) {
                        $scope.contact_address.push(
                            {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                            )
                    });
                });
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});
    }

    $scope.addContact=function() {
        var customer=$rootScope.findJsonId($scope.formData.customer_id,$scope.data.customer);
        $scope.formSave={}
        $scope.formSave.company_id=customer.company_id
        $scope.formSave.job_order_customer_id=customer.id
        $scope.formSave.is_pegawai=0
        $scope.formSave.is_investor=0
        $scope.formSave.is_pelanggan=0
        $scope.formSave.is_asuransi=0
        $scope.formSave.is_supplier=0
        $scope.formSave.is_depo_bongkar=0
        $scope.formSave.is_helper=0
        $scope.formSave.is_driver=0
        $scope.formSave.is_vendor=0
        $scope.formSave.is_sales=0
        $scope.formSave.is_kurir=0
        $scope.formSave.is_pengirim=1
        $scope.formSave.is_penerima=1
        $scope.formSave.is_pkp=0
        $('#modalContact').modal('show');
    }

    $scope.cariWO=function() {
        if (!$scope.formData.customer_id) {
            return toastr.error("Anda Harus Memilih Customer");
        }
        wodTable.ajax.reload(function() {
            $('#modalWO').modal('show');
        });
    }

    $scope.chooseWO=function(jsn) {
// console.log(jsn);
$scope.formData.type_tarif=jsn.type_tarif;
$scope.formData.work_order_id=jsn.id_wo;
$scope.company_id=jsn.company_id;
$scope.company_warehouse = [];
var unit;
for(x in $scope.warehouse) {
    unit = $scope.warehouse[x];
// if(unit.company_id == $scope.company_id) {
    $scope.company_warehouse.push(unit);
// }
}
$scope.changeType(jsn.type_tarif,jsn.code+' - '+jsn.service,jsn.id_wo,jsn.id_wod);
if (jsn.type_tarif==1) {
    $scope.formData.quotation_detail_id=jsn.pq_id;
    $scope.changeTypeKontrak(jsn.pq_id);
} else {
    $scope.formData.price_list_id=jsn.pq_id;
    $scope.changeServiceDiv($rootScope.findJsonId(jsn.service_id,$scope.data.services).service_type_id,jsn.service_id)
    $scope.cari_price_list(jsn.pq_id);
}
$('#modalWO').modal('hide');
}

$scope.cari_price_list=function(id) {
    $http.get(baseUrl+'/operational/job_order/cari_price_list/'+id).then(function(data) {
        var ikon=data.data;
        $scope.formData.route_id=ikon.route_id;
        $scope.formData.commodity_id=ikon.commodity_id;
        $scope.formData.moda_id=ikon.moda_id;
        $scope.formData.vehicle_type_id=ikon.vehicle_type_id;
        $scope.formData.total_unit=1;
        $scope.detailData.imposition=1;
        $scope.formData.container_type_id=ikon.container_type_id;
        $scope.formData.wo_customer='';
        $scope.detailData.piece_id=(ikon.piece_id?ikon.piece_id:$scope.data.piece[0].id);
        $scope.formData.piece_id=ikon.piece_id;
    });
}

$scope.jobOrderCreate = function() {
    $http.get(baseUrl+'/operational/job_order/create').then(function(data) {
        $scope.data=data.data;
    }, function(){
        $scope.jobOrderCreate()
    });
} 
$scope.jobOrderCreate()

// trigger jika combobox jenis layanan diganti
// jangan di inject data hanya show div saja
$scope.changeServiceDiv=function(stype,service,fromIq) {
// if (stype!=1 || stype!=3) {
//   return toastr.error("Mohon Pilih Layanan dengan Benar!");
// }
$scope.div_main=true;
$scope.formData.service_id=service;
$scope.formData.service_type_id=stype;
if (!fromIq) {
    $scope.formData.sender_id=null;
    $scope.formData.receiver_id=null;
    $scope.formData.moda_id=null;
    $scope.formData.vehicle_type_id=null;
    $scope.formData.commodity_id=null;
    $scope.formData.container_type_id=null;
    $scope.formData.wo_customer='-';
    $scope.formData.shipment_date=dateNow;
    $scope.formData.description='-';
    $scope.formData.total_unit=1;
}
$scope.formData.shipment_date=dateNow;
$scope.formData.detail=[];
$scope.urut=0;

$('#ftl_append tbody').html("");
$('#retail_append tbody').html("");
if (stype==1) {
// Pengirima Retail
$scope.div_sender=true;
$scope.div_receive=true;
$scope.div_moda=true;
$scope.div_armada=true;
$scope.div_trayek=true;
$scope.div_commodity=true;
$scope.div_shipment_date=true;
$scope.div_unit=false;
$scope.div_container=false;
$scope.div_table_ftl=false;
$scope.div_detail_ftl=false;
$scope.div_detail_retail=true;
$scope.div_table_retail=true;
$scope.detailData.imposition=1
$scope.div_jasa=false;
$scope.div_document=false;

$scope.resetDetailRetail()
} else if(stype==3) {
    $scope.div_sender=true;
    $scope.div_receive=true;
    $scope.div_moda=false;
    $scope.div_armada=true;
    $scope.div_trayek=true;
    $scope.div_commodity=true;
    $scope.div_shipment_date=true;
    $scope.div_unit=true;
    $scope.div_container=false;
    $scope.div_table_ftl=true;
    $scope.div_detail_ftl=true;
    $scope.div_detail_retail=false;
    $scope.div_table_retail=false;
    $scope.div_jasa=false;
    $scope.div_document=false;

    $scope.resetDetailFTL()
} else if(stype==4) {
    $scope.div_sender=true;
    $scope.div_receive=true;
    $scope.div_moda=false;
    $scope.div_armada=true;
    $scope.div_trayek=true;
    $scope.div_commodity=true;
    $scope.div_shipment_date=true;
    $scope.div_unit=false;
    $scope.div_container=false;
    $scope.div_table_ftl=true;
    $scope.div_detail_ftl=true;
    $scope.div_detail_retail=false;
    $scope.div_table_retail=false;
    $scope.div_jasa=false;
    $scope.div_document=false;

    $scope.resetDetailFTL()
} else if(stype==2) {
    $scope.div_sender=true;
    $scope.div_receive=true;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_trayek=true;
    $scope.div_commodity=true;
    $scope.div_shipment_date=true;
    $scope.div_unit=true;
    $scope.div_container=true;
    $scope.div_table_ftl=true;
    $scope.div_detail_ftl=true;
    $scope.div_detail_retail=false;
    $scope.div_table_retail=false;
    $scope.div_jasa=false;
    $scope.div_document=false;

    $scope.resetDetailFTL()
} else if(stype==6) {
    $scope.div_sender=false;
    $scope.div_receive=true;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_trayek=false;
    $scope.div_commodity=false;
    $scope.div_shipment_date=false;
    $scope.div_unit=false;
    $scope.div_container=false;
    $scope.div_table_ftl=false;
    $scope.div_detail_ftl=false;
    $scope.div_detail_retail=false;
    $scope.div_table_retail=false;
    $scope.div_document=true;
    $scope.div_jasa=false;
} else if(stype==7) {
    $scope.div_sender=false;
    $scope.div_receive=true;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_trayek=false;
    $scope.div_commodity=false;
    $scope.div_shipment_date=false;
    $scope.div_unit=false;
    $scope.div_container=false;
    $scope.div_table_ftl=false;
    $scope.div_detail_ftl=false;
    $scope.div_detail_retail=false;
    $scope.div_table_retail=false;
    $scope.div_document=false;
    $scope.div_jasa=true;
}
}

$scope.resetDetailRetail=function() {
    $scope.detailData={}
    $scope.detailData.imposition=1;
    $scope.detailData.item_name='GENERAL CARGO';
    $scope.detailData.total_item=1;
    $scope.detailData.total_tonase=0;
    $scope.detailData.total_volume=0;
    $scope.detailData.description='-';
}
$scope.resetDetailFTL=function() {
    $scope.detailData={}
    $scope.detailData.reff_no='-';
    $scope.detailData.manifest_no="-";
    $scope.detailData.item_name="GENERAL CARGO";
    $scope.detailData.total_item=1;
    $scope.detailData.total_tonase=0;
    $scope.detailData.total_volume=0;
    $scope.detailData.description="-";
}

// trigger jika combobox tipe tarif digati
$scope.changeType=function(id,code,id_wo,id_wod) {
// console.log(id);
if (!$scope.formData.customer_id) {
    toastr.error("Anda Belum Memilih Customer!","Maaf !");
    return null;
}
var cid=$scope.formData.customer_id;
$scope.formData={};
$scope.formData.customer_id=cid;
$scope.formData.type_tarif=id;
$scope.formData.work_order_name=code;
$scope.formData.work_order_id=id_wo;
$scope.formData.work_order_detail_id=id_wod;
$scope.div_main=false;

if (id==1) {
    $scope.div_item_kontrak=true;
    $scope.div_type_layanan=false;
} else {
    $scope.work_orders=[
    {id:0,name:"Buat WO Baru"}
    ];
    $http.get(baseUrl+'/operational/job_order/cari_wo/'+$scope.formData.customer_id,{params:{type_tarif:$scope.formData.type_tarif,quotation_detail_id:$scope.formData.quotation_detail_id}}).then(function(data) {
        angular.forEach(data.data.wo,function(val,i) {
            $scope.work_orders.push(
                {id:val.id,name:val.code}
                );
        })
    });

    $scope.div_item_kontrak=false;
    $scope.div_type_layanan=true;
}
}

$scope.urut=0;
$scope.formData.detail=[];
$scope.appendFTL=function() {
    var html="";

    html+="<tr id='rows-"+$scope.urut+"'>";
    html+="<td>"+($scope.detailData.reff_no?$scope.detailData.reff_no:'-')+"</td>";
    html+="<td>"+($scope.detailData.manifest_no?$scope.detailData.manifest_no:'-')+"</td>";
    html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";
    html+="<td>"+($scope.detailData.total_item?$scope.detailData.total_item:0)+" "+$rootScope.findJsonId($scope.detailData.piece_id,$scope.data.piece).name+"</td>";
    html+="<td>"+($scope.detailData.total_volume?$scope.detailData.total_volume:0)+"</td>";
    html+="<td>"+($scope.detailData.total_tonase?$scope.detailData.total_tonase:0)+"</td>";
    html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
    html+="<td><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
    html+="</tr>";

    $scope.formData.detail.push(
    {
        reff_no:($scope.detailData.reff_no?$scope.detailData.reff_no:'-'),
        manifest_no:($scope.detailData.manifest_no?$scope.detailData.manifest_no:'-'),
        item_name:($scope.detailData.item_name?$scope.detailData.item_name:'-'),
        total_item:($scope.detailData.total_item?$scope.detailData.total_item:0),
        total_volume:($scope.detailData.total_volume?$scope.detailData.total_volume:0),
        total_tonase:($scope.detailData.total_tonase?$scope.detailData.total_tonase:0),
        description:($scope.detailData.description?$scope.detailData.description:'-'),
        piece_id:($scope.detailData.piece_id?$scope.detailData.piece_id:null),
    }
    )
    $('#ftl_append tbody').append($compile(html)($scope));
    $scope.hitungAppend()
    $scope.urut++;
}

$scope.formData.detailVehicle=[];
vehicle_datatable = $('#vehicle_append').DataTable();
$scope.appendVehicle=function() {
    var html="";

    html+="<tr id='rows-"+$scope.urut+"'>";
    html+="<td>"+($scope.vehicleData.reff_no?$scope.vehicleData.reff_no:'-')+"</td>";
    html+="<td>"+($scope.vehicleData.manifest_no?$scope.vehicleData.manifest_no:'-')+"</td>";
    html+="<td>"+($scope.vehicleData.item_name?$scope.vehicleData.item_name:'-')+"</td>";
    html+="<td>"+($scope.vehicleData.total_item?$scope.vehicleData.total_item:0)+" "+$rootScope.findJsonId($scope.vehicleData.piece_id,$scope.data.piece).name+"</td>";
    html+="<td>"+($scope.vehicleData.total_volume?$scope.vehicleData.total_volume:0)+"</td>";
    html+="<td>"+($scope.vehicleData.total_tonase?$scope.vehicleData.total_tonase:0)+"</td>";
    html+="<td>"+($scope.vehicleData.description?$scope.vehicleData.description:'-')+"</td>";
    html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
    html+="</tr>";

    $scope.formData.detailVehicle.push(
    {
        reff_no:($scope.detailData.reff_no?$scope.detailData.reff_no:'-'),
        manifest_no:($scope.detailData.manifest_no?$scope.detailData.manifest_no:'-'),
        item_name:($scope.detailData.item_name?$scope.detailData.item_name:'-'),
        total_item:($scope.detailData.total_item?$scope.detailData.total_item:0),
        total_volume:($scope.detailData.total_volume?$scope.detailData.total_volume:0),
        total_tonase:($scope.detailData.total_tonase?$scope.detailData.total_tonase:0),
        description:($scope.detailData.description?$scope.detailData.description:'-'),
        piece_id:($scope.detailData.piece_id?$scope.detailData.piece_id:null),
    }
    )
    $('#ftl_append tbody').append($compile(html)($scope));
    $scope.hitungAppend()
    $scope.urut++;
}
$scope.appendRetail=function() {
    var item_warehouse = $rootScope.findJsonId($scope.detailData.item_id,$scope.formData.detail);
    var warehouse_receipt_code = $scope.data.warehouse_receipt.find(x => x.id == $scope.detailData.warehouse_receipt_id).code
    if($scope.detailData.storage_type != 'RACK') {
        var rack_name = 'Picking Area';
    }
    else {
        var rack = $rootScope.findJsonId($scope.detailData.rack_id, $scope.data.racks);
        var rack_name = rack['code'];
    }
    if(item_warehouse.id != undefined) {
        var tr = $('tr#rows-' + item_warehouse.urut);
        console.log("item_warehouse.qty : " + item_warehouse.qty);
        console.log("$scope.detailData.total_item : " + $scope.detailData.total_item);
        var new_qty = parseInt(item_warehouse.total_item) + parseInt($scope.detailData.total_item);
        var new_volume = parseInt(item_warehouse.total_volume) + parseInt($scope.detailData.total_volume);
        var new_tonase = parseInt(item_warehouse.total_tonase) + parseInt($scope.detailData.total_tonase);

        html = '';
        html+="<td>"+(warehouse_receipt_code)+"</td>";
        html+="<td>"+rack_name+"</td>";
        html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";

        html+="<td class='text-right'>"+new_qty+" Item</td>";
        html+="<td class='text-right'>"+new_volume+"</td>";
        html+="<td class='text-right'>"+new_tonase+"</td>";
        html+="<td>"+$rootScope.findJsonId($scope.detailData.imposition,$scope.imposition).name+"</td>";
        html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
        html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>";

        tr.html(html);
        $compile(tr[0])($scope);
    }
    else {
        var html="";

        html+="<tr id='rows-"+$scope.urut+"'>";
        html+="<td>"+warehouse_receipt_code+"</td>";
        html+="<td>"+rack_name+"</td>";
        html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";

        html+="<td class='text-right'>"+($scope.detailData.total_item?$scope.detailData.total_item:0)+" Item</td>";
        html+="<td class='text-right'>"+($scope.detailData.total_volume?$scope.detailData.total_volume:0)+"</td>";
        html+="<td class='text-right'>"+($scope.detailData.total_tonase?$scope.detailData.total_tonase:0)+"</td>";
        html+="<td>"+$rootScope.findJsonId($scope.detailData.imposition,$scope.imposition).name+"</td>";
        html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
        html+="<td><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
        html+="</tr>";

        $scope.formData.detail.push(
        {
            urut:$scope.urut,
            id:$scope.detailData.item_id,
            rack_id:$scope.detailData.rack_id,
            warehouse_receipt_detail_id:$scope.detailData.warehouse_receipt_detail_id,
            storage_type:$scope.detailData.storage_type,
            item_name:($scope.detailData.item_name?$scope.detailData.item_name:'-'),
            barcode:($scope.detailData.barcode?$scope.detailData.barcode:''),
            total_item:($scope.detailData.total_item?$scope.detailData.total_item:0),
            total_volume:($scope.detailData.total_volume?$scope.detailData.total_volume:0),
            total_tonase:($scope.detailData.total_tonase?$scope.detailData.total_tonase:0),
            description:($scope.detailData.description?$scope.detailData.description:'-'),
            piece_id:($scope.detailData.piece_id?$scope.detailData.piece_id:null),
            imposition:$scope.detailData.imposition,
            long:$scope.detailData.long,
            wide:$scope.detailData.wide,
            high:$scope.detailData.high
        }
        )
        $('#retail_append tbody').append($compile(html)($scope));
        $scope.hitungAppend()
        $scope.detailData = {};
        $scope.urut++;
    }

}

$scope.deleteAppend=function(id) {
    $('#rows-'+id).remove()
    $scope.formData.detail.splice(id, 1)
    $scope.hitungAppend()
    toastr.success('Item berhasil dihapus');
}

$scope.hitungAppend=function() {
    $scope.formData.total_append=0
    if ($scope.formData.detail) {
        angular.forEach($scope.formData.detail, function(val,i) {
            if (!val) {
                return;
            }
            $scope.formData.total_append+=1
        })
    }

    if($scope.formData.detail.length == 0) {
        $scope.isItemExists = false;
    }
    else {
        $scope.isItemExists = true;

    }

    $scope.validateForm();
}

//trigger jika combobox customer diganti
$scope.changeCustomer=function(id) {
    $scope.formData={};
    $scope.formData.detail=[];
    $scope.formData.customer_id=id;
    $scope.contact_address=[];
    $scope.quotation_details=[];
    inbound_datatable.rows().clear().draw();
    $scope.changeType(1,'',null,null);
//cari WO dan alamat kirim - terima
$http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
    angular.forEach(data.data.address,function(val,i) {
        $scope.contact_address.push(
            {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
            )
    });
});

//cari semua kontrak dari customer_id
// $http.get(baseUrl+'/operational/job_order/cari_item_kontrak/'+id).then(function(data) {
//   angular.forEach(data.data.item,function(val,i) {
//     $scope.quotation_details.push(
//       {id:val.id,name:val.sname,group:val.code}
//     )
//   })
// });
}

//trigger jika item kontrak diganti
$scope.changeTypeKontrak=function(id) {
    $scope.work_orders=[
    {id:0,name:"Buat WO Baru"}
    ];
    $http.get(baseUrl+'/operational/job_order/cari_wo/'+$scope.formData.customer_id,{params:{type_tarif:$scope.formData.type_tarif,quotation_detail_id:$scope.formData.quotation_detail_id}}).then(function(data) {
        angular.forEach(data.data.wo,function(val,i) {
            $scope.work_orders.push(
                {id:val.id,name:val.code}
                );
        })
    });

    $http.get(baseUrl+'/operational/job_order/detail_kontrak/'+id).then(function(data) {
        var ikon=data.data;
        $scope.changeServiceDiv(ikon.service_type_id,ikon.service_id,true);
        $scope.formData.route_id=ikon.route_id;
        $scope.formData.commodity_id=ikon.commodity_id;
        $scope.formData.moda_id=ikon.moda_id;
        $scope.formData.vehicle_type_id=ikon.vehicle_type_id;
        $scope.formData.total_unit=1;
        $scope.detailData.imposition=ikon.imposition;
        $scope.formData.container_type_id=ikon.container_type_id;
        $scope.formData.wo_customer=ikon.header.no_inquery;
        $scope.detailData.piece_id=(ikon.piece_id?ikon.piece_id:$scope.data.piece[0].id);
        $scope.formData.piece_id=ikon.piece_id;
    });
}

$scope.disBtn=false;
$scope.submitForm=function() {
    $scope.disBtn=true;
    var inbound_carrier = inbound_datatable.data().toArray();
    $scope.formData.detail_carrier =  inbound_carrier;
    $http.post(baseUrl+'/operational_warehouse/stuffing',$scope.formData).then(function(data) {
        $state.go('operational_warehouse.stuffing');
        toastr.success("Data Berhasil Disimpan!");
        $scope.disBtn=false;
    }, function(error) {
        $scope.disBtn=false;
        if (error.status==422) {
            var det="";
            angular.forEach(error.data.errors,function(val,i) {
                det+="- "+val+"<br>";
            });
            toastr.warning(det,error.data.message);
        } else {
            toastr.error(error.data.message,"Error Has Found !");
        }
    });
}
});
app.controller('opWarehouseStuffingShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Stuffing";

    if ($state.current.name=="operational_warehouse.stuffing.show") {
        $state.go('operational_warehouse.stuffing.show.detail',{},{location:'replace'});
    }

});
app.controller('opWarehouseStuffingEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Edit Stuffing";
    $scope.formData={}
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.data = {};
    editedRow = null;

    $http.get(baseUrl+'/operational_warehouse/stuffing/'+$stateParams.id+'/edit').then(function(data) {
        $scope.item=data.data.item;
        $scope.data.staff_gudang = data.data.staff_gudang;
        $scope.data.warehouse = data.data.warehouse;
        var description = $scope.item.description
        $scope.formData = Object.assign($scope.item, data.data.stuffing);
        $scope.formData.description = description;
        var shipment_date = $scope.formData.shipment_date;
        shipment_date = shipment_date.replace(/(\d+)-(\d+)-(\d+).+/, '$3-$2-$1');
        $scope.formData.shipment_date = shipment_date;

    });

    $scope.companyChange=function(sts) {
        $scope.customers=[]
        $scope.warehouses=[]
        angular.forEach($scope.data.customer, function(val,i) {
            if (sts == val.company_id) {
                $scope.customers.push({
                    id: val.id,
                    name: val.name
                })
            }
        });

        angular.forEach($scope.data.warehouse,function(val,i) {
            if (sts == val.company_id) {
                $scope.warehouses.push(
                    {id:val.id,name:val.name}
                    )
            }
        });

    }

    $scope.customerChange=function(id) {
        $scope.contact_address=[]
        $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
            angular.forEach(data.data.address,function(val,i) {
                $scope.contact_address.push(
                    {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                    )
            });
        });
    }

    $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:4,name:'Kubikasi & Tonase'},
    ]

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.put(baseUrl+'/operational_warehouse/stuffing/'+$stateParams.id,$scope.formData).then(function(data) {
            $state.go('operational_warehouse.stuffing');
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

});


app.controller('opWarehouseStuffingShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Detail Stuffing";
    $scope.data = {};
    $scope.itemData = {};
    $scope.state=$state;
    $(".clockpick").clockpicker({
        placement:'right',
        autoclose:true,
        donetext:'DONE',
    });
    editedRow = null;
    $scope.transaction_id = $stateParams.id;


  $scope.adjustSizeTotal = function() {
      $scope.itemData.total_tonase = ($scope.itemData.weight || 0) * ($scope.itemData.total_item || 0)
      $scope.itemData.total_volume = ($scope.itemData.long || 0) * ($scope.itemData.high || 0) * ($scope.itemData.wide || 0) * ($scope.itemData.total_item || 0) / 1000000
  }
    
    $('#modalItemWarehouse').on('hidden.bs.modal', function(){
        setTimeout(function(){
            $('#modalItem').modal('show')
        }, 200)
    })

  $scope.resetItem = function() {
      var warehouse_receipt_id = $scope.itemData.warehouse_receipt_id
      var storage_type = $scope.itemData.storage_type
      $scope.itemData = {}
      $scope.itemData = {
          'warehouse_receipt_id': warehouse_receipt_id,
          'storage_type': storage_type
      }
  }

    $scope.getSuratJalan = function() {
// show detail surat jalan
var request = {
    warehouse_id : $scope.item.warehouse.id,
    customer_id : $scope.item.customer.id
};
request = $.param(request);
$http.get(baseUrl+'/inventory/item/surat_jalan?' + request).then(function(data) {
    $scope.data.warehouse_receipt=data.data.warehouse_receipt;
});
}

$scope.getRack = function() {

    var request = {
        'warehouse_id' : $scope.item.warehouse.id,
        'no_surat_jalan' : $scope.itemData.no_surat_jalan
    }
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/rack?' + request ).then(function(data) {
        $scope.racks=data.data.rack;
    });
}

$scope.validasiItem = function() {
    var kpi_status = $rootScope.findJsonId($scope.statusData.kpi_status_id, $scope.kpi_status);
    if(kpi_status.is_done == 1 ) {

        $('#modalStatus').modal('hide');
        setTimeout(function(){
            $('#modalValidasiItem').modal('show');
        }, 500);

    }
    else {
        $scope.submitStatus();
    }
}

$('.modal').on('hidden.bs.modal', function(){
    $('.modal-backdrop').remove()
});


$scope.cariPallet=function() {
// show detail cari pallet

if (!$scope.itemData.warehouse_receipt_id) {
    toastr.error("Anda harus memilih No TTB terlebih dahulu!","Maaf!")
    return null;
}

$('#modalItem').modal('hide')
setTimeout(function(){

    $('#modalItemWarehouse').modal()
}, 600);
pallet_datatable.ajax.reload();
}


$scope.choosePallet=function(json) {
//  show detail choose pallet
$scope.itemData.item_id=json.id
$scope.itemData.item_name=json.name+' ('+json.code+')'
$scope.itemData.item_code=json.code
$scope.itemData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
$scope.itemData.imposition=parseInt(json.imposition)
$scope.itemData.stock=parseInt(json.qty)
$scope.itemData.total_item=parseInt(json.qty)
$scope.itemData.barcode=json.barcode
$scope.itemData.long=json.long
$scope.itemData.wide=json.wide
$scope.itemData.high=json.height
$scope.itemData.rack_id=parseInt(json.rack_id)
$('#modalItemWarehouse').modal('hide')
setTimeout(function(){
    $('#modalItem').modal();
}, 600);
}

$scope.cost_journal=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/cost_journal',{id:$stateParams.id}).then(function(data) {
// $('#revisiModal').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya telah dijurnal !");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}

$scope.status=[
{id:1,name:'Belum Diajukan'},
{id:2,name:'Diajukan Keuangan'},
{id:3,name:'Disetujui Keuangan'},
{id:4,name:'Ditolak'},
{id:5,name:'Diposting'},
{id:6,name:'Revisi'},
{id:7,name:'Diajukan'},
{id:8,name:'Disetujui'},
]
$scope.imposition=[
{id:1,name:'Kubikasi'},
{id:2,name:'Tonase'},
{id:3,name:'Item'},
{id:4,name:'Borongan'},
]
$scope.type_cost=[
{id:1,name:'Biaya Operasional'},
{id:2,name:'Reimbursement'},
]

$http.get(baseUrl+'/operational_warehouse/handling/create').then(function(data) {
    $scope.data_carrier = data.data;
    $scope.warehouse = data.data.warehouse;
});

$scope.ajukanAtasan=function(id) {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/ajukan_atasan',{id:id}).then(function(data) {
// $('#revisiModal').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya Telah Diajukan !");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}
$scope.approveAtasan=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/approve_atasan',{id:id}).then(function(data) {
// $('#revisiModal').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya Telah Disetujui !");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}

$scope.rejectAtasan=function(id) {
    var cofs=confirm("Apakah anda yakin?");
    if (cofs) {
        $scope.disBtn=true;
        $http.post(baseUrl+'/operational/job_order/reject_atasan',{id:id}).then(function(data) {
// $('#revisiModal').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya Telah Ditolak !");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
    }
}

$scope.appendInboundDelivery = function() {
    $scope.judulDetailPengangkut = "Tambah Detail Pengangkut";
    $scope.detailPengangkut = {};
    $scope.detailPengangkut.type = 'inbound';
    $('#modalPengangkut').modal('show');
}

$scope.appendOutboundDelivery = function() {
    editedRow = null;
    $scope.judulDetailPengangkut = "Tambah Detail Pengangkut";
    $scope.detailPengangkut = {};
    $scope.detailPengangkut.type = 'outbond';
    $('#modalPengangkut').modal('show');
}

$scope.deleteCarrierInbound = function(dom) {
    var is_delete = confirm('Apakah anda ingin menghapus detail kendaraan ini ?');
    if(is_delete == true) {

        var row =  $(dom).parents('tr')[0];
        var data = inbound_datatable.row(row).data();
        var param = $.param(data);
        $http.post(baseUrl+'/operational_warehouse/stuffing/delete_vehicle_detail/?'+param,{_token:csrfToken}).then(function success(data) {
// $state.reload();
inbound_datatable.row(row).remove().draw();
toastr.success("Detail kendaraan Berhasil Dihapus!");
}, function error(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
});
    }
}

$scope.editCarrierInbound = function(dom) {
    $scope.judulDetailPengangkut = "Edit Detail Pengangkut";
    editedRow = inbound_datatable.row($(dom).parents('tr')[0]);
    var data = editedRow.data();
    $scope.detailPengangkut = data;
    $('#modalPengangkut').modal('show');
}


$scope.editCarrierOutbound = function(dom) {
    $scope.judulDetailPengangkut = "Edit Detail Pengangkut";
    editedRow = outbound_datatable.row($(dom).parents('tr')[0]);
    var data = editedRow.data();
    $scope.detailPengangkut = data;
    $('#modalPengangkut').modal('show');
}

$scope.carrierSave = function() {
    $scope.disVehicleBtn = true;
    if($scope.detailPengangkut.carrier) {
        $scope.detailPengangkut.carrier_name = $scope.detailPengangkut.carrier.name;
    }

    if(editedRow == null){
        $http.post(baseUrl+'/operational_warehouse/stuffing/store_vehicle_detail/'+$stateParams.id,$scope.detailPengangkut).then(function(data) {
// $state.go('operational.job_order');

$('#modalPengangkut').modal('hide');
$scope.disVehicleBtn = false;
setTimeout(function(){
    $state.reload()
}, 300)

toastr.success("Kendaraan berhasil disimpan!");
$scope.disVehicleBtn=false;
}, function(error) {
    $('#modalPengangkut').modal('hide');
    $scope.disVehicleBtn = false;      if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});

    }
    else {
        console.log(editedRow);
        $http.put(baseUrl+'/operational_warehouse/stuffing/update_vehicle_detail/',$scope.detailPengangkut).then(function(data) {
// $state.go('operational.job_order');
$('#modalPengangkut').modal('hide');
$scope.disVehicleBtn = false;
setTimeout(function(){
    $state.reload()
}, 300)


toastr.success("Kendaraan berhasil disimpan!");
$scope.disVehicleBtn=false;
}, function(error) {
    $('#modalPengangkut').modal('hide');
    $scope.disVehicleBtn = false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});

    }

}

$scope.revision=function(jsn) {
// console.log(jsn);
$scope.revisiData={}
$scope.revisiData.cost_id=jsn.id
$scope.revisiData.cost_type_f=jsn.cost_type
$scope.revisiData.qty=jsn.qty
$scope.revisiData.price=jsn.price
$scope.revisiData.total_price=jsn.total_price
$scope.revisiData.before_revision_cost=jsn.total_price
$scope.revisiData.description=jsn.description
$scope.revisiData.vendor_id=jsn.vendor_id
$('#revisiModal').modal('show');
}

$scope.editService=function(item) {
    $scope.serviceChangeData={}
    $scope.serviceChangeData.old_work_order_detail_id=item.work_order_detail_id
    $('#modalService').modal()
}

$scope.changeServiceSubmit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/change_service/'+$stateParams.id,$scope.serviceChangeData).then(function(data) {
        $('#modalService').modal('hide');
        $timeout(function() {
            $state.reload();
        },1000)
        toastr.success("Data Berhasil Disimpan !");
        $scope.disBtn=false;
    }, function(error) {
        $scope.disBtn=false;
        if (error.status==422) {
            var det="";
            angular.forEach(error.data.errors,function(val,i) {
                det+="- "+val+"<br>";
            });
            toastr.warning(det,error.data.message);
        } else {
            toastr.error(error.data.message,"Error Has Found !");
        }
    });
}

$scope.deleteArmada=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $http.delete(baseUrl+'/operational/job_order/delete_armada/'+id).then(function(data) {
        $state.reload();
        toastr.success("Armada telah dihapus !");
    })
}

$scope.deleteItem=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $http.delete(baseUrl+'/operational/job_order/delete_item/'+id).then(function(data) {
        $state.reload();
        toastr.success("Item Handling telah dihapus !");
    })
}

$scope.editItem=function(val) {
    $scope.itemData={}
    $scope.itemData.detail_id=val.id;
    $scope.itemData.reff_no=val.no_reff;
    $scope.itemData.manifest_no=val.no_manifest;
    $scope.itemData.item_id=val.item_id;
    $scope.itemData.item_name=val.item_name;
    $scope.itemData.warehouse_receipt_id=val.warehouse_receipt_id;
    $scope.itemData.barcode=val.barcode;
    $scope.itemData.long=val.long;
    $scope.itemData.wide=val.wide;
    $scope.itemData.high=val.high;
    $scope.itemData.total_item=val.qty;
    $scope.itemData.imposition=val.imposition;
    $scope.itemData.piece_id=val.piece_id;
    $scope.itemData.total_tonase=val.weight;
    $scope.itemData.total_volume=val.volume;
    $scope.itemData.description=val.description;
    $scope.itemData.is_edit=1;
    if(val.is_picking_area == 1) {
        $scope.itemData.storage_type = 'PICKING';
    }
    else {
        $scope.itemData.storage_type = 'RACK';
        $scope.itemData.rack_id = val.rack_id;

    }
    $scope.getRack();
    var request = {
        warehouse_id : $scope.item.warehouse.id,
        warehouse_receipt_id : val.warehouse_receipt_id,
        rack_id : val.rack_id,
        item_id : val.item_id
    };

    if(val.is_picking_area == 1) {
        request['is_picking_area'] = 1;
    }

    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/cek_stok_warehouse?' + request).then(function(data) {
        $scope.itemData.stock=data.data.stok;
        $scope.itemData.total_item=data.data.stok;
    });
    $("#modalItem").modal();
}

$scope.deleteCost=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
        return null;
    }
    $http.delete(baseUrl+'/operational/job_order/delete_cost/'+id).then(function(data) {
        $state.reload();
        toastr.success("Biaya Order telah dihapus !");
    })
}

$scope.submitRevisi=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/store_revision/'+$scope.revisiData.cost_id,$scope.revisiData).then(function(data) {
// $state.go('operational.job_order');
$('#revisiModal').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya telah direvisi !");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}

$scope.saveSubmission=function(id) {
    var conf=confirm("Apakah anda ingin menyimpan di pengajuan biaya ?");
    if (conf) {
        $http.post(baseUrl+'/operational/job_order/store_submission/'+id).then(function(data) {
            $state.reload()
            toastr.success("Pengajuan Biaya berhasil disimpan!");
        });
    }
}

$scope.detail_approve=[];
$http.get(baseUrl+'/operational_warehouse/stuffing/'+$stateParams.id).then(function(data) {
// show detail fetch stuffing
var item=data.data.item;
var stuffing=data.data.stuffing;
var description = item.description;
$scope.item = Object.assign(item, stuffing);
$scope.item.description = description;
$scope.durasi=data.data.durasi;
$scope.manifest=data.data.manifest;
$scope.detail=data.data.detail;
$scope.piece=data.data.piece;
$scope.vendor=data.data.vendor;
$scope.cost_type=data.data.cost_type;
$scope.cost_detail=data.data.cost_detail;
$scope.receipt_detail=data.data.receipt_detail;
$scope.kpi_status=data.data.kpi_status;
$scope.wo_detail=data.data.wo_detail;
$scope.getSuratJalan();


pallet_datatable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: false,
    ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
        data: function(d) {
            d.warehouse_id = $scope.item.warehouse_id
            d.customer_id = $scope.item.customer.id
            d.warehouse_receipt_id = $scope.itemData.warehouse_receipt_id
            if($scope.itemData.storage_type != 'RACK')
                d.is_picking_area = 1
            else
                d.rack_id = $scope.itemData.rack_id
        }
    },
    columns:[
    {
        data:"action_choose",
        orderable:false,
        searchable:false,
        className:"text-center"
    },
    {data:"code",name:"code"},
    {data:"name",name:"name"},
    {data:"barcode",name:"barcode", className : 'hidden'},
    {data:"piece.name",name:"piece.name"},
    {data:"description",name:"description"},
    ],
    createdRow: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
    }
});


$scope.verifyStock = function(items) {
    var item, good, stock, item_out;
    var tr;
    for(x in items) {
        item = items[x];
        good = $rootScope.findJsonId(item.id, $scope.detail, 'item_id');
        stock = parseInt(item.stock);
        console.log('Stok : ' + stock);
        item_out = parseInt(good.qty)
        console.log('item out : ' + item_out);
        if(item_out > stock) {
            $scope.isStockClear = false;
            tr = item_warehouse.row(x).node();
            $(tr).css('color', 'red');
            $(tr).css('fontWeight', 'bold');
        }
    }
}


item_warehouse = $('#item_warehouse_datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'rt',
    scrollX: false,
    ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/operational_warehouse/validasi_item_datatable/' + $stateParams.id,
    },
    columnDefs : [
    {
        targets : 7,
        orderable : false
    }
    ],
    columns:[
      {data:"item_code",orderable:false,searchable:false},
      {data:"item_name",orderable:false,searchable:false},
      {data:"barcode",name:"barcode", className : 'hidden'},
      {data:"warehouse_receipt_code",orderable:false,searchable:false},
      {data:"warehouse_receipt_code",orderable:false,searchable:false},
      {data:"rack_code",orderable:false,searchable:false},
      {data:"piece_name",orderable:false,searchable:false},
      {
        data:null,
        className : 'text-right',
        render:function(resp) {
          return $filter('number')(resp.stock);
        }
      },
      {
        data:null,
        orderable:false,
        searchable:false,
        className : 'text-right',
        render : function(resp) {
          var item = $rootScope.findJsonId(resp.id, $scope.detail, 'id');
          return $filter('number')(item.qty);
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
        var units = this.api().data().toArray();
        $scope.verifyStock(units);
    }
});

inbound_datatable = $('#inbound_datatable').DataTable({
    data : data.data.stuffing_vehicle,
    columns : [


    { 'data' : 'no_carrier' },
    { 'data' : 'no_container' },

    { 'data' : 'no_seal' },
    { 'data' : 'driver_name' },
    {
        className : 'text-center',
        'data' : function(resp) {
            var outp = '<a class="edit_carrier_btn" ng-click="editCarrierInbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            outp += '<a class="delete_carrier_btn" ng-click="deleteCarrierInbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

            return outp;
        }
    }
    ],
    rowCallback: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
    }
});




angular.forEach($scope.cost_detail, function(val,i) {
    var percent=(val.total_price-val.quotation_costs)/val.quotation_costs*100;
if (val.quotation_costs>0 && val.total_price > 50000000) { //ditambahkan nominal 50jt -> diatas supervisi
    if (val.total_price <= val.quotation_costs) {
        $scope.detail_approve.push({approve_with:1})
    } else if (percent <= 5) {
        $scope.detail_approve.push({approve_with:2})
    } else {
        $scope.detail_approve.push({approve_with:3})
    }
} else {
    if (val.total_price < 50000000) {
// kurang dari 50 juta
$scope.detail_approve.push({approve_with:1})
} else if (val.total_price < 100000000) {
// kurang dari 100 juta
$scope.detail_approve.push({approve_with:2})
} else {
// lebih dari 100 juta
$scope.detail_approve.push({approve_with:3})
}
}
})
});

$scope.addArmada=function() {
    $scope.armadaData={};
    $scope.armadaData.qty=1;
    $('#modalArmada').modal('show');
}
$scope.addItem=function() {
    $scope.itemData={};
    $scope.itemData.reff_no="-";
    $scope.itemData.manifest_no="-";
    $scope.itemData.item_name="GENERAL CARGO";
    $scope.itemData.total_item=1;
    $scope.itemData.imposition=1;
    $scope.itemData.total_tonase=0;
    $scope.itemData.total_volume=0;
    $scope.itemData.description="-";
    $('#modalItem').modal('show');
}

$scope.getDesc=function(description) {
    if(description == null)
    {
        return ''
    }

    return ' : '+s.description
}
$scope.addCost=function() {
    $scope.costData={};
    $scope.costData.is_edit=false;
    $scope.costData.type=1
    $scope.costData.cost_type = 144;
    $scope.costData.price=0
    $scope.costData.qty=1
    $scope.costData.total_price=0
    $scope.titleCost = 'Tambah Biaya'
    $('#modalCost').modal('show');
}
$scope.cost_type_f={}
$scope.changeCT=function(id) {
    $http.get(baseUrl+'/setting/cost_type/'+id).then(function(data) {
        $scope.cost_type_f=data.data.item;

        $scope.costData.vendor_id=$scope.cost_type_f.vendor_id;
        $scope.costData.qty=$scope.cost_type_f.qty;
        $scope.costData.price=$scope.cost_type_f.cost;
        $scope.costData.total_price=$scope.cost_type_f.initial_cost;
    });
}
$scope.calcCTTotalPrice=function(){
    $scope.costData.total_price=$scope.costData.qty*$scope.costData.price
}

$scope.editCost=function(id) {
    $scope.costData={};
    $scope.costData.is_edit=true;
    $scope.costData.id=id;
    $scope.titleCost = 'Edit Biaya'
    $http.get(baseUrl+'/operational/job_order/edit_cost/'+id).then(function(data) {
        var dt=data.data;
// $scope.costData.cost_type=dt.cost_type_id;
$scope.costData.cost_type=parseInt(dt.cost_type_id);
$scope.costData.vendor_id=parseInt(dt.vendor_id);
$scope.costData.qty=dt.qty;
$scope.costData.price=dt.price;
$scope.costData.total_price=dt.total_price;
$scope.costData.description=dt.description;
$scope.costData.type=dt.type;
$('#modalCost').modal('show');
});
}

$scope.addStatus=function(status) {
// console.log(status);
$scope.statusData={};
$scope.statusData.kpi_status_id=status;
$scope.statusData.update_date=dateNow;
$scope.statusData.update_time=timeNow;
$('#modalStatus').modal('show');
}

$scope.addReceipt=function() {
    $scope.receiptData={};
    $scope.receiptData.date_receive=dateNow;
    $('#modalReceipt').modal('show');
}

$scope.disBtn=false;
$scope.submitArmada=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_armada/'+$stateParams.id,$scope.armadaData).then(function(data) {
// $state.go('operational.job_order');
$('#modalArmada').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Armada berhasil ditambahkan!");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}
$scope.submitItem=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_item_warehouse/'+$stateParams.id,$scope.itemData).then(function(data) {
// $state.go('operational.job_order');
$('#modalItem').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Item Barang berhasil disimpan!");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}
$scope.submitCost=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_cost/'+$stateParams.id,$scope.costData).then(function(data) {
// $state.go('operational.job_order');
$('#modalCost').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Biaya berhasil disimpan!");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}
$scope.submitReceipt=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_receipt/'+$stateParams.id,$scope.receiptData).then(function(data) {
// $state.go('operational.job_order');
$('#modalReceipt').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Item Barang berhasil ditambahkan!");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}
$scope.submitStatus=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_status/'+$stateParams.id,$scope.statusData).then(function(data) {
// $state.go('operational.job_order');
$('#modalValidasiItem').modal('hide');
$('#modalStatus').modal('hide');
$timeout(function() {
    $state.reload();
},1000)
toastr.success("Status berhasil diupdate!");
$scope.disBtn=false;
}, function(error) {
    $scope.disBtn=false;
    if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
            det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
    } else {
        toastr.error(error.data.message,"Error Has Found !");
    }
});
}


});
app.controller('opWarehouseStuffingShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Handling";
    $scope.urls=baseUrl;
    $http.get(baseUrl+'/operational/job_order/show_document/'+$stateParams.id).then(function(data) {
        $scope.detail=data.data.detail;
    });
    $scope.formData={}
    $scope.formData.is_customer_view=0

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/operational/job_order/upload_file/'+$stateParams.id+'?_token='+csrfToken,
            contentType: false,
            cache: false,
            processData: false,
            data: new FormData($('#uploadForm')[0]),
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.reload();
                $timeout(function() {
                    $state.reload();
                },1000)
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});
    }

    $scope.delete_file=function(id) {
        var cof=confirm("Apakah Anda Ingin Menghapus File ini ?");
        if (cof) {
            $http.delete(baseUrl+'/operational/job_order/delete_file/'+id).then(function(data) {
                $state.reload()
                toastr.success("Berkas Berhasil Dihapus");
            })
        }
    }

});
app.controller('opWarehouseStuffingShowProses', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Stuffing";
    $scope.urls=baseUrl;

    $http.get(baseUrl+'/operational/job_order/show_status/'+$stateParams.id).then(function(data) {
        $scope.detail=data.data.detail;
        $scope.data=data.data;
    });

    $scope.edit=function(formData) {
// console.log(formData);
$scope.statusData={}
$scope.statusData.id=formData.id
$scope.statusData.name=formData.kpi_status.name
$scope.statusData.kpi_status_id=formData.kpi_status_id
$scope.statusData.update_date=$filter('minDate')(formData.date_update)
$scope.statusData.update_time=$filter('aTime')(formData.date_update)
$scope.statusData.description=formData.description
$('#modalStatus').modal('show');
}

$scope.delete=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
        $http.delete(baseUrl+'/operational/job_order/delete_status/'+ids,{_token:csrfToken}).then(function success(data) {
            $state.reload();
// oTable.ajax.reload();
toastr.success("Data Berhasil Dihapus!");
}, function error(data) {
    toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
});
    }
}

$scope.disBtn=false;
$scope.submitStatus=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/operational/job_order/update_status',$scope.statusData).then(function(data) {
        $('#modalStatus').modal('hide');
        toastr.success("Data Berhasil Disimpan!");
        $scope.disBtn=false;
        $timeout(function() {
            $state.reload();
        },1000)
    }, function(error) {
        $scope.disBtn=false;
        if (error.status==422) {
            var det="";
            angular.forEach(error.data.errors,function(val,i) {
                det+="- "+val+"<br>";
            });
            toastr.warning(det,error.data.message);
        } else {
            toastr.error(error.data.message,"Error Has Found !");
        }
    });
}

});
