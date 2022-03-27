app.controller('opWarehousePalletDeletion', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Pallet Deletion";
    $scope.filterData={}
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[4,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/pallet_deletion_datatable',
      data: function(d) {
        d.warehouse_id=$scope.filterData.warehouse_id
        d.start_date=$scope.filterData.start_date
        d.end_date=$scope.filterData.end_date
      }
    },
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'action' : newExportAction,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Pallet Deletion - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Pallet Deletion'
      },
    ],

    columns:[
      {data:"warehouse_name",name:"warehouses.name"},
      {data:"code",name:"code",className:"font-bold"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"status_name",name:"item_deletion_statuses.name",className:"text-center"},
      {
            data:null,
            orderable:false,
            searchable:false,
            className:"text-center",
            render : function(item) {
                var html = ''
                html += "<a ui-sref='operational_warehouse.pallet_deletion.edit({id:" + item.id + "})' ><span class='fa fa-edit'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                html += "<a ng-show=\"roleList.includes('operational_warehouse.pallet.deletion.detail')\" ui-sref='operational_warehouse.pallet_deletion.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                if (item.status == 1) {
                    html += '<a ng-show="roleList.includes(\'operational_warehouse.pallet.deletion.delete\')" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
                }

                return html;
            }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational_warehouse/pallet_deletion/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $http.get(baseUrl+'/operational_warehouse/pallet_deletion').then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })

    $scope.filter=function() {
        oTable.ajax.reload()
    }

    $scope.reset_filter=function() {
        $scope.filterData={}
        oTable.ajax.reload()
    }

});
app.controller('opWarehousePalletDeletionCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, itemDeletionsService) {
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
           itemDeletionsService.api.showDetail($stateParams.id, function(dt){
                dt = dt.map(function(v){
                    v.code = v.item_code
                    v.name = v.item_name

                    return v
                })
              $scope.detail = dt
           })
      }
  }

  $scope.show = function() {
      if($stateParams.id) {
           itemDeletionsService.api.show($stateParams.id, function(dt){
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
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('operational_warehouse.pallet_deletion')
        }
    }

  $scope.submitForm=function() {
    var method = 'post'
    var url = itemDeletionsService.url.store()
    if($stateParams.id) {
        method = 'put'
        url = itemDeletionsService.url.update($stateParams.id)        
    }
    $scope.disBtn=true;
    var item_detail = $scope.detail;
    $scope.formData.detail = item_detail;


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
app.controller('opWarehousePalletDeletionShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Deletion";

  $http.get(baseUrl+'/operational_warehouse/pallet_deletion/'+$stateParams.id).then(function(data){
    $scope.item=data.data.item
    $scope.detail=data.data.detail
  },function(error){
    console.log(error)
  })

  $scope.status=[
    {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-primary">Approved</span>'},
  ]

  $scope.back = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('operational_warehouse.pallet_deletion')
        }
    }

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
    $http.post(baseUrl+'/operational_warehouse/pallet_deletion/delete_detail/'+id).then(function(data) {
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
    $http.post(baseUrl+'/operational_warehouse/pallet_deletion/store_detail',$scope.editData).then(function(data) {
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
    var cofs=confirm("Apakah anda yakin ? barang akan dikeluarkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/pallet_deletion/approve/'+$stateParams.id).then(function(data) {
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
