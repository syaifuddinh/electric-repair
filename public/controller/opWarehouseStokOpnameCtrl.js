app.controller('opWarehouseStokOpname', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, stokOpnameService) {
  $rootScope.pageTitle="Stok Opname";
  $scope.formData = {};

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order : [[1, 'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : stokOpnameService.url.datatable(),
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
        'filename' : 'StokOpname - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'StokOpname'
      },
    ],

    columns:[
      {data:"code",name:"code"},
      {
        data:null,
        name:'stok_opname_warehouses.created_at',
        searchable : false,
        render : resp => $filter('fullDate')(resp.created_at)
      },
      {data:"company_name",name:"companies.name"},
      {data:"warehouse_name",name:"warehouses.name"},
      {data:"status_label",name:"status_label", className : 'text-center', orderable : false, searchable : false},
      {
        data:null,
        className : 'text-center', 
        orderable : false, 
        searchable : false,
        render : function(item) {
            var html = ''
            html += "<a ng-show=\"roleList.includes('inventory.stock.opname.detail')\" ui-sref='operational_warehouse.stok_opname.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail'></span></a>&nbsp;&nbsp;";
            if (item.status == 1) {
                html += '<a ng-show="roleList.includes(\'inventory.stock.opname.edit\')" title="Edit" ui-sref=\'operational_warehouse.stok_opname.edit({id:' + item.id + '})\'><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                html += '<a ng-show="roleList.includes(\'inventory.stock.opname.delete\')" title="Hapus" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
            }

            return html;
        } 
      }
      
    ],
    createdRow: function(row, data, dataIndex) {
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
      $http.delete(baseUrl+'/operational_warehouse/stok_opname/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
});
app.controller('opWarehouseStokOpnameCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, stokOpnameService) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
    $scope.formData={}
    $scope.formData.date_transaction=dateNow
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
    $scope.formData.detail = {};
    $scope.warehouses = [];
    $scope.racks = [];



    $scope.clearDetail = function() {
        detailTable.clear().draw();
        $scope.isItemExists = false;
    }


  // append table stok_opname create
    detailTable = $('#detailTable').DataTable({
    columns : [
      { 
        'data' : null, 
        'render' : function(resp) {
            return resp.warehouse_receipt_code            
        }
      },
      { 'data' : 'name'},
      { 'data' : 'rack_code'},
      { 'data' : 'qty', className : 'text-right'},
      { 
        'data' : null, 
        'render' : function(data, type, row, meta) {
          return '<input type="number" value="' + data.qty + '" class="form-control text-right qty_riil">';
        }
      },
      { 
        'data' : null, 
        'className' : 'text-center',
        'render' : function(data, type, row, meta) {
          return "<a ng-click='deletes($event.currentTarget)' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
      },
    ],
    'createdRow' : function(row, data) {
      $compile(angular.element(row).contents())($scope);
    }
    });
    $compile($('thead'))($scope)
    $compile($('.ibox-footer'))($scope)

  $scope.appendItem = function(v) {
    var params = {}
    params.item_id = v.id
    params.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
    params.rack_id = v.rack_id
    params.warehouse_receipt_code = v.warehouse_receipt_code
    params.qty = v.qty
    params.qty_riil = v.qty_riil
    params.name = v.name
    params.rack_code = v.rack_code
    detailTable.row.add(params).draw()
  }
    $scope.showDetail = function() {
         if($stateParams.id) {
            stokOpnameService.api.showDetail($stateParams.id, function(dt){
                dt.forEach(function(v){
                    v.id = v.item_id
                    v.name = v.item_name
                    v.qty = v.stock_sistem
                    $scope.appendItem(v)   
                })
            })
         }
      }

    $scope.show = function() {
         if($stateParams.id) {
            stokOpnameService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showDetail()
            })
         }
    }
    $scope.show()

    $scope.getItemWarehouses =  function(items) {
        for(i in items) {
            $scope.appendItem(items[i])
        }
    }

    $scope.$on('getItemWarehouse', function(e, v) {
        $scope.appendItem(v)
    })
    $scope.$on('getItemWarehouses', function(e, items) {
        $scope.getItemWarehouses(items)
    })

    $scope.$on('getWarehouse', function(e, v) {
        if(!$scope.formData.company_id) {
            $scope.formData.company_id = parseInt(v.company_id)
        }
    })


    $scope.is_allow_insert = function() {
        if(!$scope.detailData.item_id || $scope.detailData.stock==0 || parseInt($scope.detailData.qty) > parseInt($scope.detailData.stock)) {
            return true;
        }

        return false;
    }

    $scope.deletes = function(dom) {
        var tr = $(dom).parents('tr');
        detailTable.row(tr).remove().draw();

        var length = detailTable.data().toArray();
        if(length == 0) {
            $scope.isItemExists = false;
        }
    }

  
  $scope.disabledAppend = function() {
    if(!$scope.formData.detail.qty) {
      $scope.disabledAppendBtn = true;
    }
    else {
      if(parseInt($scope.formData.detail.qty) > parseInt($scope.formData.detail.stock) ) {
        $scope.disabledAppendBtn = true; 
      }
      else {
        $scope.disabledAppendBtn = false; 
      }
    }
  }

  $scope.cariPallet=function() {
    if (!$scope.formData.warehouse_id) {
      toastr.error("Anda harus memilih gudang terlebih dahulu!","Maaf!")
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



    $scope.choosePallet=function(json) {
        detailTable.row.add(json).draw()
        $scope.isItemExists = true;
        $('#modalItem').modal('hide')
    }


  $scope.counter=0
  $scope.detail = []
  $scope.appendDetail=function() {
    if($scope.formData.type == 1) {
        var request = {
          warehouse_id : $scope.formData.warehouse_id
        };
        detailTable.clear().draw()
        request = $.param(request);
        $http.get(baseUrl+'/api/operational_warehouse/item_warehouse_datatable?' + request).then(function success(data) {
            var result = data.data;
            if(result.data.length > 0) {
                $scope.getItemWarehouses(result.data)
            }
        }, function error(data) {
            toastr.error("Gagal mengambil data","Error Has Found!");
        });
    }
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
    if($scope.formData.detail.length == 0) {
      $scope.isItemExists = false;
    }
  }

    $rootScope.disBtn = false
    $scope.submitForm=function() {
        var detailItem = detailTable.data().toArray();
        var unit;

        for(x in detailItem) {
            detailItem[x].qty_riil = $(detailTable.rows(x).nodes()).find('.qty_riil').val();
        }

        var formData = $scope.formData
        formData.detail = detailItem;
        $rootScope.disBtn = true
        if($stateParams.id) {

            stokOpnameService.api.update(formData, $stateParams.id, function(){
                $state.go('operational_warehouse.stok_opname')
            })
        } else {
            stokOpnameService.api.store(formData, function(){
                $state.go('operational_warehouse.stok_opname')
            })
        }
    }
});

app.controller('opWarehouseStokOpnameShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, stokOpnameService) {
  $rootScope.pageTitle="Detail Stok Opname";

  $scope.status=[
        {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
        {id:2,name:'<span class="badge badge-primary">Disetujui</span>'},
  ]

  $scope.showDetail = function() {
     if($stateParams.id) {
        stokOpnameService.api.showDetail($stateParams.id, function(dt){
            $scope.detail = dt
        })
     }
  }

  $scope.show = function() {
     if($stateParams.id) {
        stokOpnameService.api.show($stateParams.id, function(dt){
            $scope.item = dt
            $scope.showDetail()
        })
     }
  }
  $scope.show()

  $scope.editDetail=function(json) {
        $('#editModal').modal()
        $scope.editData={}
        $scope.editData.id=json.id
        $scope.editData.qty=parseInt(json.qty)
        $scope.editData.stock=parseInt(json.stock)
        $scope.validateStock();
  }

  $scope.deleteDetail=function(id) {
    var cofs=confirm("Apakah anda yakin ?")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/stok_opname/delete_detail/'+id).then(function(data) {
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
    $http.post(baseUrl+'/operational_warehouse/stok_opname/store_detail',$scope.editData).then(function(data) {
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

  $scope.approve=function() {
    var cofs=confirm("Apakah anda yakin ?")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.put(stokOpnameService.url.approve($stateParams.id)).then(function(data) {
      $scope.disBtn=false;
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
    $http.post(baseUrl+'/operational_warehouse/stok_opname/item_in/'+$stateParams.id).then(function(data) {
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

