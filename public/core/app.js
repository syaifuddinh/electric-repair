var dependencies = [
    'solog',
    'units',
    'containerTypes',
    'items',
    'contacts',
    'receiptTypes',
    'ui.router',
    'ngAnimate',
    'summernote',
    'localytics.directives',
    'uiBreadcrumbs',
    'ngSanitize',
    'ngResource',
    'infinite-scroll',
    'ngtimeago',
    'angucomplete'
]
if(getModule('typeTransactions')) {
    dependencies.push('typeTransactions')
}
if(getModule('additionalFields')) {
    dependencies.push('additionalFields')
}
if(getModule('routes')) {
    dependencies.push('routes')
}
if(getModule('vehicleTypes')) {
    dependencies.push('vehicleTypes')
}
if(getModule('warehouses')) {
    dependencies.push('warehouses')
}

if(getModule('itemCategories')) {
    dependencies.push('itemCategories')
}

if(getModule('racks')) {
    dependencies.push('racks')
}

if(getModule('salesOrders')) {
    dependencies.push('salesOrders')
}

if(getModule('operationalClaimCategories')) {
    dependencies.push('operationalClaimCategories')
}

if(getModule('operationalClaims')) {
    dependencies.push('operationalClaims')
}

if(getModule('customerOrders')) {
    dependencies.push('customerOrders')
}

if(getModule('salesOrderReturns')) {
    dependencies.push('salesOrderReturns')
}

if(getModule('shipmentSales')) {
    dependencies.push('shipmentSales')
}

if(getModule('invoiceSales')) {
    dependencies.push('invoiceSales')
}

if(getModule('contracts')) {
    dependencies.push('contracts')
}

if(getModule('vendorPrices')) {
    dependencies.push('vendorPrices')
}

if(getModule('jobOrders')) {
    dependencies.push('jobOrders')
}

if(getModule('voyageSchedules')) {
    dependencies.push('voyageSchedules')
}

if(getModule('manifests')) {
    dependencies.push('manifests')
}

if(getModule('deliveryOrders')) {
    dependencies.push('deliveryOrders')
}

if(getModule('invoices')) {
    dependencies.push('invoices')
}

if(getModule('warehouseReceipts')) {
    dependencies.push('warehouseReceipts')
}

if(getModule('purchaseOrders')) {
    dependencies.push('purchaseOrders')
}

if(getModule('purchaseRequests')) {
    dependencies.push('purchaseRequests')
}

if(getModule('pickingOrders')) {
    dependencies.push('pickingOrders')
}

if(getModule('purchaseOrderReturs')) {
    dependencies.push('purchaseOrderReturs')
}

if(getModule('salesOrderReturns')) {
    dependencies.push('salesOrderReturns')
}

if(getModule('itemDeletions')) {
    dependencies.push('itemDeletions')
}

if(getModule('itemUsages')) {
    dependencies.push('itemUsages')
}

if(getModule('itemMigrations')) {
    dependencies.push('itemMigrations')
}

if(getModule('stokOpname')) {
    dependencies.push('stokOpname')
}

if(getModule('costRouteTypes')) {
    dependencies.push('costRouteTypes')
}

if(getModule('costTypes')) {
    dependencies.push('costTypes')
}

if(getModule('containers')) {
    dependencies.push('containers')
}

if(getModule('itemConditions')) {
    dependencies.push('itemConditions')
}

// Depo management module
if(getModule('containerInspections')) {
    dependencies.push('containerInspections')
}
if(getModule('gateInContainers')) {
    dependencies.push('gateInContainers')
}
if(getModule('movementContainers')) {
    dependencies.push('movementContainers')
}
// End of depo managemen module

if(getModule('packagings')) {
    dependencies.push('packagings')
}
if(getModule('email')) {
    dependencies.push('email')
}

if(getModule('vehicles')) {
    dependencies.push('vehicles')
}
if(getModule('warehouseMaps')) {
    dependencies.push('warehouseMaps')
}

if(getModule('warehouseStocks')) {
    dependencies.push('warehouseStocks')
}

if(getModule('purchaseRequestSales')) {
    dependencies.push('purchaseRequestSales')
}

if(getModule('purchaseOrderSales')) {
    dependencies.push('purchaseOrderSales')
}

if(getModule('purchaseOrderReturnSales')) {
    dependencies.push('purchaseOrderReturnSales')
}

if(getModule('stocklistSales')) {
    dependencies.push('stocklistSales')
}

if(getModule('stockByWarehouseSales')) {
    dependencies.push('stockByWarehouseSales')
}

if(getModule('stockByItemSales')) {
    dependencies.push('stockByItemSales')
}

if(getModule('receiptSales')) {
    dependencies.push('receiptSales')
}

if(getModule('salesOrderReturnSales')) {
    dependencies.push('salesOrderReturnSales')
}

