app.controller('opWarehousePicking', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, pickingOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.picking_order.title;
    $scope.formData = {};

    oTable = $('#datatable').DataTable({
        processing: true,
        order : [[3, 'desc']],
        serverSide: true,
        ajax: {
          headers : {'Authorization' : 'Bearer '+authUser.api_token},
          url : baseUrl+'/api/operational_warehouse/picking_datatable',
          data : function(request) {
            request['company_id'] = $scope.formData.company_id;
            request['warehouse_id'] = $scope.formData.warehouse_id;
            request['status'] = $scope.formData.status;
            request['start_date'] = $scope.formData.start_date;
            request['end_date'] = $scope.formData.end_date;

            return request;
          }
        },
        dom: 'Blfrtip',
        buttons: [
          {
            'extend' : 'excel',
            'enabled' : true,
            'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
            'className' : 'btn btn-default btn-sm',
            'filename' : 'Picking - '+new Date(),
            'sheetName' : 'Data',
            'title' : 'Picking'
          },
        ],

        columns:[
          {data:"code",name:"code"},
          {data:"company.name",name:"company.name"},
          {data:"warehouse.name",name:"warehouse.name"},
          {
              data:null,
              name: "date_transaction",
              searchable:false,
              render : resp => $filter('fullDate')(resp.date_transaction)
          },
          {data:"status",name:"status", className : 'text-center'},
          {data:"action",name:"action", className : 'text-center'}
          
        ],
        createdRow: function(row, data, dataIndex) {
          $(row).find('td').attr('ui-sref', 'operational_warehouse.picking.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
          $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo( '#export_button' );
    $compile($('thead'))($scope)

    $scope.searchData = function() {
        oTable.ajax.reload();
    }

    $scope.resetFilter = function() {
        $scope.formData = {};
        oTable.ajax.reload();
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            pickingOrdersService.api.destroy(ids, function(){
                oTable.ajax.reload();
            })
        }
    }
});
app.controller('opWarehousePickingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, pickingOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
    $scope.disBtn = false;
    $scope.formData={}
    $scope.formData.date_transaction=dateNow
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
    $scope.formData.detail = {};
    $scope.warehouses = [];
    $scope.racks = [];

  
  $compile($('thead'))($scope)

  $scope.$on('getWarehouse', function(e, v){
      if(!$scope.formData.company_id) {
          $scope.formData.company_id = parseInt(v.company_id)
      }
  })

  $scope.showDetail = function() {
      if($stateParams.id) {
           pickingOrdersService.api.showDetail($stateParams.id, function(dt){
              $scope.detail = dt
           })
      }
  }

  $scope.show = function() {
      if($stateParams.id) {
           pickingOrdersService.api.show($stateParams.id, function(dt){
              dt.date_transaction = $filter('minDate')(dt.date_transaction)
              $scope.formData = dt
              $scope.formData.detail = {}
              $scope.showDetail()
           })
      }
  }
  $scope.show()

  $scope.is_allow_insert = function() {
    if(!$scope.detailData.item_id || $scope.detailData.stock==0 || parseInt($scope.detailData.qty) > parseInt($scope.detailData.stock)) {
      return true;
    }

    return false;
  }

    $scope.deletes = function(id) {
        $scope.detail = $scope.detail.filter(x => x.id != id) 
    }

  
  $scope.counter=0
  $scope.detail = []

  $scope.appendItemWarehouse = function(v) {
      $scope.formData.detail.code = v.code
      $scope.formData.detail.item_id = v.id
      $scope.formData.detail.name = v.name
      $scope.formData.detail.rack_id = v.rack_id
      $scope.formData.detail.warehouse_receipt_id = v.warehouse_receipt_id
      $scope.formData.detail.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
      $scope.formData.detail.warehouse_receipt_code = v.warehouse_receipt_code
      $scope.formData.detail.rack_code = v.rack_code
      $scope.appendTable()
  }

  $scope.$on('getItemWarehouse', function(e, v){
      $scope.appendItemWarehouse(v)
  })

  $scope.$on('getItemWarehouses', function(e, items){
      var i
      for(i in items) {
          $scope.appendItemWarehouse(items[i])
      }
  })

  $scope.appendTable=function() {
    disabledAppendBtn = true;
    $scope.formData.detail.id = Math.round(Math.random() * 999999999)
    $scope.detail.push($scope.formData.detail)
    $scope.formData.detail = {};
    disabledAppendBtn = false;
    $scope.isItemExists = true;
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
  }

  $scope.deleteAppend=function(id) {
    $scope.hitungAppend()
    console.log('Item length : ' + $scope.formData.detail.length)
    if($scope.formData.detail.length == 0) {
      $scope.isItemExists = false;
    }
  }

    $scope.back = function() {
        $state.go('operational_warehouse.picking')
    }

  $scope.submitForm=function() {
    var method = 'post'
    var url = pickingOrdersService.url.store()
    if($stateParams.id) {
        method = 'put'
        url = pickingOrdersService.url.update($stateParams.id)        
    }
    $scope.disBtn=true;
    var item_detail = $scope.detail;
    $scope.formData.detail_item = item_detail;

    $http[method](url,$scope.formData).then(function(data) {
      $scope.back()
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
});
app.controller('opWarehousePickingShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, pickingOrdersService) {
  $rootScope.pageTitle="Detail Picking";
  $scope.showDetail = function() {
      if($stateParams.id) {
           pickingOrdersService.api.showDetail($stateParams.id, function(dt){
              $scope.detail = dt
           })
      }
  }
  $scope.showDetail()

  $scope.show = function() {
      if($stateParams.id) {
           pickingOrdersService.api.show($stateParams.id, function(dt){
              $scope.item = dt
           })
      }
  }
  $scope.show()

  $scope.validateStock = function() {
    if(parseInt($scope.editData.qty) > parseInt($scope.editData.stock)) {
      $('#submitItem').attr('disabled', 'disabled');
    }
    else {
      $('#submitItem').removeAttr('disabled');

    }
  }

  $scope.status=[
    {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-primary">Disetujui</span>'},
  ]

  $scope.editDetail=function(json) {
    console.log(json)
    $('#editModal').modal()
    $scope.editData={}
    $scope.editData.id=json.id
    $scope.editData.qty=parseInt(json.qty)
    $scope.editData.stock=parseInt(json.stock)
    $scope.validateStock();
  }

  $scope.approve=function() {
    var cofs=confirm("Apakah anda yakin ? transaksi ini akan disetujui")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.put(pickingOrdersService.url.approve($stateParams.id)).then(function(data) {
      // $('#revisiModal').modal('hide');
      $scope.show()
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
  $scope.item_in=function() {
    var cofs=confirm("Apakah anda yakin ? barang akan dimasukkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/picking/item_in/'+$stateParams.id).then(function(data) {
      // $('#revisiModal').modal('hide');
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
})
app.controller('opWarehousePickingEdit', function($scope, $http, $filter, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Picking";
  $scope.disBtn = false;
  $scope.formData={}
  $scope.formData.detail=[]
  $scope.detailData={}
  $scope.detailData.qty=1
  $scope.detailData.stock=0
  $scope.formData.detail = {};
  $scope.warehouses = [];
  $scope.racks = [];


  $scope.getRack = function() {

    var request = {
      'warehouse_id' : $scope.formData.warehouse_id,
      'warehouse_receipt_id' : $scope.formData.detail.warehouse_receipt_id
    }
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/rack?' + request ).then(function(data) {
      $scope.racks=data.data.rack;
    });
  }

  $http.get(baseUrl+'/operational_warehouse/picking/create').then(function(data){
    $scope.data=data.data;
    $http.get(baseUrl+'/operational_warehouse/picking/'+$stateParams.id).then(function(data){
    $scope.formData=data.data.item
    $scope.formData.date_transaction = $filter('date')($scope.formData.date_transaction);
    $scope.formData.customer_id = parseInt($scope.formData.customer_id);
    $scope.formData.company_id = $scope.formData.company.id;
    $scope.formData.warehouse_id = $scope.formData.warehouse.id;
    $scope.detail=data.data.detail;
    $scope.detail=data.data.detail;
    $scope.switchGudang();
    $scope.switchRack();
    
    console.log('customer : ' + $scope.formData.customer_id)
    // append table picking edit
    appendTable = $('#appendTable').DataTable({
      data : $scope.detail, 
      columns : [
        { 
          'data' : null,
          render : function(resp) {
            if(resp.item_code) {
              return resp.item_code;
            }
            else {
              return resp.item.code
            }
          }
        },
        { 
          'data' : null,
          render : function(resp) {
            if(resp.item_name) {
              return resp.item_name;
            }
            else {
              return resp.item.name
            }
          }
        },
        { 
            'data' : null,
            render : function(resp) {
                var warehouse_receipt = $scope.data.warehouse_receipt.find(x => x.id == resp.warehouse_receipt_id)
                return warehouse_receipt.code
            }
        },
        { 'data' : 'rack.code'},
        { 'data' : 'qty', className : 'text-right'},
        {
          'data' : null,
          'render' : function(resp) {
              var html = "<a ng-click=\"deletes($event.currentTarget)\"><span class='fa fa-trash-o'></span></a>";
              return html;
          },
          'className' : 'text-center'
        }
      ],
      'rowCallback' : function(row) {
        $compile(angular.element(row).contents())($scope);
      }
    });
  },function(error){
    console.log(error)
  })

  },function(error){
    console.log(error)
  })

  

  $scope.status=[
    {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-primary">Disetujui</span>'},
  ]

  $scope.editDetail=function(json) {
    console.log(json)
    $('#editModal').modal()
    $scope.editData={}
    $scope.editData.id=json.id
    $scope.editData.qty=json.qty
  }

  $scope.deleteDetail=function(id) {
    var cofs=confirm("Apakah anda yakin ?")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/picking/delete_detail/'+id).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Data Berhasil Dihapus !");
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

  $scope.submitEdit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/picking/store_detail',$scope.editData).then(function(data) {
      $('#editModal').modal('hide');
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

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
      data: function(d) {
        d.warehouse_id = $scope.formData.warehouse_id
        d.customer_id = $scope.formData.customer_id
        d.warehouse_receipt_id = $scope.formData.detail.warehouse_receipt_id
        if($scope.formData.detail.rack) {
          
          d.rack_id = $scope.formData.detail.rack.id
        }
      }
    },
    columns:[
      {data:"action_choose",name:"created_at",className:"text-center"},
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



  $scope.is_allow_insert = function() {
    if(!$scope.detailData.item_id || $scope.detailData.stock==0 || parseInt($scope.detailData.qty) > parseInt($scope.detailData.stock)) {
      return true;
    }

    return false;
  }

  $scope.deletes = function(dom) {
    var tr = $(dom).parents('tr');
    appendTable.row(tr).remove().draw();

  }

  
  $scope.disabledAppend = function() {
    if(!$scope.formData.detail.qty) {
      $scope.disabledAppendBtn = true;
    }
    else {
      console.log('Qty : ' + $scope.formData.detail.qty);
      console.log('Stock : ' + $scope.formData.detail.stock);
      if(parseInt($scope.formData.detail.qty) > parseInt($scope.formData.detail.stock) ) {
        console.log('Greater');
        $scope.disabledAppendBtn = true; 
      }
      else {
        console.log('Lower');
        $scope.disabledAppendBtn = false; 
      }
      console.log('d : ' + $scope.disabledAppendBtn);
    }
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

    if (!$scope.formData.detail.warehouse_receipt_id) {
      toastr.error("Anda harus memilih No TTB terlebih dahulu!","Maaf!")
      return null;
    }
    $('#modalItem').modal()
    oTable.ajax.reload();
  }

  $scope.switchGudang = function() {
    var warehouses = [], unit;
    for(x in $scope.data.warehouse) {
      unit = $scope.data.warehouse[x];
      if(unit.company_id == $scope.formData.company_id) {
        warehouses.push(unit);
      }
    }

    $scope.warehouses = warehouses;
  }


  $scope.switchRack = function() {
    

    $scope.racks = [];
    $scope.getSuratJalan();
  }

  $scope.getRack = function() {

    var request = {
      'warehouse_id' : $scope.formData.warehouse_id,
      'warehouse_receipt_id' : $scope.formData.detail.warehouse_receipt_id
    }
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/rack?' + request ).then(function(data) {
      $scope.racks=data.data.rack;
    });
  }
  $scope.getSuratJalan = function() {
    var request = {
      'warehouse_id' : $scope.formData.warehouse_id,
      'customer_id' : $scope.formData.customer_id
    }
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/surat_jalan?' + request ).then(function(data) {
      $scope.data.warehouse_receipt=data.data.warehouse_receipt;
    });
  }

  $scope.choosePallet=function(json) {
    $('#modalItem').modal('hide')
    $scope.formData.detail.item_id=json.id
    $scope.formData.detail.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
    $scope.formData.detail.item_name=json.name+' ('+json.code+')'
    $scope.formData.detail.item_code=json.code
    $http.get(baseUrl+'/operational_warehouse/pallet_using/cek_stok',{params:{warehouse_id:$scope.formData.warehouse_id,item_id:json.id,rack_id:$scope.formData.detail.rack.id,warehouse_receipt_id:$scope.formData.detail.warehouse_receipt_id}}).then(function(data){
      if (data.data.qty != undefined) {
        $scope.formData.detail.stock=data.data.qty
      } else {
        $scope.formData.detail.stock=0
      }
    },function(error){
      console.log(error)
    })
  }


  $scope.counter=0
  $scope.detail = []
  $scope.appendTable=function() {
    disabledAppendBtn = true;
    $scope.detail.push($scope.formData.detail);
    appendTable.row.add( $scope.formData.detail ).draw();
    $scope.formData.detail = {};
    disabledAppendBtn = false;
    $scope.isItemExists = true;
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    $scope.detail.splice(id, 1)
    $scope.hitungAppend()
    console.log('Item length : ' + $scope.formData.detail.length)
    if($scope.formData.detail.length == 0) {
      $scope.isItemExists = false;
    }
  }

  $scope.submitForm=function() {
    $scope.disBtn=true;
    var item_detail = appendTable.data().toArray();
    $scope.formData.detail_item = item_detail;
    console.log($scope.formData.detail_item);
    $http.put(baseUrl+'/operational_warehouse/picking/' + $stateParams.id,$scope.formData).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.go('operational_warehouse.picking');
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
})
