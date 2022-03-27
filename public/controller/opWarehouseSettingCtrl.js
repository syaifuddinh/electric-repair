app.controller('opWarehouseSetting', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Warehouse Setting";
    $scope.state=$state;
    //$scope.stateParams=$stateParams;

    $scope.init = function() {
        if($rootScope.roleList.includes('inventory.setting.warehouse')){
            $state.go('operational_warehouse.setting.warehouse',null,{'location':'replace'})
        } else if($rootScope.roleList.includes('inventory.setting.bin_location')) {
            $state.go('operational_warehouse.setting.bin_location')
        } else if($rootScope.roleList.includes('inventory.setting.pallet')) {
            $state.go('operational_warehouse.setting.pallet',null,{'location':'replace'})
        } else if($rootScope.roleList.includes('inventory.setting.storage')) {
            $state.go('operational_warehouse.setting.storage')
        }
    }
});

app.controller('opWarehouseSettingWarehouseCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, warehousesService) {
    $rootScope.pageTitle="Create";
    $scope.id = $stateParams.id
    
});


app.controller('opWarehouseSettingWarehouse', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.warehouses.title 
});

app.controller('opWarehouseSettingWarehouseShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, warehousesService) {
  $rootScope.pageTitle = $rootScope.solog.label.warehouses.title 
  $scope.formData = {}
  warehousesService.api.show($stateParams.id, function(dt) {
      $scope.formData = dt
  })

  $scope.openInfo = function() {
          $('.tab-item').hide()
          $('#info_detail').show()
      }

  $scope.openInfo()

  $scope.openRack = function() {
      $('.tab-item').hide()
      $('#rack_detail').show()
  }
  $scope.openReceipt = function() {
      $('.tab-item').hide()
      $('#receipt_detail').show()
  }
  $scope.openMap = function() {
      $('.tab-item').hide()
      $('#map_detail').show()
  }
});

app.controller('opWarehouseSettingRack', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Bin Location";
});

app.controller('opWarehouseSettingRackShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, racksService) {
    $rootScope.pageTitle="Bin Location Detail";

    $scope.stateParams = $stateParams;
    $scope.id = $stateParams.id
    $scope.states = $state;
    $scope.data = {}
    $rootScope.emptyBuffer()

    $scope.openInfo = function() {
          $('.tab-item').hide()
          $('#info_detail').show()
    }
    $scope.openInfo()

    $scope.openCategory = function() {
          $('.tab-item').hide()
          $('#category_detail').show()
    }

    $scope.openItem = function() {
          $('.tab-item').hide()
          $('#item_detail').show()
    }

    $scope.show =  function() {
        racksService.api.show($stateParams.id, function(dt){
            $scope.data = dt
        })
    }
    $scope.show()

    $scope.backward=function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $state.go('operational_warehouse.setting.rack');
        }
    }

});

app.controller('opWarehouseSettingRackShowQRCode', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    var url = baseUrl + '/operational_warehouse/setting/rack/' + $stateParams.id + '/qrcode'
    $('#barcodeEmbed').attr('src', url)
});

app.controller('opWarehouseSettingPallet', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Kategori Pallet";
  $('.sk-container').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm ',
        filename: 'Inventory - Setting - Kategori Pallet | ' + new Date,
        sheetName: 'Data',
        title: 'Inventory - Setting - Kategori Pallet',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/pallet_category_datatable',
      dataSrc: function(d) {
        $('.sk-container').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"parent",name:"parent.name"},
      {data:"code",name:"categories.code"},
      {data:"name",name:"categories.name"},
      {data:"description",name:"categories.description"},
      {
        data:null,
        orderable:false, 
        searchable:false,
        className:"text-center",
        render : function(item) {
            var html = ''
            html += '<a ng-show="roleList.includes(\'inventory.setting.pallet.edit\')" ng-click=\'edit(' + JSON.stringify(item) + ')\'><i class="fa fa-edit"></i></a>&nbsp;';
            if (item.parent_id) {
                html += '<a ng-show="roleList.includes(\'inventory.setting.pallet.delete\')" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
            }

            return html
        }
    },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational_warehouse/setting/delete_rack/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $http.get(baseUrl+'/operational_warehouse/setting/category_pallet_list').then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })

    

  $scope.add=function() {
    $scope.modalTitle="Tambah Kategori Pallet";
    $scope.formData={}
    $scope.formData.is_pallet=1
    if( $scope.data.category.length > 0) {

      $scope.formData.parent_id=$scope.data.category[0].id
    }
    $('#modalForm').modal();
  }

  $scope.edit=function(jsn) {
    // console.log(jsn);
    $scope.modalTitle="Edit Kategori Pallet";
    $scope.formData=Object.assign({},jsn)
    $scope.formData.parent_id = parseInt(jsn.parent_id)
    // $scope.formData.id=jsn.id
    // $scope.formData.parent_id=jsn.parent_id
    // $scope.formData.name=jsn.name
    // $scope.formData.code=jsn.code
    // $scope.formData.description=jsn.description
    $scope.formData.is_pallet=1
    $('#modalForm').modal();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/setting/store_pallet_category',$scope.formData).then(function(data) {
      oTable.ajax.reload()
      $('#modalForm').modal('hide');
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
app.controller('opWarehouseSettingStorage', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Storage Type";
  $('.sk-container').removeClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm ',
        filename: 'Inventory - Setting - Storage Type | ' + new Date,
        sheetName: 'Data',
        title: 'Inventory - Setting - Storage Type',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/storage_type_datatable',
      dataSrc: function(d) {
        $('.sk-container').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {
        data:null,
        name:"created_at",
        className:"text-center",
        render : function(resp) {
          if(resp.is_handling_area != 1 && resp.is_picking_area != 1 && resp.is_stripping_area != 1) {
            return resp.action;
          }

        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      // $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational_warehouse/setting/delete_storage/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $http.get(baseUrl+'/operational_warehouse/setting/category_pallet_list').then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })

  $scope.add=function() {
    $scope.modalTitle="Add New Storage Type";
    $scope.formData={}
    $('#modalForm').modal();
  }

  $scope.edit=function(jsn) {
    // console.log(jsn);
    $scope.modalTitle="Edit Storage Type";
    $scope.formData={}
    $scope.formData.id=jsn.id
    $scope.formData.name=jsn.name
    $('#modalForm').modal();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/setting/store_storage_type',$scope.formData).then(function(data) {
      oTable.ajax.reload()
      $('#modalForm').modal('hide');
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