var app = angular.module('myApp', dependencies, function($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');
});
app.run(function(solog, $rootScope,$http,$transitions,$state,$stateParams, $timeout, $filter, $compile) {
  $http.defaults.headers.common['Authorization']='Bearer '+authUser.api_token
  item_warehouse_datatable = null
  $rootScope.pageTitle = '';
  $rootScope.currentPage = '';
  $rootScope.solog = solog

  $rootScope.job_order = {
      'statusData' : {},
      'detail' : []
  }
  $rootScope.itemData={};
  $rootScope.job_order.appendBtn = true
  $rootScope.exit = 1


  $rootScope.showSetting = function(slug, fn = null, ...args) {
        if(fn == null) {
            fn = function(data){}
        }
        $http.get(baseUrl+'/setting/setting/' + slug).then(function(data){
            if(args.length == 0) {
                fn(data.data)
            } else {
                for(a in args) {
                    fn(data.data, args[a])
                }
            }
        }, function() {
        })
  }

  $rootScope.flagSettings = ['picking', 'job_order', 'work_order', 'general', 'good_receipt', 'shipment']
  $rootScope.fetchSettings = function() {
      var settings = localStorage.getItem('settings')
      var slugs = $rootScope.flagSettings
      var resp
      if(!settings) {
          resp = {}
          for(s in slugs) {
            resp[slugs[s]] = {}
          }

          return resp
      } else {
          resp = JSON.parse(settings)
          for(s in slugs) {
             if(!resp[slugs[s]]) {
                 resp[slugs[s]] = {}
             }
          }
          return resp
      }
  }
  $rootScope.settings = $rootScope.fetchSettings()

  $rootScope.storeToLocalStorage = function(name, params) {
      localStorage.setItem(name, JSON.stringify(params))
      $rootScope.settings = $rootScope.fetchSettings()
  }

  $rootScope.storeSettings = function() {
      var settings = $rootScope.fetchSettings()
      var slugs = $rootScope.flagSettings
      var slug
      for(s in slugs) {
          slug = slugs[s]
          $rootScope.showSetting(slug, function(resp, slug){
             for(x in resp) {
                for(y in resp[x].content.settings) {
                    // alert(resp[x].content.settings[y].slug + ' - ' + slug)
                    settings[slug][resp[x].content.settings[y].slug] = resp[x].content.settings[y].value
                }
             }
             $rootScope.storeToLocalStorage('settings', settings)
          }, slug)
      }
  }
  $rootScope.storeSettings()




  $rootScope.job_order.showKpiLog = function() {
      $http.get(baseUrl+'/operational/job_order/show_status/'+$rootScope.job_order_id).then(function(data) {
        $rootScope.job_order.kpiLogDetail=data.data.detail;
        $('.sk-container').removeClass('sk-loading');
    });
  }

  $rootScope.job_order.editKpiStatus = function(formData) {
    $rootScope.job_order.statusData={}
    $rootScope.job_order.statusData.id=formData.id
    $rootScope.job_order.statusData.name=formData.kpi_status.name
    $rootScope.job_order.statusData.kpi_status_id=formData.kpi_status_id
    $rootScope.job_order.statusData.update_date=$filter('minDate')(formData.date_update)
    $rootScope.job_order.statusData.update_time=$filter('aTime')(formData.date_update)
    $rootScope.job_order.statusData.description=formData.description
    $('#modalUpdateStatus').modal('show');
}
    $rootScope.job_order.statusOnChange = function() {
        var kpi_status = $rootScope.job_order.kpi_status.find(x => x.id == $rootScope.job_order.statusData.kpi_status_id)
        if(kpi_status) {
            $rootScope.job_order.statusIsDone = kpi_status.is_done
        }
    }

$rootScope.job_order.showKpiStatus = function() {
  $http.get(baseUrl+'/operational/job_order/' + $rootScope.job_order_id + '/kpi_status').then(function(data) {
    $rootScope.job_order.kpi_status=data.data;
}, function(){
  $rootScope.job_order.showKpiStatus()
});
}

$rootScope.job_order.updateStatus=function() {
    $rootScope.disBtn=true;
    $http.put(baseUrl+'/operational/job_order/update_status',$rootScope.job_order.statusData).then(function(data) {
      $('#modalUpdateStatus').modal('hide');
      toastr.success("Data Berhasil Disimpan!");
      $rootScope.disBtn=false;
      $timeout(function() {
        $state.reload();
    },1000)
  }, function(error) {
      $rootScope.disBtn=false;
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

$rootScope.job_order.deleteKpiStatus = function(ids) {
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

$rootScope.job_order.showKpiStatusData = function() {
  $http.get(baseUrl+'/operational/job_order/' + $rootScope.job_order_id + '/kpi_status/data').then(function(data) {
    $rootScope.job_order.kpi_status_data=data.data;
}, function(){
  $rootScope.job_order.showKpiStatusData()
});
}

$rootScope.job_order.addStatus=function() {
    var status = $rootScope.job_order.kpi_status_data.id
    $rootScope.job_order.statusData={};
    $rootScope.job_order.statusData.decrease=1;
    $rootScope.job_order.statusData.kpi_status_id=status;
    $rootScope.job_order.statusData.update_date=dateNow;
    $rootScope.job_order.statusData.update_time=timeNow;
    $('#modalStatus').modal('show');
}

$rootScope.job_order.showDetail = function(job_order_id) {
  if(job_order_id) {
      $rootScope.job_order_id = job_order_id
  }
  $rootScope.job_order.showKpiStatus()
  $rootScope.job_order.showKpiStatusData()
  $http.get(baseUrl+'/operational/job_order/'+ $rootScope.job_order_id  + '/detail').then(function(data) {
    $rootScope.job_order.detail = data.data
});
}

$rootScope.job_order.submitStatus=function() {
    $rootScope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_status/'+$rootScope.job_order_id,$rootScope.job_order.statusData).then(function(data) {
      $('#modalStatus').modal('hide');
      $timeout(function() {
        $state.reload();
    },1000)
      toastr.success("Status berhasil diupdate!");
      $rootScope.disBtn=false;
  }, function(error) {
      $rootScope.disBtn=false;
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

$rootScope.job_order.autoSubmitStatus=function() {
    $rootScope.disBtn=true;
    $http.put(baseUrl+'/operational/job_order/add_status/' + $rootScope.job_order_id + '/auto').then(function(data) {
      $('#modalStatus').modal('hide');
      $timeout(function() {
        $state.reload();
    },1000)
      toastr.success("Status berhasil diupdate!");
      $rootScope.disBtn=false;
  }, function(error) {
      $rootScope.disBtn=false;
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

$rootScope.job_order.showItemWarehouseDatatable = function() {
    if(!item_warehouse_datatable) {
        item_warehouse_datatable = $('#item_warehouse_datatable').DataTable({
            processing: true,
            serverSide: true,
            scrollX:false,
            ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
                  data: function(d) {
                    d.warehouse_id = $rootScope.itemData.warehouse_id
                    d.customer_id = $rootScope.customer_id
                    d.warehouse_receipt_id = $rootScope.itemData.warehouse_receipt_id
                    d.rack_id = $rootScope.itemData.rack_id
                    return d;
                }
            },
            columns:[
                {
                  data:"action_choose",
                  className:"text-center",
                  orderable:false,
                  searchable:false
                },
                {data:"warehouse_receipt_code",name:"warehouse_receipts.code"},
                {data:"warehouse_name",name:"warehouses.name"},
                {data:"rack_code",name:"racks.code"},
                {data:"name",name:"name"},
                {data:"barcode",name:"barcode", className : 'hidden'},
                {data:"imposition_name",orderable:false,searchable:false},
            ],
          createdRow: function(row, data, dataIndex) {
              $compile(angular.element(row).contents())($rootScope);
          }
        });

        $('#modalItemWarehouse').on('hidden.bs.modal', function(){
        setTimeout(function(){
            $('#modalItem').modal('show')
        }, 500)
  })
    }
}

$rootScope.job_order.choosePallet=function(json) {
    $('#modalItemWarehouse').modal('hide')
    setTimeout(function(){
        $('#modalItem').modal()
    }, 500)
    $rootScope.itemData.item_id=json.id
    $rootScope.itemData.warehouse_receipt_id=parseInt(json.warehouse_receipt_id)
    $rootScope.itemData.imposition=parseInt(json.imposition)
    $rootScope.itemData.item_name=json.name+' ('+json.code+')'
    $rootScope.itemData.item_code=json.code
    $rootScope.itemData.rack_code=json.rack_code
    $rootScope.itemData.warehouse_name=json.warehouse_name
    $rootScope.itemData.warehouse_receipt_id=json.warehouse_receipt_id
    $rootScope.itemData.rack_id=json.rack_id
    $rootScope.itemData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
    $rootScope.itemData.item_warehouse = json;
    $rootScope.itemData.stock = parseInt(json.qty)
    $rootScope.itemData.long=json.long
    $rootScope.itemData.wide=json.wide
    $rootScope.itemData.high=json.height
    $rootScope.itemData.volume = parseInt(json.long) * parseInt(json.wide) * parseInt(json.height) / 1000000;
    $rootScope.itemData.weight=json.weight
    $rootScope.job_order.allowAppend()
    $rootScope.job_order.adjustSizeTotal()
    $rootScope.job_order.adjustStock()
}

$rootScope.$on('getItemWarehouse', function(e, v) {
    if($rootScope.job_order_id) {
        $rootScope.job_order.choosePallet(v)
    }
})

$rootScope.job_order.addItem = function() {
    $rootScope.job_order.itemTitle = 'Add Item'
    $rootScope.itemData={};
    $rootScope.itemData.is_warehouse = 0
    $rootScope.itemData.reff_no="-";
    $rootScope.itemData.manifest_no="-";
    $rootScope.itemData.item_name="GENERAL CARGO";
    $rootScope.item_name="";
    $rootScope.itemData.total_item = $rootScope.currentImposition ? $rootScope.currentImposition : 1;
    $rootScope.itemData.imposition=1;
    $rootScope.itemData.weight_type=1;
    $rootScope.itemData.total_tonase=0;
    $rootScope.itemData.total_volume=0;
    $rootScope.itemData.piece_id=$rootScope.settings.work_order.default_piece_id;
    $rootScope.itemData.description="-";
    $rootScope.job_order.allowAppend()
    $('#modalItem').modal('show');
    $rootScope.job_order.showItemWarehouseDatatable()
}


$rootScope.job_order.submitItem=function(exit = 1) {
    $rootScope.disBtn=true;
    $http.post(baseUrl+'/operational/job_order/add_item/'+$rootScope.job_order_id,$rootScope.itemData).then(function(data) {
      $rootScope.job_order.showDetail($rootScope.job_order_id)
      $rootScope.$broadcast('showJobOrder', 1)
      if(exit == 1) {
          $('#modalItem').modal('hide');
          var hash = location.hash
          $rootScope.job_order.showDetail()
      } else {
          $rootScope.job_order.addItem()
      }
      toastr.success("Item Barang berhasil disimpan!");
      $rootScope.disBtn=false;
  }, function(error) {
      $rootScope.disBtn=false;
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

$rootScope.job_order.editItem=function(val) {
    $rootScope.itemData={}
    $rootScope.job_order.itemTitle = 'Edit Item'
    $rootScope.itemData.stock=val.stock;
    $rootScope.itemData.detail_id=val.id;
    $rootScope.itemData.reff_no=val.no_reff;
    $rootScope.itemData.manifest_no=val.no_manifest;
    $rootScope.itemData.item_name=val.item_name;
    $rootScope.item_name=val.item_name;
    $rootScope.itemData.warehouse_name=val.warehouse_name;
    $rootScope.itemData.rack_code=val.rack_code;
    $rootScope.itemData.total_item=val.qty;
    $rootScope.itemData.imposition=val.imposition;
    $rootScope.itemData.piece_id=val.piece_id;
    $rootScope.itemData.weight_type=val.weight_type ?? 1;
    $rootScope.itemData.total_tonase=val.weight;
    $rootScope.itemData.total_volume=val.volume;
    $rootScope.itemData.description=val.description;
    $rootScope.itemData.load_date=$filter('minDate')(val.load_date);
    $rootScope.itemData.is_edit=1;

    $rootScope.itemData.long=val.long
    $rootScope.itemData.wide=val.wide
    $rootScope.itemData.high=val.high
    $rootScope.itemData.volume = parseInt(val.long) * parseInt(val.wide) * parseInt(val.high) / 1000000;

    $rootScope.itemData.customer_id=$rootScope.customer_id;
    $rootScope.job_order.adjustSizeTotal()
    if(val.warehouse_receipt_detail_id != null) {
        $rootScope.itemData.item_id=val.item_id;
        $rootScope.itemData.rack_id=val.rack_id;
        $rootScope.itemData.warehouse_id=val.warehouse_id;
        $rootScope.itemData.warehouse_receipt_detail_id=val.warehouse_receipt_detail_id;
        $rootScope.itemData.warehouse_receipt_id=val.warehouse_receipt_id;
        $rootScope.itemData.is_warehouse  = 1
    } else {
        $rootScope.itemData.is_warehouse  = 0
    }
    $rootScope.job_order.allowAppend()
    $("#modalItem").on('shown.bs.modal', function(){
        $rootScope.itemData.total_tonase = val.weight
        $rootScope.itemData.total_volume = val.volume
        $compile($('[ng-model="itemData.total_tonase"]'))($rootScope)
        $compile($('[ng-model="itemData.total_volume"]'))($rootScope)
    })
    $("#modalItem").modal();
    $rootScope.job_order.showItemWarehouseDatatable()
}

$rootScope.job_order.adjustStock = function() {
    var item_id = $rootScope.itemData.item_id
    var id = $rootScope.itemData.detail_id
    var unit
    
    $rootScope.itemData.stock_existing = parseInt($rootScope.itemData.stock)
}

$rootScope.job_order.adjustSizeTotal = function() {
  $rootScope.itemData.total_volume = ($rootScope.itemData.long || 0) * ($rootScope.itemData.high || 0) * ($rootScope.itemData.wide || 0) * ($rootScope.itemData.total_item || 0) / 1000000
  $rootScope.itemData.volumetric_weight = ($rootScope.itemData.long || 0) * ($rootScope.itemData.high || 0) * ($rootScope.itemData.wide || 0) * ($rootScope.itemData.total_item || 0) / 6000
  if($rootScope.itemData.weight_type == 1) {
    $rootScope.itemData.total_tonase = ($rootScope.itemData.weight || 0) * ($rootScope.itemData.total_item || 0)
  }
  else {
    $rootScope.itemData.total_tonase = $rootScope.itemData.total_volume / 6000;
  }
  $rootScope.job_order.allowAppend()
}

$rootScope.job_order.weightTypeChange = function() {
  if($rootScope.itemData.weight_type == 1) {
    $rootScope.itemData.total_tonase = ($rootScope.itemData.weight || 0) * ($rootScope.itemData.total_item || 0)
  }
  else {
    $rootScope.itemData.total_tonase = $rootScope.itemData.total_volume / 6000;
  }
}

$rootScope.job_order.totalVolumeChange = function() {
  if($rootScope.itemData.weight_type == 2) {
    $rootScope.itemData.total_tonase = $rootScope.itemData.total_volume / 6000;
  }
}

$rootScope.job_order.allowAppend = function() {
    if($rootScope.itemData.is_warehouse == 1) {
        if(parseInt($rootScope.itemData.total_item) > parseInt($rootScope.itemData.stock) || ($rootScope.itemData.is_warehouse == 1 && !$rootScope.itemData.item_id)) {
          $rootScope.job_order.appendBtn = true
      } else {
        $rootScope.job_order.appendBtn = false
    }
} else {
    $rootScope.job_order.appendBtn = false
}
}

$rootScope.job_order.deleteItem=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
  }
  $http.delete(baseUrl+'/operational/job_order/delete_item/'+id).then(function(data) {
      $rootScope.job_order.showDetail($rootScope.job_order_id);
      var hash = location.hash
      $rootScope.$broadcast('showJobOrder', 1)
      toastr.success("Item telah dihapus !");
  })
}

$rootScope.job_order.cariItem=function() {
  item_warehouse_datatable.ajax.reload();
  $('#modalItem').modal('hide')
  setTimeout(function(){
    $('#modalItemWarehouse').modal()
}, 500)
}

$rootScope.backward = function(){
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $rootScope.emptyBuffer()
      history.back();
  }
}

$rootScope.getCurrentPathname = function() {
  var paths = $state.getCurrentPath()
  var path = paths[paths.length - 1]

  return path.state.name
}
$rootScope.buffer = []
$rootScope.insertBuffer = function() {
  var paths = $state.getCurrentPath()
  var path = paths[paths.length - 1]
  var formData = angular.element($('.mains')[0]).scope().formData
  $rootScope.buffer.push({
      'url' : path.state.name,
      'params' : {
          'id' : $stateParams.id,
          'formData' : formData
      }
  })
}
$rootScope.accessBuffer = function() {
  var index = $rootScope.buffer.length - 1
  if(index > -1) {
      var buffer = $rootScope.buffer[index]
      $rootScope.buffer.splice(index, 1)
      $state.go(buffer.url, buffer.params)
      var formData = buffer.params.formData
      $timeout(function(){
          var main = angular.element($('.mains')[0]).scope()
          main.formData = formData
          main.$apply()
      }, 400)
  }
}

$rootScope.emptyBuffer = function() {
  $rootScope.buffer = []
}
$rootScope.hasBuffer = function() {
  var resp = false
  if($rootScope.buffer.length > 0) {
      resp = true
  }

  return resp
}

$rootScope.baseUrl = baseUrl;
$rootScope.summConfig={
    toolbar:[
    ['style',['bold', 'italic', 'underline']],
    ['fontsize', ['fontsize']],
    ['para', ['ul', 'ol']],
    ]
}

$transitions.onSuccess({}, function() {
    $rootScope.states=$state;
    $rootScope.statesParams=$stateParams;
    document.body.scrollTop = document.documentElement.scrollTop = 0; //autoscroll ketika mengganti state
    $rootScope.refreshNotif();
})
$transitions.onStart({}, function() {
    // $rootScope.states=$state;
})
$rootScope.dates=function(data) {
    if (data) {
      return new Date(data)
  } else {
      return new Date()
  }
}
$rootScope.findJsonId=function(value,jsons,key='id') {
    if (!jsons || value==null || value==undefined) {
      return {}
  }
  for (var i = 0; i < jsons.length; i++) {
      if(jsons[i] != undefined) {

        if (jsons[i][key]==value) {
          return jsons[i]
      }
  }
}
return {}
}
$rootScope.in_array=function(id,array) {
    return array.indexOf(id) > -1;
}
$rootScope.chunk = function(array, size) {
    const chunked_arr = [];
    for (let i = 0; i < array.length; i++) {
      const last = chunked_arr[chunked_arr.length - 1];
      if (!last || last.length === size) {
        chunked_arr.push([array[i]]);
      } else {
        last.push(array[i]);
      }
    }
    return chunked_arr;
}
$rootScope.roleList=[]
$http.post(baseUrl+'/setting/user/role_array').then(function(data) {
    $rootScope.roleList=data.data;
})
$rootScope.groupNameProfile=authUser.is_admin?'Administrator':'User Branch '+authUser.company.name;
$('[data-toggle="tooltip"]').tooltip();

$rootScope.insertRoute = function() {
    $rootScope.insertBuffer()
    $state.go('setting.route.create')
}

$rootScope.insertCountry = function() {
    $rootScope.insertBuffer()
    $state.go('setting.general.countries')
}

$rootScope.insertProvince = function() {
    $rootScope.insertBuffer()
    $state.go('setting.province.create')
}

$rootScope.insertItemCategory = function() {
    $rootScope.insertBuffer()
    $state.go('inventory.category.create')
}

$rootScope.insertAccount = function() {
    $rootScope.insertBuffer()
    $state.go('setting.account.create')
}

$rootScope.insertVehicle = function() {
    $rootScope.insertBuffer()
    $state.go('vehicle.vehicle.create')
}

$rootScope.insertVendor = function() {
    $rootScope.insertBuffer()
    $state.go('contact.vendor.create')
}

$rootScope.insertCustomer = function() {
    $rootScope.insertBuffer()
    $state.go('contact.customer.create')
}

$rootScope.insertVehicleVariant = function() {
    $rootScope.insertBuffer()
    $state.go('setting.vehicle_variant.create')
}


    $rootScope.insertService = function() {
        $rootScope.insertBuffer()
        $state.go('setting.general.service')
    }

    $rootScope.list = {}
    $rootScope.showCity = function(fn) {
        $http.get(baseUrl+'/setting/city').then(function(data) {
            $rootScope.list.city = data.data.data
        }, function(){
        });
    }
    $rootScope.showCity()

    $rootScope.showCostType = function(payload = {}) {
        $http.get(baseUrl+'/setting/cost_type', {params : payload}).then(function(data) {
            $rootScope.list.cost_type = data.data
        }, function(){
        });
    }
    $rootScope.showCostType()

    $rootScope.showKpiStatus = function(service_id, fn) {
        $http.get(baseUrl+'/setting/service/' + service_id + '/statuses').then(function(data) {
            $rootScope.list.kpi_statuses = data.data.data
        }, function(){
        });
    }
});

app.service('maintenanceInterceptor', function($q) {
  var service = this;
  service.responseError=function(response) {
    if (response.status==503) {
      alert("Aplikasi sedang maintenance!");
      // window.location='#!/dashboard';
  }
  if (response.status==401) {
      // window.location='/login';
  }
  if (response.status==404) {
      // alert("Aplikasi sedang maintenance!");
      toastr.error("404 : Page Not Found","Oops!")
      // window.location='#!/dashboard';
      // window.location.reload();
  }
  return $q.reject(response);
}
});
app.config(function($httpProvider,$locationProvider) {
  $httpProvider.interceptors.push('maintenanceInterceptor');
  $httpProvider.defaults.cache = false;
  // $locationProvider.html5Mode(true);
});

function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;

    var byteCharacters = atob(b64Data);
    var byteArrays = [];

    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);

        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        var byteArray = new Uint8Array(byteNumbers);

        byteArrays.push(byteArray);
    }

    var blob = new Blob(byteArrays, {type: contentType});
    return blob;
}

app.config(function($stateProvider, $urlRouterProvider) {
  $stateProvider
  // .state('home',{url:'/home',templateUrl:'view/dashboard/home.html'})
  .state('home',{
    url:'/home',
    data:{label:'Dashboard'},
    views:{'':{templateUrl:'view/home.html',controller:'homeIndex'}}
})
  .state('setting',{url:'/setting',data:{label:'Setting'},views:{'':{templateUrl:'view/setting/head.html',controller:'setting'}}})

  .state('setting.area',{url:'/area',data:{label:'Area'},views:{'@':{templateUrl:'view/setting/area/index.html',controller:'settingAreaIndex'}}})
  .state('setting.query_builder',{url:'/query_builder',data:{label:'Query Builder'},views:{'@':{templateUrl:'view/setting/query_builder/index.html',controller:'settingQueryBuilder'}}})
  .state('setting.widget',{url:'/widget',data:{label:'Widget'},views:{'@':{templateUrl:'view/setting/widget/index.html',controller:'settingWidget'}}})
  .state('setting.widget.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/setting/widget/create.html',controller:'settingWidgetCreate'}}})
  .state('setting.widget.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/setting/widget/create.html',controller:'settingWidgetCreate'}}})

  .state('setting.dashboard_builder',{url:'/dashboard_builder',data:{label:'Dashboard Builder'},views:{'@':{templateUrl:'view/setting/dashboard_builder/index.html',controller:'settingDashboard'}}})
  .state('setting.dashboard_builder.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/setting/dashboard_builder/create.html',controller:'settingDashboardCreate'}}})
  .state('setting.dashboard_builder.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/setting/dashboard_builder/create.html',controller:'settingDashboardCreate'}}})
  .state('setting.dashboard_builder.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/setting/dashboard_builder/show.html',controller:'settingDashboardShow'}}})

  .state('setting.company',{url:'/company',data:{label:'Branch'},views:{'@':{templateUrl:'view/setting/company/index.html',controller:'settingCompanyIndex'}}})
  .state('setting.company.create',{url:'/create',data:{label:'Add Branch'},views:{'@':{templateUrl:'view/setting/company/create.html',controller:'settingCompanyCreate'}}})
  .state('setting.company.edit',{url:'/:id/edit',data:{label:'Edit Branch'},views:{'@':{templateUrl:'view/setting/company/create.html',controller:'settingCompanyEdit'}}})
  .state('setting.company.show',{url:'/:id',data:{label:'Detail Branch'},views:{'@':{templateUrl:'view/setting/company/showHead.html',controller:'settingCompanyShow'}}})
  .state('setting.company.show.dashboard',{url:'/dashboard',data:{label:'Dashboard'},views:{'':{templateUrl:'view/setting/company/dashboard.html',controller:'settingCompanyShowDashboard'}}})
  .state('setting.company.show.info',{url:'/info',data:{label:'Info & Setting'},views:{'':{templateUrl:'view/setting/company/info.html',controller:'settingCompanyShowInfo'}}})
  .state('setting.company.show.info.detail',{url:'/detail',data:{label:'Detail'},views:{'':{templateUrl:'view/setting/company/detailInfo.html',controller:'settingCompanyShowInfoDetail'}}})
  .state('setting.company.show.info.numbering',{url:'/penomoran_transaksi',data:{label:'Format Penomoran'},views:{'':{templateUrl:'view/setting/company/detailPenomoran.html',controller:'settingCompanyShowInfoNumbering'}}})
  .state('setting.company.show.info.numbering.detail',{url:'/:idFormat',data:{label:'Detail Penomoran'},views:{'@^.^':{templateUrl:'view/setting/company/detailPenomoranCreate.html',controller:'settingCompanyShowInfoNumberingCreate'}}})
  .state('setting.company.show.info.gudang',{url:'/gudang',data:{label:'Gudang'},views:{'':{templateUrl:'view/setting/company/warehouse.html',controller:'settingCompanyShowInfoGudang'}}})
  .state('setting.company.show.info.gudang.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/setting/company/warehouse/create.html',controller:'settingCompanyShowInfoGudangCreate'}}})
  .state('setting.company.show.info.gudang.edit',{url:'/:warehouse_id/edit',data:{label:'Add'},views:{'@':{templateUrl:'view/setting/company/warehouse/create.html',controller:'settingCompanyShowInfoGudangCreate'}}})

  .state('setting.user',{url:'/user',data:{label:'User Management'},views:{'@':{templateUrl:'view/setting/user/index.html',controller:'settingUserIndex'}}})
  .state('setting.user.group',{url:'/group',data:{label:'Group User'},views:{'@':{templateUrl:'view/setting/user/group.html',controller:'settingUserGroup'}}})
  .state('setting.user.group.previlage',{url:'/previlage/:id',data:{label:'Grup Previlage'},views:{'':{templateUrl:'view/setting/user/grup_previlage.html',controller:'settingUserGroupPrevilage'}}})
  .state('setting.user.create',{url:'/create',data:{label:'Add User'},views:{'@':{templateUrl:'view/setting/user/create.html',controller:'settingUserCreate'}}})
  .state('setting.user.edit',{url:'/:id/edit',data:{label:'Edit User'},views:{'@':{templateUrl:'view/setting/user/create.html',controller:'settingUserEdit'}}})
  .state('setting.user.show',{url:'/:id',data:{label:'Detail User'},views:{'@':{templateUrl:'view/setting/user/showHead.html',controller:'settingUserShow'}}})
  .state('setting.user.show.personal',{url:'/personal',data:{label:'Personal Info'},views:{'':{templateUrl:'view/setting/user/personal.html',controller:'settingUserShowPersonal'}}})
  .state('setting.user.show.password',{url:'/ganti_passsword',data:{label:'Ganti Password'},views:{'':{templateUrl:'view/setting/user/password.html',controller:'settingUserShowPassword'}}})
  .state('setting.user.show.previlage',{url:'/previlage',data:{label:'User Previlage'},views:{'':{templateUrl:'view/setting/user/previlage.html',controller:'settingUserShowPrevilage'}}})
  .state('setting.user.show.notification',{url:'/notifikasi',data:{label:'Setting Notifikasi'},views:{'':{templateUrl:'view/setting/user/notification.html',controller:'settingUserShowNotification'}}})
  .state('setting.account',{url:'/daftar_akun',data:{label:'Daftar Akun'},views:{'@':{templateUrl:'view/setting/account/index.html',controller:'settingAccount'}}})
  .state('setting.account.create',{url:'/create',data:{label:'Add Akun'},views:{'@':{templateUrl:'view/setting/account/create.html',controller:'settingAccountCreate'}}})
  .state('setting.account.edit',{url:'/:id/edit',data:{label:'Edit Akun'},views:{'@':{templateUrl:'view/setting/account/create.html',controller:'settingAccountEdit'}}})

  .state('setting.account_default',{url:'/default_akun',data:{label:'Setting Default Akun'},views:{'@':{templateUrl:'view/setting/account_default/index.html',controller:'settingAccountDefault'}}})

  
  // KONTAK
  .state('contact',{url:'/kontak',data:{label:'Contacts'},views:{'':{templateUrl:'view/contact/head.html',controller:'contact'}}})
  .state('contact.contact',{url:'/kontak',data:{label:'Contacts'},views:{'@':{templateUrl:'view/contact/contact/index.html',controller:'contactContact'}}})
  .state('contact.contact.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/contact/contact/create.html',controller:'contactContactCreate'}}})
  .state('contact.contact.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/contact/contact/create.html',controller:'contactContactEdit'}}})
  .state('contact.contact.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/contact/contact/showHead.html',controller:'contactContactShow'}}})
  .state('contact.contact.show.contract',{url:'/kontrak',data:{label:'Contract'},views:{'':{templateUrl:'view/marketing/contract/index.html',controller:'marketingContract'}}})
  .state('contact.contact.show.receivable',{url:'/piutang',data:{label:'Receivable'},views:{'':{templateUrl:'view/finance/draft_list_piutang/index.html',controller:'DraftListPiutang'}}})
  .state('contact.contact.show.detail',{url:'/detail',data:{label:'Data'},views:{'':{templateUrl:'view/contact/contact/detail.html',controller:'contactContactShowDetail'}}})
  .state('contact.contact.show.document',{url:'/document',data:{label:'Files'},views:{'':{templateUrl:'view/contact/contact/document.html',controller:'contactContactShowDocument'}}})
  .state('contact.contact.show.address',{url:'/alamat',data:{label:'Sender & Receiver'},views:{'':{templateUrl:'view/contact/contact/address.html',controller:'contactContactShowAddress'}}})
  .state('contact.contact.show.address.create',{url:'/create',data:{label:'Add Shipper & Consignee'},views:{'@^.^':{templateUrl:'view/contact/contact/add_address.html',controller:'contactContactShowAddressCreate'}}})
  .state('contact.contact.show.address.create_f',{url:'/create_from_kontak',data:{label:'Add Alamat'},views:{'@^.^':{templateUrl:'view/contact/contact/add_from_contact.html',controller:'contactContactShowAddressCreatef'}}})
  .state('contact.contact.show.address.edit',{url:'/:idaddress/edit',data:{label:'Edit Alamat'},views:{'@^.^':{templateUrl:'view/contact/contact/edit_address.html',controller:'contactContactShowAddressEdit'}}})
  .state('contact.contact.show.address.show',{url:'/:idaddress',data:{label:'Detail Alamat'},views:{'@^.^':{templateUrl:'view/contact/contact/detail_address.html',controller:'contactContactShowAddressShow'}}})
  .state('contact.contact.show.user',{url:'/user',data:{label:'User Aplikasi'},views:{'':{templateUrl:'view/contact/contact/user.html',controller:'contactContactShowUser'}}})
  
  .state('contact.customer',{url:'/customer',data:{label:'Customers'},views:{'@':{templateUrl:'view/contact/customer/index.html',controller:'contactCustomer'}}})
  .state('contact.customer.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/contact/customer/create.html',controller:'contactCustomerCreate'}}})
  .state('contact.customer.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/contact/customer/create.html',controller:'contactCustomerCreate'}}})
  .state('contact.customer.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/contact/customer/detail.html',controller:'contactCustomerShow'}}})

  .state('contact.vendor',{url:'/vendor',data:{label:'Vendor'},views:{'@':{templateUrl:'view/contact/vendor/index.html',controller:'contactVendor'}}})
  .state('contact.vendor.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/contact/vendor/create.html',controller:'contactContactCreate'}}})
  .state('contact.vendor.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/contact/vendor/showHead.html',controller:'contactVendorShow'}}})
  .state('contact.vendor.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/contact/vendor/create.html',controller:'contactVendorEdit'}}})
  .state('contact.vendor.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/contact/vendor/detail.html',controller:'contactVendorShowDetail'}}})
  .state('contact.vendor.show.document',{url:'/document',data:{label:'Berkas'},views:{'':{templateUrl:'view/contact/vendor/document.html',controller:'contactVendorShowDocument'}}})
  .state('contact.vendor.show.price',{url:'/price',data:{label:'Tarif'},views:{'':{templateUrl:'view/contact/vendor/price.html',controller:'contactVendorShowPrice'}}})
  .state('contact.vendor.show.app',{url:'/app',data:{label:'Akses Aplikasi'},views:{'':{templateUrl:'view/contact/vendor/app.html',controller:'contactVendorShowApp'}}})
  .state('contact.vendor.show.price.create',{url:'/create',data:{label:'Add'},views:{'@^.^':{templateUrl:'view/contact/vendor/create_price.html',controller:'contactVendorShowPriceCreate'}}})
  .state('contact.vendor.show.price.edit',{url:'/:idprice/edit',data:{label:'Edit'},views:{'@^.^':{templateUrl:'view/contact/vendor/create_price.html',controller:'contactVendorShowPriceCreate'}}})
  .state('contact.vendor.show.price.show',{url:'/:idprice',data:{label:'Detail'},views:{'@^.^':{templateUrl:'view/contact/vendor/create_price.html',controller:'contactVendorShowPriceShow'}}})

  .state('contact.driver',{url:'/driver',data:{label:'Semua Driver'},views:{'@':{templateUrl:'view/contact/driver/index.html',controller:'contactDriver'}}})
  .state('contact.driver.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/contact/driver/create.html',controller:'contactDriverCreate'}}})
  .state('contact.driver.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/contact/driver/create.html',controller:'contactDriverEdit'}}})
  .state('contact.driver.show',{url:'/:id',data:{label:'Detail Driver'},views:{'@':{templateUrl:'view/contact/driver/showHead.html',controller:'contactDriverShow'}}})
  .state('contact.driver.show.info',{url:'/info',data:{label:'Info'},views:{'':{templateUrl:'view/contact/driver/info.html',controller:'contactDriverShowInfo'}}})
  .state('contact.driver.show.vehicle',{url:'/kendaraan',data:{label:'Kendaraan'},views:{'':{templateUrl:'view/contact/driver/vehicle.html',controller:'contactDriverShowVehicle'}}})
  .state('contact.driver.show.history',{url:'/riwayat',data:{label:'Riwayat Pekerjaan'},views:{'':{templateUrl:'view/driver/driver/history.html',controller:'driverDriverShowHistory'}}})

    // PEMASARAN
    .state('marketing',{url:'/pemasaran',data:{label:'Marketing'},views:{'':{templateUrl:'view/marketing/head.html',controller:'marketing'}}})

    .state('marketing.sales_price',{url:'/sales_price',data:{label:'Sales Price'},views:{'@':{templateUrl:'view/marketing/sales_price/index.html',controller:'marketingSalesPrice'}}})
    .state('marketing.sales_price.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/sales_price/show.html',controller:'marketingSalesPriceShow'}}})
    .state('marketing.sales_price.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/sales_price/create.html',controller:'marketingSalesPriceCreate'}}})
    .state('marketing.sales_price.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/sales_price/create.html',controller:'marketingSalesPriceCreate'}}})

    .state('marketing.sales_contract',{url:'/sales_contract',data:{label:'Sales Contract'},views:{'@':{templateUrl:'view/marketing/sales_contract/index.html',controller:'marketingSalesContract'}}})
    .state('marketing.sales_contract.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/sales_contract/show.html',controller:'marketingSalesContractShow'}}})
    .state('marketing.sales_contract.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/marketing/sales_contract/detail/detail.html',controller:'marketingSalesContractShowDetail'}}})
    .state('marketing.sales_contract.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/sales_contract/create.html',controller:'marketingSalesContractCreate'}}})
    .state('marketing.sales_contract.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/sales_contract/create.html',controller:'marketingSalesContractEdit'}}})
    .state('marketing.sales_contract.show.add_contract',{url:'/create_kontrak',data:{label:'Buat Kontrak'},views:{'@':{templateUrl:'view/marketing/sales_contract/add_contract.html',controller:'marketingSalesContractShowContract'}}})


  .state('marketing.price_list',{url:'/tarif_umum',data:{label:'Price List'},views:{'@':{templateUrl:'view/marketing/price_list/index.html',controller:'marketingPriceList'}}})
  .state('marketing.price_list.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/price_list/show.html',controller:'marketingPriceListShow'}}})

  .state('marketing.price_list.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/price_list/create.html',controller:'marketingPriceListCreate'}}})
  .state('marketing.price_list.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/price_list/create.html',controller:'marketingPriceListEdit'}}})

  .state('marketing.inquery',{url:'/quotation',data:{label:'Quotation'},views:{'@':{templateUrl:'view/marketing/inquery/index.html',controller:'marketingInquery'}}})
  .state('marketing.inquery.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/inquery/create.html',controller:'marketingInqueryCreate'}}})
  .state('marketing.inquery.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/inquery/create.html',controller:'marketingInqueryCreate'}}})
  .state('marketing.inquery.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/inquery/showHead.html',controller:'marketingInqueryShow'}}})
  .state('marketing.inquery.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/marketing/inquery/detail.html',controller:'marketingInqueryShowDetail'}}})
  .state('marketing.inquery.show.offer',{url:'/penawaran',data:{label:'Penawaran'},views:{'':{templateUrl:'view/marketing/inquery/penawaran.html',controller:'marketingInqueryShowPenawaran'}}})
  .state('marketing.inquery.show.add_contract',{url:'/buat_kontrak',data:{label:'Buat Kontrak'},views:{'@':{templateUrl:'view/marketing/inquery/add_contract.html',controller:'marketingInqueryShowContract'}}})
  .state('marketing.inquery.show.cost',{url:'/biaya_operasional',data:{label:'Operational Cost'},views:{'':{templateUrl:'view/marketing/inquery/cost.html',controller:'marketingInqueryShowCost'}}})
  .state('marketing.inquery.show.document',{url:'/dokumen',data:{label:'Documents'},views:{'':{templateUrl:'view/marketing/inquery/document.html',controller:'marketingInqueryShowDocument'}}})
  .state('marketing.inquery.show.create_detail',{url:'/create_detail',data:{label:'Add Detail'},views:{'':{templateUrl:'view/marketing/inquery/add_detail.html',controller:'marketingInqueryShowCreateDetail'}}})
  .state('marketing.inquery.show.edit_detail',{url:'/:iddetail/edit_detail',data:{label:'Edit Detail'},views:{'':{templateUrl:'view/marketing/inquery/add_detail.html',controller:'marketingInqueryShowEditDetail'}}})
  .state('marketing.contract',{url:'/kontrak',data:{label:'Contract'},views:{'@':{templateUrl:'view/marketing/contract/index.html',controller:'marketingContract'}}})
  .state('marketing.contract.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/contract/show.html',controller:'marketingContractShow'}}})
  .state('marketing.contract.edit',{url:'/:id/edit',data:{label:'Edit Kontrak'},views:{'@':{templateUrl:'view/marketing/contract/edit.html',controller:'marketingContractEdit'}}})
  .state('marketing.contract.amandemen',{url:'/:id/amandemen',data:{label:'Amandemen Kontrak'},views:{'@':{templateUrl:'view/marketing/contract/amandemen.html',controller:'marketingContractAmandemen'}}})
  .state('marketing.contract.amandemen.detail',{url:'/create_detail',data:{label:'Add Detail'},views:{'@':{templateUrl:'view/marketing/contract/amandemen_detail.html',controller:'marketingContractAmandemenDetail'}}})
  .state('marketing.contract.amandemen.edit_detail',{url:'/:iddetail/edit_detail',data:{label:'Add Detail'},views:{'@':{templateUrl:'view/marketing/contract/amandemen_detail.html',controller:'marketingContractAmandemenEditDetail'}}})
  .state('marketing.contract.show.item',{url:'/penawaran',data:{label:'Item Penawaran'},views:{'':{templateUrl:'view/marketing/contract/item.html',controller:'marketingContractShowItem'}}})
  .state('marketing.contract.show.barang',{url:'/barang',data:{label:'Barang'},views:{'':{templateUrl:'view/marketing/contract/barang.html',controller:'marketingContractShowItem'}}})
  .state('marketing.contract.show.cost',{url:'/biaya',data:{label:'Biaya dan vendor'},views:{'':{templateUrl:'view/marketing/contract/cost.html',controller:'marketingContractShowCost'}}})
  .state('marketing.contract.show.document',{url:'/dokumen',data:{label:'Dokumen'},views:{'':{templateUrl:'view/marketing/contract/document.html',controller:'marketingContractShowDocument'}}})
  .state('marketing.contract.show.history',{url:'/jo_history',data:{label:'Riwayat Job Order'},views:{'':{templateUrl:'view/marketing/contract/jo_history.html',controller:'marketingContractShowJo'}}})
  .state('marketing.vendor_price',{url:'/vendor_price',data:{label:'Vendor Price'},views:{'@':{templateUrl:'view/marketing/vendor_price/index.html',controller:'marketingVendorPrice'}}})
  .state('marketing.vendor_price.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/vendor_price/create.html',controller:'marketingVendorPriceCreate'}}})
  .state('marketing.vendor_price.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/vendor_price/create.html',controller:'marketingVendorPriceCreate'}}})
  .state('marketing.vendor_price.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/vendor_price/show.html',controller:'marketingVendorPriceShow'}}})

  .state('marketing.customer_price',{url:'/tarif_customer',data:{label:'Tarif Customer'},views:{'@':{templateUrl:'view/marketing/customer_price/index.html',controller:'marketingCustomerPrice'}}})
  .state('marketing.customer_price.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/customer_price/create.html',controller:'marketingCustomerPriceCreate'}}})
  .state('marketing.customer_price.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/customer_price/create.html',controller:'marketingCustomerPriceEdit'}}})
  .state('marketing.customer_price.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/customer_price/show.html',controller:'marketingCustomerPriceShow'}}})

  .state('marketing.contract_price',{url:'/tarif_kontrak',data:{label:'Contract Price'},views:{'@':{templateUrl:'view/marketing/contract_price/index.html',controller:'marketingContractPrice'}}})
  .state('marketing.contract_price.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/contract_price/show.html',controller:'marketingContractPriceShow'}}})
  .state('marketing.lead',{url:'/leads',data:{label:'Leads'},views:{'@':{templateUrl:'view/marketing/lead/index.html',controller:'marketingLead'}}})
  .state('marketing.lead.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/lead/create.html',controller:'marketingLeadCreate'}}})
  .state('marketing.lead.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/lead/create.html',controller:'marketingLeadEdit'}}})
  .state('marketing.lead.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/lead/show.html',controller:'marketingLeadShow'}}})
  .state('marketing.lead.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/marketing/lead/show/detail.html',controller:'marketingLeadShowDetail'}}})
  .state('marketing.lead.show.activity',{url:'/aktivitas',data:{label:'Aktivitas'},views:{'':{templateUrl:'view/marketing/lead/show/activity.html',controller:'marketingLeadShowActivity'}}})
  .state('marketing.lead.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/marketing/lead/show/document.html',controller:'marketingLeadShowDocument'}}})
  .state('marketing.lead.create_opportunity',{url:'/:id/buat_opportunity',data:{label:'Buat Opportunity'},views:{'@':{templateUrl:'view/marketing/lead/create_opportunity.html',controller:'marketingLeadOpportunity'}}})
  .state('marketing.lead.create_inquery',{url:'/:id/buat_inquery',data:{label:'Buat Inquery'},views:{'@':{templateUrl:'view/marketing/lead/create_opportunity.html',controller:'marketingLeadInquery'}}})
  .state('marketing.lead.create_quotation',{url:'/:id/buat_quotation',data:{label:'Buat Quotation'},views:{'@':{templateUrl:'view/marketing/lead/create_quotation.html',controller:'marketingLeadQuotation'}}})

  .state('marketing.opportunity',{url:'/opportunity',data:{label:'Opportunity'},views:{'@':{templateUrl:'view/marketing/opportunity/index.html',controller:'marketingOppo'}}})
  .state('marketing.opportunity.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/opportunity/create.html',controller:'marketingOppoCreate'}}})
  .state('marketing.opportunity.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/opportunity/show.html',controller:'marketingOppoShow'}}})
  .state('marketing.opportunity.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/opportunity/create.html',controller:'marketingOppoEdit'}}})
  .state('marketing.opportunity.generate_inquery',{url:'/:id/buat_inquery',data:{label:'Generate Inquery'},views:{'@':{templateUrl:'view/marketing/inquery_qt/create.html',controller:'marketingOppoGenerate'}}})
  .state('marketing.opportunity.generate_quotation',{url:'/:id/buat_quotation',data:{label:'Generate Quotation'},views:{'@':{templateUrl:'view/marketing/opportunity/create_quotation.html',controller:'marketingOppoQuotation'}}})
  .state('marketing.inquery_customer',{url:'/inquery_customer',data:{label:'Inquery Customer'},views:{'@':{templateUrl:'view/marketing/inquery_customer/index.html',controller:'marketingInqueryCustomer'}}})
  .state('marketing.inquery_customer.show',{url:'/:id',data:{label:'Detail Inquery Customer'},views:{'@':{templateUrl:'view/marketing/inquery_customer/show.html',controller:'marketingInqueryCustomerShow'}}})
  .state('marketing.inquery_qt',{url:'/inquery',data:{label:'Inquery'},views:{'@':{templateUrl:'view/marketing/inquery_qt/index.html',controller:'marketingInqueryQt'}}})
  .state('marketing.inquery_qt.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/inquery_qt/create.html',controller:'marketingInqueryQtCreate'}}})
  .state('marketing.inquery_qt.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/inquery_qt/show.html',controller:'marketingInqueryQtShow'}}})
  .state('marketing.inquery_qt.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/inquery_qt/create.html',controller:'marketingInqueryQtEdit'}}})
  .state('marketing.inquery_qt.generate_quotation',{url:'/:id/buat_quotation',data:{label:'Generate Quotation'},views:{'@':{templateUrl:'view/marketing/inquery/create.html',controller:'marketingInqueryQtGenerate'}}})
  .state('marketing.report',{url:'/laporan',data:{label:'Laporan'},views:{'@':{templateUrl:'view/marketing/report/index.html',controller:'marketingReport'}}})
  .state('marketing.work_order',{url:'/work_order',data:{label:'Work Order'},views:{'@':{templateUrl:'view/marketing/work_order/index.html',controller:'operationalWO'}}})
  .state('marketing.work_order_invoice',{url:'/work_order_invoice',data:{label:'Work Order Invoice'},views:{'@':{templateUrl:'view/marketing/work_order/invoice.html',controller:'operationalWOInvoice'}}})
  .state('marketing.work_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/marketing/work_order/show.html',controller:'operationalWOShow'}}})
  .state('marketing.work_order.show.detail',{url:'/detail',data:{label:'Detail Work Order'},views:{'':{templateUrl:'view/marketing/work_order/detail/detail.html',controller:'operationalWOShowDetail'}}})
  .state('marketing.work_order.show.price',{url:'/price',data:{label:'Rincian Harga'},views:{'':{templateUrl:'view/marketing/work_order/detail/price.html',controller:'operationalWOShowPrice'}}})
  .state('marketing.work_order.show.job_order',{url:'/job_order',data:{label:'Detail Job Order'},views:{'':{templateUrl:'view/marketing/work_order/detail/job_order.html',controller:'operationalWOShowJobOrder'}}})
  .state('marketing.work_order.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/marketing/work_order/create.html',controller:'operationalWOCreate'}}})
  .state('marketing.work_order.save_as',{url:'/save_as/:id',data:{label:'Save As'},views:{'@':{templateUrl:'view/marketing/work_order/create.html',controller:'operationalWOCreate'}}})
  .state('marketing.work_order.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/marketing/work_order/edit.html',controller:'operationalWOEdit'}}})
  .state('marketing.work_order.show_request',{url:'/:id/request',data:{label:'Permintaan WO'},views:{'@':{templateUrl:'view/marketing/work_order/show_request.html',controller:'operationalWOShowRequest'}}})
  .state('marketing.work_order.create_request',{url:'/:idrequest/create_wo',data:{label:'Add Permintaan WO'},views:{'@':{templateUrl:'view/marketing/work_order/create.html',controller:'operationalWOCreate'}}})
  .state('marketing.activity_work_order',{url:'/aktivitas_wo',data:{label:'Aktivitas WO'},views:{'@':{templateUrl:'view/marketing/activity/wo.html',controller:'marketingActivityWO'}}})
  .state('marketing.activity_job_order',{url:'/aktivitas_jo',data:{label:'Aktivitas JO'},views:{'@':{templateUrl:'view/marketing/activity/jo.html',controller:'marketingActivityJO'}}})
  .state('marketing.activity_job_order.show',{url:'/:id',data:{label:'Aktivitas JO'},views:{'@':{templateUrl:'view/marketing/activity/jo_show.html',controller:'marketingActivityJOShow'}}})
  .state('marketing.operational_notification',{url:'/notifikasi',data:{label:'Daftar Notifikasi'},views:{'@':{templateUrl:'view/marketing/operational_notification/index.html',controller:'marketingNotification'}}})

  //KEUANGAN
  .state('finance',{url:'/keuangan',data:{label:'Finance'},views:{'':{templateUrl:'view/finance/head.html',controller:'finance'}}})
  .state('finance.journal',{url:'/journal',data:{label:'Jurnal Umum'},views:{'@':{templateUrl:'view/finance/journal/index.html',controller:'financeJournal'}}})
  .state('finance.journal.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/journal/create.html',controller:'financeJournalCreate'}}})
  .state('finance.journal.create_audit',{url:'/create/audit/:year',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/journal/create.html',controller:'financeJournalCreate'}}})
  .state('finance.journal.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/journal/edit.html',controller:'financeJournalEdit'}}})
  .state('finance.journal.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/journal/show.html',controller:'financeJournalShow'}}})
  .state('finance.journal.create_favorite',{url:'/create_favorite',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/journal/favorite.html',controller:'financeJournalFavorite'}}})
  .state('finance.journal_notification',{url:'/jurnal_notifikasi',data:{label:'Daftar Notifikasi Jurnal'},views:{'@':{templateUrl:'view/finance/journal_notification/index.html',controller:'journalNotification'}}})

  .state('finance.kelompok_asset',{url:'/kelompok_asset',data:{label:'Kelompok Asset'},views:{'@':{templateUrl:'view/finance/Asset/kelompok_asset.html',controller:'KelompokAsset'}}})
  .state('finance.kelompok_asset.create',{url:'/kelompok_asset_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/Asset/kelompok_asset_create.html',controller:'KelompokAssetCreate'}}})
  .state('finance.kelompok_asset.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/Asset/kelompok_asset_create.html',controller:'KelompokAssetEdit'}}})

  .state('finance.saldoawal_asset',{url:'/saldoawal_asset',data:{label:'Saldo Awal Asset'},views:{'@':{templateUrl:'view/finance/Asset/saldoawal_asset.html',controller:'SaldoAwal'}}})
  .state('finance.saldoawal_asset.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/Asset/saldoawal_asset_create.html',controller:'SaldoAwalAssetCreate'}}})
  .state('finance.saldoawal_asset.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/Asset/saldoawal_asset_create.html',controller:'SaldoAwalAssetEdit'}}})
  .state('finance.saldoawal_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/saldoawal_asset_show.html',controller:'SaldoAwalAssetShow'}}})
  .state('finance.pembelian_asset',{url:'/pembelian_asset',data:{label:'Pembelian Awal Asset'},views:{'@':{templateUrl:'view/finance/Asset/pembelian_asset.html',controller:'PembelianAsset'}}})
  .state('finance.pembelian_asset.create',{url:'/pembelian_asset_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/Asset/pembelian_asset_create.html',controller:'PembelianAssetCreate'}}})
  .state('finance.pembelian_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/pembelian_asset_show.html',controller:'PembelianAssetShow'}}})
  .state('finance.pembelian_asset.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/Asset/pembelian_asset_create.html',controller:'PembelianAssetCreate'}}})

  .state('finance.daftar_asset',{url:'/daftar_asset',data:{label:'Daftar Asset'},views:{'@':{templateUrl:'view/finance/Asset/daftar_asset.html',controller:'DaftarAsset'}}})
  .state('finance.daftar_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/daftar_asset_show.html',controller:'DaftarAssetShow'}}})

  .state('finance.depresiasi_asset',{url:'/depresiasi_asset',data:{label:'Depresiasi Asset'},views:{'@':{templateUrl:'view/finance/Asset/depresiasi_asset.html',controller:'DepresiasiAsset'}}})
  .state('finance.depresiasi_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/depresiasi_asset_show.html',controller:'DepresiasiAssetShow'}}})

  .state('finance.pengafkiran_asset',{url:'/pengafkiran_asset',data:{label:'Pengafkiran Asset'},views:{'@':{templateUrl:'view/finance/Asset/pengafkiran_asset.html',controller:'PengafkiranAsset'}}})

  .state('finance.pengafkiran_asset.create',{url:'/pengafkiran_asset_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/Asset/pengafkiran_asset_create.html',controller:'PengafkiranAssetCreate'}}})
  .state('finance.pengafkiran_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/pengafkiran_asset_show.html',controller:'PengafkiranAssetShow'}}})
  .state('finance.pengafkiran_asset.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/Asset/pengafkiran_asset_create.html',controller:'PengafkiranAssetCreate'}}})

  .state('finance.penjualan_asset',{url:'/penjualan_asset',data:{label:'Penjualan Asset'},views:{'@':{templateUrl:'view/finance/Asset/penjualan_asset.html',controller:'PenjualanAsset'}}})
  .state('finance.penjualan_asset.create',{url:'/penjualan_asset_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/Asset/penjualan_asset_create.html',controller:'PenjualanAssetCreate'}}})
  .state('finance.penjualan_asset.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/Asset/penjualan_asset_show.html',controller:'PenjualanAssetShow'}}})
  .state('finance.penjualan_asset.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/Asset/penjualan_asset_create.html',controller:'PenjualanAssetEdit'}}})

  .state('finance.um_supplier',{url:'/deposit_supplier',data:{label:'Deposit Supplier'},views:{'@':{templateUrl:'view/finance/um_supplier/index.html',controller:'financeUmSupplier'}}})
  .state('finance.um_supplier.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/um_supplier/create.html',controller:'financeUmSupplierCreate'}}})
  .state('finance.um_supplier.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/um_supplier/show.html',controller:'financeUmSupplierShow'}}})

  .state('finance.um_customer',{url:'/deposit_customer',data:{label:'Deposit Customer'},views:{'@':{templateUrl:'view/finance/um_customer/index.html',controller:'financeUmCustomer'}}})
  .state('finance.um_customer.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/um_customer/create.html',controller:'financeUmCustomerCreate'}}})
  .state('finance.um_customer.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/um_customer/show.html',controller:'financeUmCustomerShow'}}})

  .state('finance.notahutang',{url:'/nota_hutang',data:{label:'Nota Hutang'},views:{'@':{templateUrl:'view/finance/notahutang/index.html',controller:'NotaHutang'}}})
  .state('finance.notahutang.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/notahutang/create.html',controller:'NotaHutangCreate'}}})
  .state('finance.notahutang.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/notahutang/show.html',controller:'NotaHutangShow'}}})

  .state('finance.draft_list_piutang',{url:'/draft_list_piutang',data:{label:'Draft List Piutang'},views:{'@':{templateUrl:'view/finance/draft_list_piutang/index.html',controller:'DraftListPiutang'}}})
  .state('finance.draft_list_piutang.show',{url:'/show',data:{label:'Detail Draft Piutang'},views:{'@':{templateUrl:'view/finance/draft_list_piutang/show.html',controller:'DraftListPiutangShow'}}})

  .state('finance.draft_list_hutang',{url:'/draft_list_hutang',data:{label:'Draft List Hutang'},views:{'@':{templateUrl:'view/finance/draft_list_hutang/index.html',controller:'DraftListHutang'}}})
  .state('finance.draft_list_hutang.show',{url:'/:id',data:{label:'Detail Draft Hutang'},views:{'@':{templateUrl:'view/finance/draft_list_hutang/show.html',controller:'DraftListHutangShow'}}})

  .state('finance.bill_receivable',{url:'/pembayaran_piutang',data:{label:'Receivable Payment'},views:{'@':{templateUrl:'view/finance/bill_receivable/index.html',controller:'financeBillReceivable'}}})
  .state('finance.bill_receivable.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/bill_receivable/create.html',controller:'financeBillReceivableCreate'}}})
  .state('finance.bill_receivable.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/bill_receivable/show.html',controller:'financeBillReceivableShow'}}})
  .state('finance.bill_receivable.payment',{url:'/:id/payment',data:{label:'Pembayaran'},views:{'@':{templateUrl:'view/finance/bill_receivable/payment.html',controller:'financeBillReceivablePayment'}}})
  .state('finance.bill_receivable.payment.edit',{url:'/edit',data:{label:'Edit Pembayaran'},views:{'@':{templateUrl:'view/finance/bill_receivable/payment.html',controller:'financeBillReceivablePayment'}}})
  .state('finance.bill_payment',{url:'/penerimaan_pembayaran',data:{label:'Penerimaan Pembayaran'},views:{'@':{templateUrl:'view/finance/bill_payment/index.html',controller:'financeBillPayment'}}})
  .state('finance.bill_payment.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/bill_payment/show.html',controller:'financeBillPaymentShow'}}})
  .state('finance.debt_payable',{url:'/permintaan_pembayaran',data:{label:'Pembayaran Hutang'},views:{'@':{templateUrl:'view/finance/debt_payable/index.html',controller:'financeDebtPayable'}}})
  .state('finance.debt_payable.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/debt_payable/create.html',controller:'financeDebtPayableCreate'}}})
  .state('finance.debt_payable.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/debt_payable/create.html',controller:'financeDebtPayableEdit'}}})
  .state('finance.debt_payable.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/debt_payable/show.html',controller:'financeDebtPayableShow'}}})
  .state('finance.debt_payable.payment',{url:'/:id/payment',data:{label:'Penerimaan Pembayaran'},views:{'@':{templateUrl:'view/finance/debt_payable/payment.html',controller:'financeDebtPayablePayment'}}})
  .state('finance.debt_payment',{url:'/pembayaran_hutang',data:{label:'Pembayaran Hutang'},views:{'@':{templateUrl:'view/finance/debt_payment/index.html',controller:'financeDebtPayment'}}})
  .state('finance.debt_payment.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/debt_payment/show.html',controller:'financeDebtPaymentShow'}}})

  .state('finance.nota_credit',{url:'/nota_potong_penjualan',data:{label:'Nota Potong Penjualan'},views:{'@':{templateUrl:'view/finance/nota_credit/index.html',controller:'financeNotaCredit'}}})
  .state('finance.nota_credit.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/nota_credit/create.html',controller:'financeNotaCreditCreate'}}})
  .state('finance.nota_credit.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/nota_credit/show.html',controller:'financeNotaCreditShow'}}})
  .state('finance.nota_debet',{url:'/nota_potong_pembelian',data:{label:'Nota Potong Pembelian'},views:{'@':{templateUrl:'view/finance/nota_debet/index.html',controller:'financeNotaDebet'}}})
  .state('finance.nota_debet.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/nota_debet/create.html',controller:'financeNotaDebetCreate'}}})
  .state('finance.nota_debet.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/nota_debet/show.html',controller:'financeNotaDebetShow'}}})

  .state('finance.cek_giro',{url:'/cek_giro',data:{label:'Transaksi Cek & Giro'},views:{'@':{templateUrl:'view/finance/cek_giro/index.html',controller:'financeCekGiro'}}})
  .state('finance.cek_giro.create',{url:'/cek_giro',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/cek_giro/create.html',controller:'financeCekGiroCreate'}}})
  .state('finance.cek_giro.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/cek_giro/detail.html',controller:'financeCekGiroDetail'}}})

  .state('finance.permintaan_mutasi',{url:'/permintaan_mutasi',data:{label:'Permintaan Mutasi'},views:{'@':{templateUrl:'view/finance/mutasi/permintaan_mutasi.html',controller:'PermintaanMutasi'}}})
  .state('finance.permintaan_mutasi.create',{url:'/permintaan_mutasi_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/mutasi/permintaan_mutasi_create.html',controller:'PermintaanMutasiCreate'}}})
  .state('finance.permintaan_mutasi.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/mutasi/permintaan_mutasi_create.html',controller:'PermintaanMutasiEdit'}}})
  .state('finance.permintaan_mutasi.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/mutasi/permintaan_mutasi_show.html',controller:'PermintaanMutasiShow'}}})

  .state('finance.realisasi_mutasi',{url:'/realisasi_mutasi',data:{label:'Realisasi Mutasi'},views:{'@':{templateUrl:'view/finance/mutasi/realisasi_mutasi.html',controller:'RealisasiMutasi'}}})
  .state('finance.realisasi_mutasi.create',{url:'/realisasi_mutasi_create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/mutasi/realisasi_mutasi_create.html',controller:'RealisasiMutasiCreate'}}})
  .state('finance.realisasi_mutasi.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/mutasi/realisasi_mutasi_show.html',controller:'RealisasiMutasiShow'}}})

  .state('finance.report',{url:'/report',data:{label:'Laporan Keuangan'},views:{'@':{templateUrl:'view/finance/report/index.html',controller:'financeReport'}}})
  .state('finance.report.account',{url:'/daftar_akun',data:{label:'Laporan Daftar Akun'},views:{'@':{templateUrl:'view/finance/report/account.html',controller:'financeReport'}}})
  .state('finance.report.journal',{url:'/jurnal_umum',data:{label:'Laporan Jurnal Umum'},views:{'@':{templateUrl:'view/finance/report/journal.html',controller:'financeReportJournal'}}})
  .state('finance.report.ledger',{url:'/buku_besar',data:{label:'Laporan Buku Besar'},views:{'@':{templateUrl:'view/finance/report/ledger.html',controller:'financeReportLedger'}}})
  .state('finance.report.ledger_receivable',{url:'/buku_besar_piutang',data:{label:'Laporan Buku Besar Piutang'},views:{'@':{templateUrl:'view/finance/report/ledger_receivable.html',controller:'financeReportLedgerReceivable'}}})
  .state('finance.report.ledger_payable',{url:'/buku_besar_hutang',data:{label:'Laporan Buku Besar Hutang'},views:{'@':{templateUrl:'view/finance/report/ledger_payable.html',controller:'financeReportLedgerPayable'}}})
  .state('finance.report.ledger_um_supplier',{url:'/buku_besar_um_supplier',data:{label:'Laporan Buku Besar Uang Muka Supplier'},views:{'@':{templateUrl:'view/finance/report/ledger_um_supplier.html',controller:'financeReportLedgerUmSupplier'}}})
  .state('finance.report.ledger_um_customer',{url:'/buku_besar_um_customer',data:{label:'Laporan Buku Besar Uang Muka Customer'},views:{'@':{templateUrl:'view/finance/report/ledger_um_customer.html',controller:'financeReportLedgerUmCustomer'}}})
  .state('finance.report.neraca_saldo',{url:'/neraca_saldo',data:{label:'Laporan Neraca Saldo'},views:{'@':{templateUrl:'view/finance/report/neraca_saldo.html',controller:'financeReportNeracaSaldo'}}})
  .state('finance.report.neraca_lajur',{url:'/neraca_lajur',data:{label:'Laporan Neraca Lajur'},views:{'@':{templateUrl:'view/finance/report/neraca_lajur.html',controller:'financeReportNeracaLajur'}}})
  .state('finance.report.neraca_saldo_banding',{url:'/neraca_saldo_banding',data:{label:'Laporan Neraca Saldo Perbandingan'},views:{'@':{templateUrl:'view/finance/report/neraca_saldo_banding.html',controller:'financeReportNeracaSaldoBanding'}}})
  .state('finance.report.laba_rugi',{url:'/laba_rugi',data:{label:'Laporan Laba Rugi'},views:{'@':{templateUrl:'view/finance/report/laba_rugi.html',controller:'financeReportLabaRugi'}}})
  .state('finance.report.ekuitas',{url:'/ekuitas',data:{label:'Laporan Ekuitas'},views:{'@':{templateUrl:'view/finance/report/ekuitas.html',controller:'financeReportEkuitas'}}})
  .state('finance.report.ekuitas_banding',{url:'/ekuitas_banding',data:{label:'Laporan Ekuitas Perbandingan'},views:{'@':{templateUrl:'view/finance/report/ekuitas_banding.html',controller:'financeReportEkuitasBanding'}}})
  .state('finance.report.posisi_keuangan',{url:'/posisi_keuangan',data:{label:'Laporan Posisi Keuangan'},views:{'@':{templateUrl:'view/finance/report/posisi_keuangan.html',controller:'financeReportPosisiKeuangan'}}})
  .state('finance.report.posisi_keuangan_banding',{url:'/posisi_keuangan_banding',data:{label:'Laporan Posisi Keuangan Perbandingan'},views:{'@':{templateUrl:'view/finance/report/posisi_keuangan_banding.html',controller:'financeReportPosisiKeuanganBanding'}}})
  .state('finance.report.arus_kas',{url:'/arus_kas',data:{label:'Laporan Arus Kas'},views:{'@':{templateUrl:'view/finance/report/arus_kas.html',controller:'financeReportArusKas'}}})
  .state('finance.report.arus_kas_banding',{url:'/arus_kas_banding',data:{label:'Laporan Arus Kas Perbandingan'},views:{'@':{templateUrl:'view/finance/report/arus_kas_banding.html',controller:'financeReportArusKasBanding'}}})
  .state('finance.cash_transaction',{url:'/transaksi_kas',data:{label:'Transaksi Kas / Bank'},views:{'@':{templateUrl:'view/finance/cash_transaction/index.html',controller:'financeCash'}}})
  .state('finance.cash_transaction.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/cash_transaction/create.html',controller:'financeCashCreate'}}})
  .state('finance.cash_transaction.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/cash_transaction/show.html',controller:'financeCashShow'}}})
  .state('finance.cash_transaction.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/cash_transaction/edit.html',controller:'financeCashEdit'}}})
  .state('finance.submission_cost',{url:'/pengajuan_biaya',data:{label:'Pengajuan Biaya'},views:{'@':{templateUrl:'view/finance/submission_cost/index.html',controller:'financeSubmission'}}})
  .state('finance.submission_cost.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/submission_cost/show.html',controller:'financeSubmissionShow'}}})

  .state('finance.cash_count',{url:'/cash_count',data:{label:'Cash Count'},views:{'@':{templateUrl:'view/finance/cash_count/index.html',controller:'financeCashCount'}}})
  .state('finance.cash_count.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/cash_count/create.html',controller:'financeCashCountCreate'}}})
  .state('finance.cash_count.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/cash_count/show.html',controller:'financeCashCountShow'}}})
  .state('finance.cash_count.cash_transaction',{url:'/cash_transaction',data:{label:'Transaksi kas'},views:{'@':{templateUrl:'view/finance/cash_count/cash_transaction.html',controller:'financeCash'}}})

  .state('finance.kas_bon',{url:'/kas_bon',data:{label:'Kas Bon'},views:{'@':{templateUrl:'view/finance/kas_bon/index.html',controller:'financeKasBon'}}})
  .state('finance.kas_bon.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/kas_bon/create.html',controller:'financeKasBonCreate'}}})
  .state('finance.kas_bon.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/finance/kas_bon/show.html',controller:'financeKasBonShow'}}})
  .state('finance.kas_bon.createkas',{url:'/:id',data:{label:'Add'},views:{'@':{templateUrl:'view/finance/cash_transaction/create.html',controller:'financeCashCreate'}}})
  .state('finance.kas_bon.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/finance/kas_bon/create.html',controller:'financeKasBonEdit'}}})  .state('finance.report.outstanding_debt',{url:'/outstanding_debt',data:{label:'Laporan Outstanding Piutang'},views:{'@':{templateUrl:'view/finance/report/outstanding_debt.html',controller:'financeReportOutstandingDebt'}}})
  .state('finance.report.outstanding_credit',{url:'/outstanding_credit',data:{label:'Laporan Outstanding Hutang'},views:{'@':{templateUrl:'view/finance/report/outstanding_credit.html',controller:'financeReportOutstandingCredit'}}})
  .state('finance.report.laba_rugi_perbandingan',{url:'/laba_rugi_perbandingan',data:{label:'Laporan Laba Rugi Perbandingan'},views:{'@':{templateUrl:'view/finance/report/laba_rugi_perbandingan.html',controller:'financeReportProfitComparison'}}})
  .state('finance.closing',{url:'/closing',data:{label:'Closing'},views:{'@':{templateUrl:'view/finance/closing/index.html',controller:'closing'}}})
  .state('finance.pajak', {url:'/pajak', data: { label:'Pajak'}, views: {'@':{templateUrl:'view/finance/pajak/index.html', controller: 'pajak'}}})
  // Date: 06-03-2020; Description: Menambah menu faktur pajak; Developer: rizal; Status: Edit

  //KENDARAAN
  .state('vehicle',{url:'/kendaraan',data:{label:'Kendaraan Operasional'},views:{'':{templateUrl:'view/vehicle/head.html',controller:'vehicle'}}})
  .state('vehicle.vehicle',{url:'/semua_kendaraan',data:{label:'Semua Kendaraan'},views:{'@':{templateUrl:'view/vehicle/vehicle/index.html',controller:'vehicleVehicle'}}})
  .state('vehicle.vehicle.print',{url:'/print',data:{label:'Print'},views:{'@':{templateUrl:'view/vehicle/vehicle/create.html',controller:'vehicleVehicleCreate'}}})
  .state('vehicle.vehicle.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/vehicle/vehicle/create.html',controller:'vehicleVehicleCreate'}}})
  .state('vehicle.vehicle.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/vehicle/vehicle/create.html',controller:'vehicleVehicleEdit'}}})
  .state('vehicle.vehicle.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/vehicle/vehicle/showHead.html',controller:'vehicleVehicleShow'}}})
  .state('vehicle.vehicle.show.card',{url:'/card',data:{label:'Dashboard'},views:{'':{templateUrl:'view/vehicle/vehicle/card.html',controller:'vehicleVehicleShowCard'}}})
  .state('vehicle.vehicle.show.detail',{url:'/detail',data:{label:'Detail'},views:{'':{templateUrl:'view/vehicle/vehicle/detail.html',controller:'vehicleVehicleShowDetail'}}})
  .state('vehicle.vehicle.show.maintenance',{url:'/perawatan',data:{label:'Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance.html',controller:'vehicleVehicleShowMaintenance'}}})

  .state('vehicle.vehicle.show.maintenance.create',{url:'/create',data:{label:'Add Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/create_perawatan.html',controller:'vehicleVehicleShowMaintenanceCreate'}}})
  .state('vehicle.vehicle.show.maintenance.show',{url:'/detail/:vm_id',data:{label:'Detail Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/detail_perawatan.html',controller:'vehicleVehicleShowMaintenanceShow'}}})
  .state('vehicle.vehicle.show.maintenance.pengajuan',{url:'/pengajuan',data:{label:'Pengajuan Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/table_perawatan.html',controller:'vehicleVehicleShowMaintenancePengajuan'}}})
  .state('vehicle.vehicle.show.maintenance.rencana',{url:'/rencana',data:{label:'Rencana Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/table_perawatan.html',controller:'vehicleVehicleShowMaintenanceRencana'}}})
  .state('vehicle.vehicle.show.maintenance.perawatan',{url:'/perawatan',data:{label:'Perawatan Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/table_perawatan.html',controller:'vehicleVehicleShowMaintenancePerawatan'}}})
  .state('vehicle.vehicle.show.maintenance.selesai',{url:'/selesai',data:{label:'Selesai Perawatan'},views:{'':{templateUrl:'view/vehicle/vehicle/maintenance/table_perawatan.html',controller:'vehicleVehicleShowMaintenanceSelesai'}}})
  .state('vehicle.vehicle.show.maintenance.show.edit_rencana',{url:'/edit_rencana',data:{label:'Rencana Perawatan'},views:{'@^.^':{templateUrl:'view/vehicle/vehicle/maintenance/edit_perawatan.html',controller:'vehicleVehicleShowMaintenanceEditRencana'}}})
  .state('vehicle.vehicle.show.maintenance.show.edit_realisasi',{url:'/edit_realisasi',data:{label:'Realisasi Perawatan'},views:{'@^.^':{templateUrl:'view/vehicle/vehicle/maintenance/edit_perawatan.html',controller:'vehicleVehicleShowMaintenanceEditRealisasi'}}})

  .state('vehicle.vehicle.show.detail.detail',{url:'/detail',data:{label:'Detail Info'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/info.html',controller:'vehicleVehicleShowDetailDetail'}}})
  .state('vehicle.vehicle.show.detail.driver',{url:'/driver',data:{label:'Driver'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/driver.html',controller:'vehicleVehicleShowDetailDriver'}}})
  .state('vehicle.vehicle.show.detail.body',{url:'/body',data:{label:'Bodi'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/body.html',controller:'vehicleVehicleShowDetailBody'}}})
  .state('vehicle.vehicle.show.detail.checklist',{url:'/kelengkapan',data:{label:'Kelengkapan'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/checklist.html',controller:'vehicleVehicleShowDetailChecklist'}}})
  .state('vehicle.vehicle.show.detail.insurance',{url:'/asuransi',data:{label:'Asuransi'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/insurance.html',controller:'vehicleVehicleShowDetailInsurance'}}})
  .state('vehicle.vehicle.show.detail.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/document.html',controller:'vehicleVehicleShowDetailDocument'}}})
  .state('vehicle.vehicle.show.detail.rate',{url:'/ritase',data:{label:'Ritase'},views:{'':{templateUrl:'view/vehicle/vehicle/detail/rate.html',controller:'vehicleVehicleShowDetailRate'}}})

  .state('vehicle.vehicle_distance',{url:'/kilometer_kendaraan',data:{label:'KM Kendaraan'},views:{'@':{templateUrl:'view/vehicle/vehicle_distance/index.html',controller:'vehicleVehicleDistance'}}})
  .state('vehicle.vehicle_check',{url:'/pengecekan_kendaraan',data:{label:'Pengecekan Kendaraan'},views:{'@':{templateUrl:'view/vehicle/vehicle_check/index.html',controller:'vehicleVehicleCheck'}}})
  .state('vehicle.vehicle_check.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/vehicle/vehicle_check/create.html',controller:'vehicleVehicleCheckCreate'}}})
  .state('vehicle.vehicle_check.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/vehicle/vehicle_check/create.html',controller:'vehicleVehicleCheckEdit'}}})
  .state('vehicle.vehicle_check.show',{url:'/:id',data:{label:'Add'},views:{'@':{templateUrl:'view/vehicle/vehicle_check/show.html',controller:'vehicleVehicleCheckShow'}}})

  //VENDOR
  .state('vendor',{url:'/vendor',data:{label:'Vendor'},views:{'':{templateUrl:'view/vendor/head.html',controller:'vendor'}}})

  .state('vendor.vendor',{url:'/daftar_vendor',data:{label:'Daftar Vendor'},views:{'@':{templateUrl:'view/vendor/vendor/index.html',controller:'vendorList'}}})
  .state('vendor.vendor.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/vendor/vendor/showHead.html',controller:'vendorRegisterShow'}}})
  .state('vendor.vendor.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/vendor/register_vendor/detail.html',controller:'vendorRegisterShowDetail'}}})
  .state('vendor.vendor.show.document',{url:'/document',data:{label:'Berkas'},views:{'':{templateUrl:'view/vendor/register_vendor/document.html',controller:'vendorRegisterShowDocument'}}})
  .state('vendor.vendor.show.price',{url:'/price',data:{label:'Tarif'},views:{'':{templateUrl:'view/vendor/vendor/price.html',controller:'vendorListShowPrice'}}})
  .state('vendor.vendor.show.price.create',{url:'/create',data:{label:'Add'},views:{'@^.^':{templateUrl:'view/vendor/vendor/create_price.html',controller:'vendorListShowPriceCreate'}}})
  .state('vendor.vendor.show.price.edit',{url:'/:idprice/edit',data:{label:'Edit'},views:{'@^.^':{templateUrl:'view/vendor/vendor/create_price.html',controller:'vendorListShowPriceCreate'}}})

  .state('vendor.register_vendor',{url:'/register_vendor',data:{label:'Vendor'},views:{'@':{templateUrl:'view/vendor/register_vendor/index.html',controller:'vendorRegister'}}})
  .state('vendor.register_vendor.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/vendor/register_vendor/create.html',controller:'vendorRegisterCreate'}}})
  .state('vendor.register_vendor.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/vendor/register_vendor/showHead.html',controller:'vendorRegisterShow'}}})
  .state('vendor.register_vendor.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/vendor/register_vendor/create.html',controller:'vendorRegisterEdit'}}})
  .state('vendor.register_vendor.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/vendor/register_vendor/detail.html',controller:'vendorRegisterShowDetail'}}})
  .state('vendor.register_vendor.show.document',{url:'/document',data:{label:'Berkas'},views:{'':{templateUrl:'view/vendor/register_vendor/document.html',controller:'vendorRegisterShowDocument'}}})
  .state('vendor.register_vendor.show.price',{url:'/price',data:{label:'Tarif'},views:{'':{templateUrl:'view/vendor/register_vendor/price.html',controller:'vendorRegisterShowPrice'}}})
  .state('vendor.register_vendor.show.price.create',{url:'/create',data:{label:'Add'},views:{'@^.^':{templateUrl:'view/vendor/register_vendor/create_price.html',controller:'vendorRegisterShowPriceCreate'}}})
  .state('vendor.register_vendor.show.price.edit',{url:'/:idprice/edit',data:{label:'Edit'},views:{'@^.^':{templateUrl:'view/vendor/register_vendor/create_price.html',controller:'vendorRegisterShowPriceEdit'}}})

  .state('vendor.job_order',{url:'/job_order',data:{label:'Job Order'},views:{'@':{templateUrl:'view/vendor/job_order/index.html',controller:'vendorJobOrder'}}})

  //DRIVER
  .state('driver',{url:'/driver',data:{label:'Driver'},views:{'':{templateUrl:'view/driver/head.html',controller:'driver'}}})
  .state('driver.driver',{url:'/semua_driver',data:{label:'Semua Driver'},views:{'@':{templateUrl:'view/driver/driver/index.html',controller:'driverDriver'}}})
  .state('driver.driver.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/driver/driver/create.html',controller:'driverDriverCreate'}}})
  .state('driver.driver.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/driver/driver/create.html',controller:'driverDriverEdit'}}})
  .state('driver.driver.show',{url:'/:id',data:{label:'Detail Driver'},views:{'@':{templateUrl:'view/driver/driver/showHead.html',controller:'driverDriverShow'}}})
  .state('driver.driver.show.info',{url:'/info',data:{label:'Info'},views:{'':{templateUrl:'view/driver/driver/info.html',controller:'driverDriverShowInfo'}}})
  .state('driver.driver.show.vehicle',{url:'/kendaraan',data:{label:'Kendaraan'},views:{'':{templateUrl:'view/driver/driver/vehicle.html',controller:'driverDriverShowVehicle'}}})
  .state('driver.driver.show.history',{url:'/riwayat',data:{label:'Riwayat Pekerjaan'},views:{'':{templateUrl:'view/driver/driver/history.html',controller:'driverDriverShowHistory'}}})

  //INVENTORY
  .state('inventory',{url:'/inventory',data:{label:'Inventory'},views:{'':{templateUrl:'view/inventory/head.html',controller:'inventory'}}})
  .state('inventory.dashboard',{url:'/dashboard',data:{label:'Dashboard'},views:{'@':{templateUrl:'view/inventory/dashboard/index.html',controller:'inventoryDashboard'}}})
  .state('inventory.category',{url:'/kategori',data:{label:'Item Categories'},views:{'@':{templateUrl:'view/inventory/category/index.html',controller:'inventoryCategory'}}})
  .state('inventory.category.create',{url:'/create',data:{label:'Add New'},views:{'@':{templateUrl:'view/inventory/category/create.html',controller:'inventoryCategoryCreate'}}})
  .state('inventory.category.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/category/create.html',controller:'inventoryCategoryEdit'}}})
  .state('inventory.category.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/category/show.html',controller:'inventoryCategoryShow'}}})
  .state('inventory.item',{url:'/master_item',data:{label:'Master Item'},views:{'@':{templateUrl:'view/inventory/item/index.html',controller:'inventoryItem'}}})
  .state('inventory.item.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/item/create_new.html',controller:'inventoryItemCreateNew'}}})
  .state('inventory.item.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/item/show.html',controller:'inventoryItemShow'}}})
  .state('inventory.item.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/item/create_new.html',controller:'inventoryItemCreateNew'}}})
  .state('inventory.stock_initial',{url:'/stock_initial',data:{label:'Initial Inventory'},views:{'@':{templateUrl:'view/inventory/stock_initial/index.html',controller:'inventoryStockInitial'}}})
  .state('inventory.stock_initial.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/stock_initial/create.html',controller:'inventoryStockInitialCreate'}}})
  .state('inventory.stock_initial.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/stock_initial/create.html',controller:'inventoryStockInitialEdit'}}})
  .state('inventory.purchase_request',{url:'/permintaan_pembelian',data:{label:'Permintaan Pembelian'},views:{'@':{templateUrl:'view/inventory/purchase_request/index.html',controller:'inventoryPurchaseRequest'}}})
  .state('inventory.purchase_request.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/purchase_request/create.html',controller:'inventoryPurchaseRequestCreate'}}})
  .state('inventory.purchase_request.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/purchase_request/create.html',controller:'inventoryPurchaseRequestCreate'}}})
  .state('inventory.purchase_request.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/purchase_request/show.html',controller:'inventoryPurchaseRequestShow'}}})
  .state('inventory.purchase_order',{url:'/purchase_order',data:{label:'Purchase Order'},views:{'@':{templateUrl:'view/inventory/purchase_order/index.html',controller:'inventoryPurchaseOrder'}}})
  .state('inventory.purchase_order.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/purchase_order/create.html',controller:'inventoryPurchaseOrderCreate'}}})
  .state('inventory.purchase_order.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/purchase_order/create.html',controller:'inventoryPurchaseOrderCreate'}}})
  .state('inventory.purchase_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/purchase_order/show.html',controller:'inventoryPurchaseOrderShow'}}})

  .state('inventory.receipt',{url:'/penerimaan_barang',data:{label:'Penerimaan Barang'},views:{'@':{templateUrl:'view/inventory/receipt/index.html',controller:'inventoryReceipt'}}})
  .state('inventory.receipt.create',{url:'/create/:po_id',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/receipt/create.html',controller:'inventoryReceiptCreate'}}})
  .state('inventory.receipt.create_gift',{url:'/create_gift',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/receipt/create_gift.html',controller:'inventoryReceiptCreateGift'}}})
  .state('inventory.receipt.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/receipt/show.html',controller:'inventoryReceiptShow'}}})

  .state('inventory.quality_check',{url:'/quality_check',data:{label:'Quality Check'},views:{'@':{templateUrl:'view/inventory/quality_check/index.html',controller:'inventoryQualityCheck'}}})
  .state('inventory.quality_check.create',{url:'/create/:po_id',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/quality_check/create.html',controller:'inventoryQualityCheckCreate'}}})
  .state('inventory.quality_check.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/quality_check/show.html',controller:'inventoryQualityCheckShow'}}})

  .state('inventory.picking_order',{url:'/picking_order',data:{label:'Picking Order'},views:{'@':{templateUrl:'view/inventory/picking_order/index.html',controller:'invPickingOrder'}}})
  .state('inventory.picking_order.create',{url:'/create',data:{label:'Picking Order Create'},views:{'@':{templateUrl:'view/inventory/picking_order/create.html',controller:'invPickingOrderCreate'}}})
  .state('inventory.picking_order.show',{url:'/:id',data:{label:'Picking Order Details'},views:{'@':{templateUrl:'view/inventory/picking_order/show.html',controller:'invPickingOrderShow'}}})
  .state('inventory.adjustment',{url:'/penyesuaian_stock',data:{label:'Penyesuaian Stock Barang'},views:{'@':{templateUrl:'view/inventory/adjustment/index.html',controller:'inventoryAdjustment'}}})
  .state('inventory.adjustment.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/adjustment/create.html',controller:'inventoryAdjustmentCreate'}}})
  .state('inventory.adjustment.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/adjustment/show.html',controller:'inventoryAdjustmentShow'}}})
  .state('inventory.stock_by_item',{url:'/stock_by_item',data:{label:'Stock By Item'},views:{'@':{templateUrl:'view/inventory/stock_by_item/index.html',controller:'inventoryStockByItem'}}})
  .state('inventory.warehouse_stock',{url:'/stok_gudang',data:{label:'Stok Gudang'},views:{'@':{templateUrl:'view/inventory/warehouse_stock/index.html',controller:'inventoryWarehouseStock'}}})
  .state('inventory.stock_transaction',{url:'/kartu_persediaan',data:{label:'Kartu Persediaan'},views:{'@':{templateUrl:'view/inventory/stock_transaction/index.html',controller:'inventoryStockTransaction'}}})
  .state('inventory.using_item',{url:'/pemakaian_item',data:{label:'Item Usage'},views:{'@':{templateUrl:'view/inventory/using_item/index.html',controller:'inventoryUsingItem'}}})
  .state('inventory.using_item.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/using_item/create.html',controller:'inventoryUsingItemCreate'}}})
  .state('inventory.using_item.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/using_item/show.html',controller:'inventoryUsingItemShow'}}})
  .state('inventory.using_item.edit',{url:'/edit/:id',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/using_item/create.html',controller:'inventoryUsingItemCreate'}}})
  .state('inventory.retur',{url:'/retur',data:{label:'Retur Barang'},views:{'@':{templateUrl:'view/inventory/retur/index.html',controller:'inventoryRetur'}}})
  .state('inventory.retur.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/inventory/retur/create.html',controller:'inventoryReturCreate'}}})
  .state('inventory.retur.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/inventory/retur/show.html',controller:'inventoryReturShow'}}})
  .state('inventory.retur.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/inventory/retur/create.html',controller:'inventoryReturCreate'}}})
  .state('inventory.retur.receive',{url:'/:id/receive',data:{label:'Add Penerimaan'},views:{'@':{templateUrl:'view/inventory/retur/receive.html',controller:'inventoryReturReceive'}}})

  .state('inventory.report',{url:'/laporan',data:{label:'Laporan Inventory'},views:{'@':{templateUrl:'view/inventory/report/index.html',controller:'inventoryReport'}}})

  //DEPO
  .state('depo',{url:'/depo',data:{label:'Depo'},views:{'':{templateUrl:'view/operational/head.html',controller:'operational'}}})
  .state('depo.operator',{url:'/operator',data:{label:'Operator'},views:{'@':{templateUrl:'view/depo/operator/index.html',controller:'depoOperator'}}})
  .state('depo.operator.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/depo/operator/create.html',controller:'depoOperatorCreate'}}})

  .state('depo.job_order',{url:'/job_order',data:{label:'Job Order'},views:{'@':{templateUrl:'view/depo/job_order/index.html',controller:'depoJobOrder'}}})

  .state('depo.container_part',{url:'/container_part',data:{label:'Container Part'},views:{'@':{templateUrl:'view/depo/container_part/index.html',controller:'depoContainerPart'}}})
  .state('depo.container_part.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/depo/container_part/create.html',controller:'depoContainerPartCreate'}}})


  .state('depo.container_yard',{url:'/container_yard',data:{label:'Container Yard'},views:{'@':{templateUrl:'view/depo/container_yard/index.html',controller:'depoContainerYard'}}})
  .state('depo.container_yard.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/depo/container_yard/create.html',controller:'depoContainerYardCreate'}}})


  //OPERASIONAL
  .state('operational',{url:'/operational',data:{label:'Operasional'},views:{'':{templateUrl:'view/operational/head.html',controller:'operational'}}})
  .state('operational.work_order',{url:'/work_order',data:{label:'Work Order'},views:{'@':{templateUrl:'view/operational/work_order/index.html',controller:'operasionalWO'}}})
  .state('operational.work_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/work_order/show.html',controller:'operasionalWOShow'}}})
  .state('operational.work_order.show.detail',{url:'/detail',data:{label:'Detail Work Order'},views:{'':{templateUrl:'view/operational/work_order/detail/detail.html',controller:'operasionalWOShowDetail'}}})
  .state('operational.work_order.show.job_order',{url:'/job_order',data:{label:'Detail Job Order'},views:{'':{templateUrl:'view/operational/work_order/detail/job_order.html',controller:'operasionalWOShowJobOrder'}}})

  .state('operational.voyage_schedule',{url:'/jadwal_kapal',data:{label:'Voyage Schedules'},views:{'@':{templateUrl:'view/operational/voyage_schedule/index.html',controller:'operationalVoyageSchedule'}}})
  .state('operational.voyage_schedule.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/voyage_schedule/create.html',controller:'operationalVoyageScheduleCreate'}}})
  .state('operational.voyage_schedule.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/voyage_schedule/show.html',controller:'operationalVoyageScheduleShow'}}})
  .state('operational.voyage_schedule.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational/voyage_schedule/create.html',controller:'operationalVoyageScheduleEdit'}}})
  .state('operational.voyage_schedule.create_receipt',{url:'/:id/receipt',data:{label:'Create Receipt'},views:{'@':{templateUrl:'view/operational/voyage_schedule/create_receipt.html',controller:'operationalVoyageScheduleReceipt'}}})
  .state('operational.container',{url:'/container',data:{label:'Container'},views:{'@':{templateUrl:'view/operational/container/index.html',controller:'operationalContainer'}}})
  .state('operational.container.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/container/create.html',controller:'operationalContainerCreate'}}})
  .state('operational.container.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/container/show.html',controller:'operationalContainerShow'}}})
  .state('operational.container.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational/container/create.html',controller:'operationalContainerEdit'}}})
  .state('operational.job_order',{url:'/job_order',data:{label:'Job Order'},views:{'@':{templateUrl:'view/operational/job_order/index.html',controller:'operationalJobOrder'}}})
  .state('operational.job_order_invoice',{url:'/job_order_invoice',data:{label:'Job Order Invoice'},views:{'@':{templateUrl:'view/operational/job_order/index_invoice.html',controller:'operationalJobOrderInvoice'}}})
  .state('operational.job_order.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/job_order/create.html',controller:'operationalJobOrderCreate'}}})
  .state('operational.job_order.create.work_order',{url:'/work_order/:work_order_detail_id',data:{label:'Work Order'},views:{'@':{templateUrl:'view/operational/job_order/create.html',controller:'operationalJobOrderCreate'}}})
  .state('operational.job_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/job_order/show.html',controller:'operationalJobOrderShow'}}})
  .state('operational.job_order.show.create_container',{url:'/container/create',data:{label:'Create Container'},views:{'@':{templateUrl:'view/operational/job_order/detail/create_container.html',controller:'operationalJobOrderCreateContainer'}}})
  .state('operational.job_order.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational/job_order/edit.html',controller:'operationalJobOrderEdit'}}})
  .state('operational.job_order.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational/job_order/detail/detail.html',controller:'operationalJobOrderShowDetail'}}})
  .state('operational.job_order.show.summary',{url:'/summary',data:{label:'Summary'},views:{'':{templateUrl:'view/operational/job_order/detail/summary.html',controller:'operationalJobOrderShowSummary'}}})
  .state('operational.job_order.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/operational/job_order/detail/document.html',controller:'operationalJobOrderShowDocument'}}})
  .state('operational.job_order.show.proses',{url:'/proses',data:{label:'Proses'},views:{'':{templateUrl:'view/operational/job_order/detail/proses.html',controller:'operationalJobOrderShowProses'}}})
  .state('operational.job_order.show.set_voyage',{url:'/set_kapal/:manifest',data:{label:'Set Kapal'},views:{'@':{templateUrl:'view/operational/job_order/vessel.html',controller:'operationalJobOrderShowVoyage'}}})
  .state('operational.job_order_archive',{url:'/job_order_archive',data:{label:'Arsip Job Order'},views:{'@':{templateUrl:'view/operational/archive_jo/index.html',controller:'operationalJobOrderArchive'}}})
  .state('operational.vendor_job',{url:'/vendor_job',data:{label:'Job Order'},views:{'@':{templateUrl:'view/operational/vendor_job/index.html',controller:'operationalVendorJob'}}})
  .state('operational.vendor_job.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/vendor_job/show.html',controller:'operationalVendorJobShow'}}})
  .state('operational.vendor_job.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational/vendor_job/detail/detail.html',controller:'operationalVendorJobShowDetail'}}})
  .state('operational.manifest_ftl',{url:'/manifest_ftl',data:{label:'Packing List FTL / LTL'},views:{'@':{templateUrl:'view/operational/manifest_ftl/index.html',controller:'operationalManifestFTL'}}})
  .state('operational.manifest_ftl.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/manifest_ftl/create.html',controller:'operationalManifestFTLCreate'}}})
  .state('operational.manifest_ftl.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/manifest_ftl/show.html',controller:'operationalManifestFTLShow'}}})
  .state('operational.manifest_ftl.create_delivery',{url:'/:id/set_kendaraan',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/manifest_ftl/create_pickup.html',controller:'operationalManifestFTLPickup'}}})
  .state('operational.manifest_fcl',{url:'/manifest_fcl',data:{label:'Packing List FCL / LCL'},views:{'@':{templateUrl:'view/operational/manifest_fcl/index.html',controller:'operationalManifestFCL'}}})
  .state('operational.manifest_fcl.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/manifest_fcl/show.html',controller:'operationalManifestFCLShow'}}})
  .state('operational.manifest_fcl.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/manifest_fcl/create.html',controller:'operationalManifestFCLCreate'}}})
  .state('operational.manifest_fcl.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational/manifest_fcl/create.html',controller:'operationalManifestFCLEdit'}}})
  .state('operational.manifest_fcl.change_vessel',{url:'/:id/set_kapal',data:{label:'Set Kapal'},views:{'@':{templateUrl:'view/operational/manifest_fcl/change_vessel.html',controller:'operationalManifestFCLChangeVessel'}}})

  .state('operational.shipment_status',{url:'/shipment_status',data:{label:'Shipment Status'},views:{'@':{templateUrl:'view/operational/shipment_status/index.html',controller:'operationalShipmentStatus'}}})
  .state('operational.shipment_status.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/shipment_status/show.html',controller:'operationalShipmentStatusShow'}}})

  .state('operational.delivery_order_driver',{url:'/surat_jalan_driver',data:{label:'Surat Jalan Driver'},views:{'@':{templateUrl:'view/operational/delivery_order_driver/index.html',controller:'operationalJODriver'}}})
  .state('operational.delivery_order_driver.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/delivery_order_driver/show.html',controller:'operationalJODriverShow'}}})
  .state('operational.invoice_jual',{url:'/invoice_jual',data:{label:'Invoice Jual'},views:{'@':{templateUrl:'view/operational/invoice_jual/index.html',controller:'operationalInvoiceJual'}}})
  .state('operational.invoice_jual.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/invoice_jual/create.html',controller:'operationalInvoiceJualCreate'}}})
  .state('operational.invoice_jual.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational/invoice_jual/create.html',controller:'operationalInvoiceJualCreate'}}})
  .state('operational.invoice_jual.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/invoice_jual/show.html',controller:'operationalInvoiceJualShow'}}})
  .state('operational.invoice_vendor',{url:'/invoice_vendor',data:{label:'Tagihan Vendor'},views:{'@':{templateUrl:'view/operational/invoice_vendor/index.html',controller:'operationalInvoiceVendor'}}})
  .state('operational.invoice_vendor.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/invoice_vendor/create.html',controller:'operationalInvoiceVendorCreate'}}})
  .state('operational.invoice_vendor.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/invoice_vendor/show.html',controller:'operationalInvoiceVendorShow'}}})
  .state('operational.progress',{url:'/progress_operasional',data:{label:'Progress Operasional'},views:{'@':{templateUrl:'view/operational/progress/index.html',controller:'operationalProgress'}}})
  .state('operational.progress.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/progress/create.html',controller:'operationalProgressCreate'}}})

  .state('operational.klaim',{url:'/klaim',data:{label:'Klaim'},views:{'@':{templateUrl:'view/operational/Klaim/index.html',controller:'Klaim'}}})
  .state('operational.klaim.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational/Klaim/create.html',controller:'KlaimCreate'}}})
  .state('operational.klaim.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational/Klaim/show.html',controller:'KlaimShow'}}})
  .state('operational.report',{url:'/laporan',data:{label:'Laporan'},views:{'@':{templateUrl:'view/operational/report/index.html',controller:'operationalReport'}}})
  .state('operational.print_shipment',{url:'/shipping_instruction',data:{label:'Laporan Shipping Instruction'},views:{'@':{templateUrl:'view/operational/print_shipment/index.html',controller:'operationalShippingInstruction'}}})

  //OPERATIONAL GUDANG
  .state('operational_warehouse',{url:'/warehousing',data:{label:'Inventory'},views:{'':{templateUrl:'view/operational_warehouse/head.html',controller:'opWarehouse'}}})
  .state('operational_warehouse.setting',{url:'/setting',data:{label:'Setting'},views:{'@':{templateUrl:'view/operational_warehouse/setting/head.html',controller:'opWarehouseSetting'}}})
  .state('operational_warehouse.setting.warehouse',{url:'/warehouse',data:{label:'Warehouse List'},views:{'':{templateUrl:'view/operational_warehouse/setting/warehouse.html',controller:'opWarehouseSettingWarehouse'}}})
  .state('operational_warehouse.setting.warehouse.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/setting/warehouse_show.html',controller:'opWarehouseSettingWarehouseShow'}}})
  .state('operational_warehouse.setting.warehouse.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/operational_warehouse/setting/warehouse/create.html',controller:'opWarehouseSettingWarehouseCreate'}}})
  .state('operational_warehouse.setting.warehouse.edit',{url:'/:id/edit',data:{label:'Create'},views:{'@':{templateUrl:'view/operational_warehouse/setting/warehouse/create.html',controller:'opWarehouseSettingWarehouseCreate'}}})
  .state('operational_warehouse.setting.rack',{url:'/bin_location',data:{label:'Bin Location'},views:{'':{templateUrl:'view/operational_warehouse/setting/rack.html',controller:'opWarehouseSettingRack'}}})
  .state('operational_warehouse.setting.pallet',{url:'/kategori_pallet',data:{label:'Kategori Pallet'},views:{'':{templateUrl:'view/operational_warehouse/setting/pallet.html',controller:'opWarehouseSettingPallet'}}})
  .state('operational_warehouse.setting.storage',{url:'/type_storage',data:{label:'Type Storage'},views:{'':{templateUrl:'view/operational_warehouse/setting/storage_type.html',controller:'opWarehouseSettingStorage'}}})

  .state('operational_warehouse.bin_location',{url:'/bin_location',data:{label:'Bin Location'},views:{'@':{templateUrl:'view/operational_warehouse/bin_location/index.html',controller:'opWarehouseSettingRack'}}})
  .state('operational_warehouse.bin_location.show',{url:'/:id',data:{label:'Bin Location Detail'},views:{'@':{templateUrl:'view/operational_warehouse/bin_location/show.html',controller:'opWarehouseSettingRackShow'}}})
  .state('operational_warehouse.bin_location.show.qrcode',{url:'/qrcode',data:{label:'Bin Location QR Code'},views:{'':{templateUrl:'view/operational_warehouse/bin_location/detail/qrcode.html',controller:'opWarehouseSettingRackShowQRCode'}}})

  .state('operational_warehouse.receipt',{url:'/penerimaan',data:{label:'Good Receipt'},views:{'@':{templateUrl:'view/operational_warehouse/receipt/index.html',controller:'opWarehouseReceipt'}}})
  .state('operational_warehouse.receipt.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/receipt/create.html',controller:'opWarehouseReceiptCreate'}}})
  .state('operational_warehouse.receipt.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/receipt/show.html',controller:'opWarehouseReceiptShow'}}})
  .state('operational_warehouse.receipt.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/receipt/edit.html',controller:'opWarehouseReceiptEdit'}}})

  .state('operational_warehouse.handling',{url:'/handling',data:{label:'Handling'},views:{'@':{templateUrl:'view/operational_warehouse/handling/index.html',controller:'opWarehouseHandling'}}})
  .state('operational_warehouse.handling.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/handling/create.html',controller:'opWarehouseHandlingCreate'}}})
  .state('operational_warehouse.handling.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/handling/show.html',controller:'opWarehouseHandlingShow'}}})
  .state('operational_warehouse.handling.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/handling/edit.html',controller:'opWarehouseHandlingEdit'}}})
  .state('operational_warehouse.handling.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational_warehouse/handling/detail/detail.html',controller:'opWarehouseHandlingShowDetail'}}})
  .state('operational_warehouse.handling.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/operational_warehouse/handling/detail/document.html',controller:'opWarehouseHandlingShowDocument'}}})
  .state('operational_warehouse.handling.show.proses',{url:'/proses',data:{label:'Proses'},views:{'':{templateUrl:'view/operational_warehouse/handling/detail/proses.html',controller:'opWarehouseHandlingShowProses'}}})

  .state('operational_warehouse.stuffing',{url:'/stuffing',data:{label:'Stuffing'},views:{'@':{templateUrl:'view/operational_warehouse/stuffing/index.html',controller:'opWarehouseStuffing'}}})
  .state('operational_warehouse.stuffing.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/stuffing/create.html',controller:'opWarehouseStuffingCreate'}}})
  .state('operational_warehouse.stuffing.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/stuffing/show.html',controller:'opWarehouseStuffingShow'}}})
  .state('operational_warehouse.stuffing.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/stuffing/edit.html',controller:'opWarehouseStuffingEdit'}}})
  .state('operational_warehouse.stuffing.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational_warehouse/stuffing/detail/detail.html',controller:'opWarehouseStuffingShowDetail'}}})
  .state('operational_warehouse.stuffing.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/operational_warehouse/stuffing/detail/document.html',controller:'opWarehouseStuffingShowDocument'}}})
  .state('operational_warehouse.stuffing.show.proses',{url:'/proses',data:{label:'Proses'},views:{'':{templateUrl:'view/operational_warehouse/stuffing/detail/proses.html',controller:'opWarehouseStuffingShowProses'}}})

  .state('operational_warehouse.packaging',{url:'/packaging',data:{label:'Packaging'},views:{'@':{templateUrl:'view/operational_warehouse/packaging/index.html',controller:'opWarehousePackaging'}}})
  .state('operational_warehouse.packaging.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/packaging/create.html',controller:'opWarehousePackagingCreate'}}})
  .state('operational_warehouse.packaging.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/packaging/show.html',controller:'opWarehousePackagingShow'}}})
  .state('operational_warehouse.packaging.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/packaging/create.html',controller:'opWarehousePackagingCreate'}}})
  .state('operational_warehouse.packaging.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational_warehouse/packaging/detail/detail.html',controller:'opWarehousePackagingShowDetail'}}})
  .state('operational_warehouse.packaging.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/operational_warehouse/packaging/detail/document.html',controller:'opWarehousePackagingShowDocument'}}})
  .state('operational_warehouse.packaging.show.proses',{url:'/proses',data:{label:'Proses'},views:{'':{templateUrl:'view/operational_warehouse/packaging/detail/proses.html',controller:'opWarehousePackagingShowProses'}}})


  .state('operational_warehouse.warehouse_rent',{url:'/warehouse_rent',data:{label:'Warehouse Rent'},views:{'@':{templateUrl:'view/operational_warehouse/warehouse_rent/index.html',controller:'opWarehouseWarehouseRent'}}})
  .state('operational_warehouse.warehouse_rent.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/warehouse_rent/create.html',controller:'opWarehouseWarehouseRentCreate'}}})
  .state('operational_warehouse.warehouse_rent.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/warehouse_rent/show.html',controller:'opWarehouseWarehouseRentShow'}}})
  .state('operational_warehouse.warehouse_rent.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/warehouse_rent/edit.html',controller:'opWarehouseWarehouseRentEdit'}}})
  .state('operational_warehouse.warehouse_rent.show.detail',{url:'/detail',data:{label:'Info'},views:{'':{templateUrl:'view/operational_warehouse/warehouse_rent/detail/detail.html',controller:'opWarehouseWarehouseRentShowDetail'}}})
  .state('operational_warehouse.warehouse_rent.show.document',{url:'/berkas',data:{label:'Berkas'},views:{'':{templateUrl:'view/operational_warehouse/warehouse_rent/detail/document.html',controller:'opWarehouseWarehouseRentShowDocument'}}})
  .state('operational_warehouse.warehouse_rent.show.proses',{url:'/proses',data:{label:'Proses'},views:{'':{templateUrl:'view/operational_warehouse/warehouse_rent/detail/proses.html',controller:'opWarehouseWarehouseRentShowProses'}}})

  .state('operational_warehouse.receipt_report',{url:'/laporan_pergerakan',data:{label:'Laporan Pergerakan Barang'},views:{'@':{templateUrl:'view/operational_warehouse/receipt_report/index.html',controller:'opWarehouseItemReceiptReport'}}})
  .state('operational_warehouse.receipt_report.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/receipt_report/show.html',controller:'opWarehouseItemReceiptReportShow'}}})

  .state('operational_warehouse.job_order',{url:'/job_order',data:{label:'Job Order'},views:{'@':{templateUrl:'view/operational_warehouse/job_order/index.html',controller:'opWarehouseJO'}}})
  .state('operational_warehouse.job_order.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/job_order/create.html',controller:'opWarehouseJOCreate'}}})
  .state('operational_warehouse.master_pallet',{url:'/master_pallet',data:{label:'Master Pallet'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/master/index.html',controller:'opWarehousePalletMaster'}}})
  .state('operational_warehouse.master_pallet.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/master/create.html',controller:'opWarehousePalletMaster'}}})
  .state('operational_warehouse.pallet_stock',{url:'/pallet_stock',data:{label:'Stock Pallet'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/list/index.html',controller:'opWarehousePalletStock'}}})
  .state('operational_warehouse.pallet_purchase_request',{url:'/permintaan_pembelian_pallet',data:{label:'Purchase Request in Pallet'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_request/index.html',controller:'opWarehousePalletPurchaseRequest'}}})
  .state('operational_warehouse.pallet_purchase_request.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_request/create.html',controller:'opWarehousePalletPurchaseRequestCreate'}}})
  .state('operational_warehouse.pallet_purchase_request.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_request/show.html',controller:'opWarehousePalletPurchaseRequestShow'}}})
  .state('operational_warehouse.pallet_purchase_order',{url:'/purchase_order_pallet',data:{label:'Purchase Order Pallet'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_order/index.html',controller:'opWarehousePalletPurchaseOrder'}}})
  .state('operational_warehouse.pallet_purchase_order.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_order/create.html',controller:'opWarehousePalletPurchaseOrder'}}})
  .state('operational_warehouse.pallet_purchase_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_order/show.html',controller:'opWarehousePalletPurchaseOrderShow'}}})
  .state('operational_warehouse.pallet_purchase_order.edit',{url:'/:id/edit',data:{label:'Create'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/purchase_order/create.html',controller:'opWarehousePalletPurchaseOrder'}}})
  .state('operational_warehouse.pallet_receipt',{url:'/receipt_pallet',data:{label:'Penerimaan Pallet'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/receipt/index.html',controller:'opWarehousePalletReceipt'}}})
  .state('operational_warehouse.pallet_receipt.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/receipt/show.html',controller:'opWarehousePalletReceiptShow'}}})
  .state('operational_warehouse.pallet_receipt.create_po',{url:'/create/:po_id/po',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/receipt/create.html',controller:'opWarehousePalletReceiptCreatePo'}}})
  .state('operational_warehouse.pallet_receipt.create',{url:'/create',params:{id:null,type:null},data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/receipt/create_new.html',controller:'opWarehousePalletReceiptCreate'}}})
  .state('operational_warehouse.pallet_using',{url:'/using_pallet',data:{label:'Pallet Usage'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/using/index.html',controller:'opWarehousePalletUsing'}}})
  .state('operational_warehouse.pallet_using.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/using/create.html',controller:'opWarehousePalletUsingCreate'}}})
  .state('operational_warehouse.pallet_using.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/using/show.html',controller:'opWarehousePalletUsingShow'}}})
  .state('operational_warehouse.pallet_using.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/using/create.html',controller:'opWarehousePalletUsingCreate'}}})
  .state('operational_warehouse.pallet_po_return',{url:'/pallet_po_return',data:{label:'Purchase Order Return'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/po_return/index.html',controller:'opWarehousePalletPOReturn'}}})
  .state('operational_warehouse.pallet_po_return.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/po_return/create.html',controller:'opWarehousePalletPOReturnCreate'}}})
  .state('operational_warehouse.pallet_po_return.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/po_return/show.html',controller:'opWarehousePalletPOReturnShow'}}})
  .state('operational_warehouse.pallet_po_return.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/po_return/create.html',controller:'opWarehousePalletPOReturnCreate'}}})
  .state('operational_warehouse.pallet_sales_order',{url:'/pallet_sales_order',data:{label:'Sales Order'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order/index.html',controller:'opWarehousePalletSalesOrder'}}})
  .state('operational_warehouse.pallet_sales_order.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order/create.html',controller:'opWarehousePalletSalesOrderCreate'}}})
  .state('operational_warehouse.pallet_sales_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order/show.html',controller:'opWarehousePalletSalesOrderShow'}}})
  .state('operational_warehouse.pallet_sales_order_return',{url:'/pallet_sales_order_return',data:{label:'Sales Order Return'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order_return/index.html',controller:'opWarehousePalletSalesOrderReturn'}}})
  .state('operational_warehouse.pallet_sales_order_return.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order_return/create.html',controller:'opWarehousePalletSalesOrderReturnCreate'}}})
  .state('operational_warehouse.pallet_sales_order_return.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order_return/show.html',controller:'opWarehousePalletSalesOrderReturnShow'}}})
  .state('operational_warehouse.pallet_sales_order_return.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order_return/create.html',controller:'opWarehousePalletSalesOrderReturnCreate'}}})
  .state('operational_warehouse.pallet_sales_order_return.create_receipt',{url:'/:id/receipt',data:{label:'Create Receipt'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/sales_order_return/create_receipt.html',controller:'opWarehousePalletSalesOrderReturnCreateReceipt'}}})
  .state('operational_warehouse.pallet_migration',{url:'/pallet_migration',data:{label:'Migration'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_migration/index.html',controller:'opWarehousePalletMigration'}}})
  .state('operational_warehouse.pallet_migration.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_migration/create.html',controller:'opWarehousePalletMigrationCreate'}}})
  .state('operational_warehouse.pallet_migration.create_receipt',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_migration/create.html',controller:'opWarehousePalletMigrationCreateReceipt'}}})
  .state('operational_warehouse.pallet_migration.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_migration/show.html',controller:'opWarehousePalletMigrationShow'}}})
  .state('operational_warehouse.pallet_deletion',{url:'/pallet_deletion',data:{label:'Deletion'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_deletion/index.html',controller:'opWarehousePalletDeletion'}}})
  .state('operational_warehouse.pallet_deletion.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_deletion/create.html',controller:'opWarehousePalletDeletionCreate'}}})
  .state('operational_warehouse.pallet_deletion.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_deletion/show.html',controller:'opWarehousePalletDeletionShow'}}})
  .state('operational_warehouse.pallet_deletion.edit',{url:'/:id/edit',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/pallet/pallet_deletion/create.html',controller:'opWarehousePalletDeletionCreate'}}})

  .state('operational_warehouse.stocklist',{url:'/stocklist',data:{label:'Stock List'},views:{'@':{templateUrl:'view/operational_warehouse/stocklist/index.html',controller:'opWarehouseStockList'}}})
  .state('operational_warehouse.stocklist.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/stocklist/show.html',controller:'opWarehouseStockListShow'}}})

  .state('operational_warehouse.mutasi_transfer',{url:'/mutasi_transfer',data:{label:'Transfer Mutation'},views:{'@':{templateUrl:'view/operational_warehouse/mutasi_transfer/index.html',controller:'opWarehouseMutasiTransfer'}}})
  .state('operational_warehouse.mutasi_transfer.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/mutasi_transfer/create.html',controller:'opWarehouseMutasiTransferCreate'}}})
  .state('operational_warehouse.mutasi_transfer.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/mutasi_transfer/show.html',controller:'opWarehouseMutasiTransferShow'}}})
  .state('operational_warehouse.mutasi_transfer.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/mutasi_transfer/create.html',controller:'opWarehouseMutasiTransferCreate'}}})
  .state('operational_warehouse.mutasi_transfer.create_receipt',{url:'/:id/receipt',data:{label:'Create Receipt'},views:{'@':{templateUrl:'view/operational_warehouse/mutasi_transfer/create_receipt.html',controller:'opWarehouseMutasiTransferCreateReceipt'}}})

  .state('operational_warehouse.putaway',{url:'/putaway',data:{label:'Put Away'},views:{'@':{templateUrl:'view/operational_warehouse/putaway/index.html',controller:'opWarehousePutaway'}}})
  .state('operational_warehouse.putaway.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/putaway/create.html',controller:'opWarehousePutawayCreate'}}})
  .state('operational_warehouse.putaway.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/putaway/create.html',controller:'opWarehousePutawayCreate'}}})
  .state('operational_warehouse.putaway.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/putaway/show.html',controller:'opWarehousePutawayShow'}}})

  .state('operational_warehouse.picking',{url:'/picking',data:{label:'Picking'},views:{'@':{templateUrl:'view/operational_warehouse/picking/index.html',controller:'opWarehousePicking'}}})
  .state('operational_warehouse.picking.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/picking/create.html',controller:'opWarehousePickingCreate'}}})
  .state('operational_warehouse.picking.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/picking/show.html',controller:'opWarehousePickingShow'}}})
  .state('operational_warehouse.picking.edit',{url:'/edit/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/picking/create.html',controller:'opWarehousePickingCreate'}}})

  .state('operational_warehouse.stok_opname',{url:'/stok_opname',data:{label:'Stok Opname'},views:{'@':{templateUrl:'view/operational_warehouse/stok_opname/index.html',controller:'opWarehouseStokOpname'}}})
  .state('operational_warehouse.stok_opname.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/stok_opname/create.html',controller:'opWarehouseStokOpnameCreate'}}})
  .state('operational_warehouse.stok_opname.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/stok_opname/show.html',controller:'opWarehouseStokOpnameShow'}}})
  .state('operational_warehouse.stok_opname.edit',{url:'/edit/:id',data:{label:'Edit'},views:{'@':{templateUrl:'view/operational_warehouse/stok_opname/create.html',controller:'opWarehouseStokOpnameCreate'}}})

  .state('operational_warehouse.master_item',{url:'/master_item',data:{label:'Master Item'},views:{'@':{templateUrl:'view/operational_warehouse/item/index.html',controller:'opWarehouseItem'}}})
  .state('operational_warehouse.master_item.create',{url:'/create',data:{label:'Add'},views:{'@':{templateUrl:'view/operational_warehouse/item/create.html',controller:'opWarehouseItemCreate'}}})
  .state('operational_warehouse.master_item.edit',{url:'/edit/:id',data:{label:'Detail'},views:{'@':{templateUrl:'view/operational_warehouse/item/create.html',controller:'opWarehouseItemEdit'}}})

  .state('sales_order',{url:'/sales_order',data:{label:'Sales'},views:{'':{templateUrl:'view/operational_warehouse/head.html',controller:'opWarehouseSetting'}}})
  .state('sales_order.bill_payment',{url:'/invoice_payment',data:{label:'Penerimaan Pembayaran'},views:{'@':{templateUrl:'view/finance/bill_payment/index.html',controller:'financeBillPayment'}}})

  $urlRouterProvider.otherwise('/home');
});

