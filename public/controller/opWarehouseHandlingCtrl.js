app.controller('opWarehouseHandling', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.source = null
  $rootScope.pageTitle="Handling Job Order";
  $('.ibox-content').addClass('sk-loading');

  $scope.isFilter = false;
  $scope.serviceStatus = [];
  $scope.formData = {}
  $scope.checkData = {}
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
      toastr.success("Handling berhasil dipindahkan ke arsip");
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
        d.kpi_status_name = $scope.formData.kpi_status_name;
        d.is_operational_done=0
        d.is_handling=1
  },
  dataSrc: function(d) {
    $('.ibox-content').removeClass('sk-loading');
    return d.data;
  }
  },
  buttons: [
  {
    'extend' : 'excel',
    'enabled' : true,
    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
    'className' : 'btn btn-default btn-sm',
    'filename' : 'Handling - '+new Date(),
    'sheetName' : 'Data',
    'title' : 'Handling'
  },
  ],

  columns:[
  {data:"checklist",name:"id",sorting:false,className:'text-center'},
  {
      data:null,
      name:"job_orders.code",
      className:'font-bold',
      render : function(resp) {
          if($rootScope.roleList.includes('operational_warehouse.handling.detail')) {
              return '<a ui-sref="operational_warehouse.handling.show({id:' + resp.id + '})">' + resp.code + '</a>'
          } else {
              return resp.code
          } 
      }  
  },
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
            var outp = action.replace(/operational\.job_order/g, 'operational_warehouse.handling');

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
app.controller('opWarehouseHandlingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Handling";
  $('.ibox-content').addClass('sk-loading');

  $scope.formData={};
  $scope.formData.detail = []
  $scope.formData.total_append=0;
  $scope.detailData={};
  $scope.disVehicleBtn = false;
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


  $scope.adjustSizeTotal = function() {
      $scope.detailData.total_tonase = ($scope.detailData.weight || 0) * ($scope.detailData.total_item || 0)
      $scope.detailData.total_volume = ($scope.detailData.long || 0) * ($scope.detailData.high || 0) * ($scope.detailData.wide || 0) * ($scope.detailData.total_item || 0) / 1000000
  }

  $scope.enableBtn = function() {
    $scope.disBtn = false;
  }

  $scope.disableBtn = function() {
    $scope.disBtn = true;
  }

  countValidate = 0;
  $scope.validateForm = function() {

    var detail_length = $scope.formData.detail.length;
    var inbound_vehicle_length = inbound_datatable.data().toArray().length;
    var outbound_vehicle_length = outbound_datatable.data().toArray().length;
    var supervisor_role = $rootScope.roleList.includes('operational_warehouse.handling.create.supervisor');
    if(supervisor_role == true && (!$scope.formData.warehouse_id  || inbound_vehicle_length == 0 || outbound_vehicle_length == 0)) {
      $scope.disableBtn();
    }
    else {
      $scope.enableBtn();
    }

    if($scope.disBtn == true) {
      $('#submitButton').attr('disabled', 'disabled')
    }
    else {
      $('#submitButton').removeAttr('disabled')

    }
  }

  $scope.create = function() {

      $http.get(baseUrl+'/operational_warehouse/handling/create').then(function(data) {
          $scope.data_carrier = data.data;
          $scope.warehouse = data.data.warehouse;
          $('.ibox-content').removeClass('sk-loading');
      }, function(){
          $scope.create()
      });
  }
  $scope.create()

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
      data: function(d) {
        d.warehouse_id = $scope.formData.warehouse_id
        d.customer_id = $scope.formData.customer_id
        d.warehouse_receipt_id = $scope.detailData.warehouse_receipt_id
        d.is_handling_area = 1
      }
    },
    columns:[
      {
        data:"action_choose", 
        className:"text-center",
        orderable:false,
        searchable:false
      },
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"barcode",name:"barcode", className : 'hidden'},
      {data:"piece.name",name:"piece.name"},
      {data:"imposition_name",orderable:false,searchable:false},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

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
    $('#modalItem').modal()
    oTable.ajax.reload();
  }



  $scope.choosePallet=function(json) {
    $('#modalItem').modal('hide')
    $scope.detailData.item_id=json.id
    $scope.detailData.imposition=parseInt(json.imposition)
    $scope.detailData.item_name=json.name+' ('+json.code+')'
    $scope.detailData.item_code=json.code
    $scope.detailData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
    $scope.detailData.item_warehouse = json;
    $scope.detailData.stock = parseInt(json.qty)
    $scope.detailData.barcode=json.barcode
    $scope.detailData.long=json.long
    $scope.detailData.wide=json.wide
    $scope.detailData.high=json.height
    $scope.detailData.weight=json.weight
  }

  $scope.chooseSuratJalan = function() {
    var warehouse_receipt_id = $scope.detailData.warehouse_receipt_id;
    $scope.detailData = {};
    $scope.detailData.warehouse_receipt_id = warehouse_receipt_id;
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

  $scope.deleteCarrierOutbound = function(dom) {
    var row = outbound_datatable.row( $(dom).parents('tr')[0] ).remove().draw();
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
    $scope.disVehicleBtn = false;
  }

  inbound_datatable = $('#inbound_datatable').DataTable({
    scrollX:false,
    'drawCallback' : function() {
      setTimeout(function(){
        console.log('validation');
        $scope.validateForm();
      }, 300)
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

  outbound_datatable = $('#outbound_datatable').DataTable({
    scrollX:false,
    'drawCallback' : function() {
      setTimeout(function(){
        console.log('validation');
        $scope.validateForm();
      }, 300)
    },
    columns : [


    { 'data' : 'no_carrier' },
    { 'data' : 'no_container' },

    { 'data' : 'no_seal' },
    { 'data' : 'driver_name' },
    {
      'className' : 'text-center',
      'data' : function(resp) {
        var outp = '<a class="edit_carrier_btn" ng-click="editCarrierOutbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
        outp += '<a class="delete_carrier_btn" ng-click="deleteCarrierOutbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

        return outp;

      }
    }
    ],
    rowCallback: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });



  var wodTable = $('#wo_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[1,'desc']],
    scrollX : false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_detail_datatable',
      data: function(d) {
        d.customer_id=$scope.formData.customer_id;
        d.is_done=0;
        d.filter_qty=1;
        d.company_id=compId;
        d.service_type_id=12;
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

$http.get(baseUrl+'/operational/job_order/create').then(function(data) {
  $scope.data=data.data;
  console.log($scope.data.staff_gudang)
});

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
  html+="<td><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
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
  console.log($scope.formData.detail);
  var warehouse_receipt_code = $scope.data.warehouse_receipt.find(x => x.id == $scope.detailData.warehouse_receipt_id).code
  var item_warehouse = $rootScope.findJsonId($scope.detailData.item_id,$scope.formData.detail);
  if(item_warehouse.id != undefined) {
      var tr = $('tr#rows-' + item_warehouse.urut);
      console.log("item_warehouse.qty : " + item_warehouse.qty);
      console.log("$scope.detailData.total_item : " + $scope.detailData.total_item);
      var new_qty = parseInt(item_warehouse.total_item) + parseInt($scope.detailData.total_item);
      var new_volume = parseInt(item_warehouse.total_volume) + parseInt($scope.detailData.total_volume);
      var new_tonase = parseInt(item_warehouse.total_tonase) + parseInt($scope.detailData.total_tonase);

      html = '';
      html+="<td>"+warehouse_receipt_code+"</td>";
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
      html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";
      html+="<td class='text-right'>"+($scope.detailData.total_item?$scope.detailData.total_item:0)+" "+" Item</td>";
      html+="<td class='text-right'>"+($scope.detailData.total_volume?$scope.detailData.total_volume:0)+"</td>";
      html+="<td class='text-right'>"+($scope.detailData.total_tonase?$scope.detailData.total_tonase:0)+"</td>";
      html+="<td>"+$rootScope.findJsonId($scope.detailData.imposition,$scope.imposition).name+"</td>";
      html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
      html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
      html+="</tr>";

      $scope.formData.detail.push(
      {
        urut:$scope.urut,
        id:$scope.detailData.item_id,
        warehouse_receipt_detail_id:$scope.detailData.warehouse_receipt_detail_id,
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
  $scope.hitungAppend();
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
  outbound_datatable.rows().clear().draw();
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
  var inbound_carrier = inbound_datatable.rows().data().toArray();
  var outbound_carrier = outbound_datatable.rows().data().toArray();
  $scope.formData.detail_carrier =  inbound_carrier.concat(outbound_carrier);
  $http.post(baseUrl+'/operational_warehouse/handling',$scope.formData).then(function(data) {
    $state.go('operational_warehouse.handling');
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
app.controller('opWarehouseHandlingShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Handling";

  if ($state.current.name=="operational_warehouse.handling.show") {
    $state.go('operational_warehouse.handling.show.detail',{},{location:'replace'});
  }

});
app.controller('opWarehouseHandlingEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Handling";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={}
  $scope.item={}
  $scope.data={}
  $scope.formData.detail=[]
  $scope.detailData={}


  $http.get(baseUrl+'/operational_warehouse/handling/'+$stateParams.id+'/edit').then(function(data) {
    $scope.item=data.data.item;
    $scope.data.staff_gudang = data.data.staff_gudang;
    $scope.data.warehouse = data.data.warehouse;
    var description = $scope.item.description
    $scope.formData = Object.assign($scope.item, data.data.handling);
    $scope.formData.description = description;
    console.log('ket : ' + description);
    var shipment_date = $scope.formData.shipment_date;
    shipment_date = shipment_date.replace(/(\d+)-(\d+)-(\d+).+/, '$3-$2-$1');
    $scope.formData.shipment_date = shipment_date;
    $('.ibox-content').removeClass('sk-loading');
  });

// outbound_datatable = $('#outbound_datatable').DataTable({
//   data : $scope.data.handling_outbound_vehicle,
//   columns : [
//
//
//     { 'data' : 'no_carrier' },
// { 'data' : 'no_container' },
//
//     { 'data' : 'no_seal' },
//     { 'data' : 'driver_name' },
//     {
//       'data' : function(resp) {
//         var outp = '<a class="edit_carrier_btn" ng-click="editCarrierOutbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
//         outp += '<a class="delete_carrier_btn" ng-click="deleteCarrierOutbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

//         return outp;

//       }
//     }
//   ],
//   rowCallback: function(row, data, dataIndex) {
//     $compile(angular.element(row).contents())($scope);
//   }
// });





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
  $http.put(baseUrl+'/operational_warehouse/handling/'+$stateParams.id,$scope.formData).then(function(data) {
    $state.go('operational_warehouse.handling');
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

app.controller('opWarehouseHandlingShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Detail Handling";
  $scope.detailData = {};
  $scope.item = {};
  $scope.data = {}
  $scope.itemData = {}
  $scope.disBtn = true
  $('.sk-container').addClass('sk-loading');
  $scope.state=$state;
  $(".clockpick").clockpicker({
    placement:'right',
    autoclose:true,
    donetext:'DONE',
  });

  $scope.transaction_id = $stateParams.id;

  
    $('#modalItemWarehouse').on('hidden.bs.modal', function(){
        setTimeout(function(){
            $('#modalItem').modal('show')
        }, 200)
    })

  $scope.adjustSizeTotal = function() {
      $scope.itemData.total_tonase = ($scope.itemData.weight || 0) * ($scope.itemData.total_item || 0)
      $scope.itemData.total_volume = ($scope.itemData.long || 0) * ($scope.itemData.high || 0) * ($scope.itemData.wide || 0) * ($scope.itemData.total_item || 0) / 1000000
  }

  $scope.validateStock = function() {
      if(!$scope.itemData.total_item || parseInt($scope.itemData.stock) < parseInt($scope.itemData.total_item)) {
          $scope.disBtn = true;
      } else {
          $scope.disBtn = false;
      }
  }


  $scope.adjustSizeOutput = function() {
      $scope.formOutput.weight = ($scope.itemData.weight || 0) * ($scope.formOutput.qty || 0)
      $scope.formOutput.volume = ($scope.itemData.long || 0) * ($scope.itemData.high || 0) * ($scope.itemData.wide || 0) * ($scope.formOutput.qty || 0) / 1000000
  }

  $scope.getSuratJalan = function() {
    // show detail surat jalan
    var request = {
      warehouse_id : $scope.item.warehouse_id,
      customer_id : $scope.item.customer.id,
    };
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/surat_jalan?' + request).then(function(data) {
      $scope.data.warehouse_receipt=data.data.warehouse_receipt;
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

  editedRow = null;

  $http.get(baseUrl+'/operational_warehouse/handling/create').then(function(data) {
    $scope.data_carrier = data.data;
    $scope.warehouse = data.data.warehouse;
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
    $scope.itemData.imposition=parseInt(json.imposition)
    $scope.itemData.item_name=json.name+' ('+json.code+')'
    $scope.itemData.item_code=json.code
    $scope.itemData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
    $scope.itemData.stock=parseInt(json.qty)
    $scope.itemData.barcode=json.barcode
    $scope.itemData.long=json.long
    $scope.itemData.wide=json.wide
    $scope.itemData.high=json.height
    $('#modalItemWarehouse').modal('hide')
    $scope.validateStock()
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
  $scope.itemData.warehouse_receipt_id=val.warehouse_receipt_id;
  $scope.itemData.item_id=val.item_id;
  $scope.itemData.item_name=val.item_name;
  $scope.itemData.barcode=val.barcode;
  $scope.itemData.long=val.long;
  $scope.itemData.wide=val.wide;
  $scope.itemData.high=val.high;
  $scope.itemData.stock=val.stock;
  $scope.itemData.total_item=val.qty;
  $scope.itemData.imposition=val.imposition;
  $scope.itemData.piece_id=val.piece_id;
  $scope.itemData.total_tonase=val.weight;
  $scope.itemData.total_volume=val.volume;
  $scope.itemData.description=val.description;
  $scope.itemData.is_edit=1;

  var request = {
    warehouse_id : $scope.item.warehouse.id,
    warehouse_receipt_id : val.warehouse_receipt_id,
    item_id : val.item_id,
    is_handling_area : 1
  };


  request = $.param(request);
  $http.get(baseUrl+'/inventory/item/cek_stok_warehouse?' + request).then(function(data) {
    $scope.itemData.stock=data.data.stok;
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
    $http.delete(baseUrl+'/operational_warehouse/handling/delete_vehicle_detail/' + data.id,{_token:csrfToken}).then(function success(data) {
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


$scope.deleteCarrierOutbound = function(dom) {
  var is_delete = confirm('Apakah anda ingin menghapus detail kendaraan ini ?');
  if(is_delete == true) {

    var row =  $(dom).parents('tr')[0];
    var data = outbound_datatable.row(row).data();
    var param = $.param(data);
    $http.post(baseUrl+'/operational_warehouse/handling/delete_vehicle_detail/?'+param,{_token:csrfToken}).then(function success(data) {
      // $state.reload();
      outbound_datatable.row(row).remove().draw();
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
    $http.post(baseUrl+'/operational_warehouse/handling/store_vehicle_detail/'+$stateParams.id,$scope.detailPengangkut).then(function(data) {
    // $state.go('operational.job_order');
    $('#modalPengangkut').modal('hide');
    $scope.disVehicleBtn = false;
    if( $scope.detailPengangkut.type == 'inbound' ) {
      inbound_datatable.row.add($scope.detailPengangkut).draw();
    }
    else {
      outbound_datatable.row.add($scope.detailPengangkut).draw();
    }

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
        $http.put(baseUrl+'/operational_warehouse/handling/update_vehicle_detail/',$scope.detailPengangkut).then(function(data) {
    // $state.go('operational.job_order');
    $('#modalPengangkut').modal('hide');
    $scope.disVehicleBtn = false;
    if( $scope.detailPengangkut.type == 'inbound' ) {
      inbound_datatable.row(editedRow).data($scope.detailPengangkut).draw();
    }
    else {
       outbound_datatable.row(editedRow).data($scope.detailPengangkut).draw();
    }

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
$http.get(baseUrl+'/operational_warehouse/handling/'+$stateParams.id).then(function(data) {
  // show detail fetch handling
  var item=data.data.item;
  var handling=data.data.handling;
  var description = item.description;
  $scope.item = Object.assign(item, handling);
  $scope.item.description = description;
  $scope.rack_id=data.data.rack_id;
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
    scrollX:false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
      data: function(d) {
        d.warehouse_id = $scope.item.warehouse_id
        d.customer_id = $scope.item.customer.id
        d.no_surat_jalan = $scope.itemData.no_surat_jalan
        d.is_handling_area = 1
      },
      dataSrc : function(json) {
        var item_id = $scope.detail.map(value => value.item_id);
        json.data = json.data.filter(value => item_id.every(unit => unit != value.id) );
        console.log({json, item_id});

        return json.data;
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
      {data:"imposition_name",orderable:false,searhable:false},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $('#modalItemWarehouse').on('hidden.bs.modal', function(){
    $('#modalItem').modal();
  })


  $scope.verifyStock = function(items) {
    var item, good, stock, item_out;
    var tr;
    for(x in items) {
      item = items[x];
      good = $rootScope.findJsonId(item.id, $scope.detail, 'item_id');
      stock = parseInt(item.stock);
      console.log('Stok : ' + stock);
      item_out =  parseInt(good.qty)
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
    "initComplete": function( settings, json ) {
      var units = this.api().data().toArray();
      $scope.verifyStock(units);
    },
    processing: true,
    serverSide: true,
    scrollX: false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/validasi_item_datatable/' + $stateParams.id,
      data: function(d) {
        var unit;
        var exclude = [];
        var item_include = [];
        var no_surat_jalan_include = [];
        for(x in $scope.detail) {
          unit = $scope.detail[x];
          exclude.push(unit.rack_id);
          item_include.push(unit.item_id);
          no_surat_jalan_include.push(unit.no_surat_jalan);
        }

        d.item_include = item_include
        d.no_surat_jalan_include = no_surat_jalan_include
        d.exclude = exclude
        d.warehouse_id = $scope.item.warehouse_id
        d.customer_id = $scope.item.customer.id
        return d;
      }
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
    }
  });
  inbound_datatable = $('#inbound_datatable').DataTable({
    scrollX:false,
    data : data.data.handling_inbound_vehicle,
    columns : [


    { 'data' : 'no_carrier' },
{ 'data' : 'no_container' },

    { 'data' : 'no_seal' },
    { 'data' : 'driver_name' },
    {
      'className' : 'text-center',
      'data' : function(resp) {
        var outp = '<a ng-show="roleList.includes(\'operational_warehouse.handling.detail.edit_vehicle\')" class="edit_carrier_btn" ng-click="editCarrierInbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
        outp += '<a ng-show="roleList.includes(\'operational_warehouse.handling.detail.delete_vehicle\')" class="delete_carrier_btn" ng-click="deleteCarrierInbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

        return outp;
      }
    }
    ],
    rowCallback: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });


  outbound_datatable = $('#outbound_datatable').DataTable({
    scrollX:false,
    data : data.data.handling_outbound_vehicle,
    columns : [


    { 'data' : 'no_carrier' },
    { 'data' : 'no_container' },
    { 'data' : 'no_seal' },
    { 'data' : 'driver_name' },
    {
      'className' : 'text-center',
      'data' : function(resp) {
        var outp = '<a ng-show="roleList.includes(\'operational_warehouse.handling.detail.edit_vehicle\')" class="edit_carrier_btn" ng-click="editCarrierOutbound($event.currentTarget)"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
        outp += '<a ng-show="roleList.includes(\'operational_warehouse.handling.detail.delete_vehicle\')" class="delete_carrier_btn" ng-click="deleteCarrierOutbound($event.currentTarget)"><i class="fa fa-trash"></i></a>';

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
  $('.sk-container').removeClass('sk-loading');
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
  $scope.itemData.item_name="";
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
  $scope.itemData.rack_id = $scope.rack_id
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
app.controller('opWarehouseHandlingShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Handling";
  $('.sk-container').addClass('sk-loading');

  $scope.urls=baseUrl;
  $http.get(baseUrl+'/operational/job_order/show_document/'+$stateParams.id).then(function(data) {
    $scope.detail=data.data.detail;
    $('.sk-container').removeClass('sk-loading');
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
app.controller('opWarehouseHandlingShowProses', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Handling";
  $('.sk-container').addClass('sk-loading');
  $scope.urls=baseUrl;

  $http.get(baseUrl+'/operational/job_order/show_status/'+$stateParams.id).then(function(data) {
    $scope.detail=data.data.detail;
    $scope.data=data.data;
    $('.sk-container').removeClass('sk-loading');
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