app.factory('hardList', function() {
  var list={};
  list.send_type=[
  {id:1,name:"Sekali"},
  {id:2,name:"Per Hari"},
  {id:3,name:"Per Minggu"},
  {id:4,name:"Per Bulan"},
  {id:5,name:"Tidak Tentu"},
  ];
  list.bill_type=[
  {id:1,name:"Per Pengiriman"},
  {id:2,name:"Borongan"},
  ];

  return list;
});


app.filter('rupiah', function () {
  return function (val) {
    if (val!=null || !isNaN(val)) {
      while (/(\d+)(\d{3})/.test(val.toString())){
        val = val.toString().replace(/(\d+)(\d{3})/, '$1'+','+'$2');
    }
    var val = 'Rp ' + val;
    return val;
} else {
  return 'Rp. 0';
}
};
});

app.filter('fullDate', function() {
  return function(val) {
    if(!val) {
      return ''
  }
  var days = new Date(val);
    // var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nop','Des'];
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    result = ('0'+days.getDate()).slice(-2)+' '+months[days.getMonth()]+' '+days.getFullYear();
    if(!months[days.getMonth()]) {
        result = ''
    }
    return result
}
});
app.filter('aTime', function() {
  return function(val) {
    var days = new Date(val);
    return ('0'+days.getHours()).slice(-2)+':'+('0'+days.getMinutes()).slice(-2);
}
});

app.filter('minDate', function() {
  return function(val) {
    if(!val) {
        return val
    }
    var days = new Date(val);
    return days.getDate()+'-'+(days.getMonth()+1)+'-'+days.getFullYear();
}
});

app.filter('fullDateTime', function() {
  return function(val) {
    if(val) {
        var days = new Date(val);
        // var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nop','Des'];
        var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return ('0'+days.getDate()).slice(-2)+' '+months[days.getMonth()]+' '+days.getFullYear()+' '+('0'+days.getHours()).slice(-2)+':'+('0'+days.getMinutes()).slice(-2);
    } else {
        return '';
    }
}
});

app.run(function($rootScope) {
  $rootScope.jStyle=function(json) {
    return JSON.stringify(json, undefined, 2);
}
});
app.directive('onlyNum', function($browser) {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, element, attrs, modelCtrl) {
        var keyCode = [8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110,190];
        $(element).addClass('text-right')
        element.bind("keydown", function(event) {
            if (modelCtrl.$modelValue) {
                  // var hitungTitik=(modelCtrl.$modelValue.match(/./g)||[]).length;
                  var modelVal=modelCtrl.$modelValue;
                  var hitungTitik=modelVal.split('.').length-1;
                  var slength=modelVal.length;
                  // console.log(slength);
                  if (hitungTitik>0) {
                    keyCode=[8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110];
                } else {
                    keyCode=[8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110,190];
                }
            }
            if($.inArray(event.which,keyCode) == -1) {
                scope.$apply(function(){
                    scope.$eval(attrs.onlyNum);
                    event.preventDefault();
                });
                event.preventDefault();
            }
        });
    }
}
});
