app.controller('opWarehousePalletMigration', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Pallet Migration";
});
app.controller('opWarehousePalletMigrationCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Add Migration";
  $scope.formData={}
  $scope.formData.date_transaction=dateNow
  $scope.formData.detail=[]
  $scope.detailData={}
  $scope.detailData.qty=1
  $scope.detailData.stock=0

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/master_pallet_datatable',
      data: function(d) {
        d.warehouse_id = $scope.formData.warehouse_from_id
      }
    },
    columns:[
      {data:"action_choose",name:"created_at",className:"text-center"},
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"barcode",name:"barcode", className : 'hidden'},
      {data:"category",name:"categories.name"},
      {data:"piece.name",name:"piece.name"},
      {data:"description",name:"description"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.cariPallet=function() {
    if (!$scope.formData.warehouse_from_id) {
      toastr.error("Anda harus memilih gudang telebih dahulu!","Maaf!")
      return null;
    }
    $('#modalItem').modal()
    oTable.ajax.reload();
  }

  $scope.choosePallet=function(json) {
    $('#modalItem').modal('hide')
    $scope.detailData.item_id=json.id
    $scope.detailData.item_name=json.name+' ('+json.code+')'
    $http.get(baseUrl+'/operational_warehouse/pallet_using/cek_stok',{params:{warehouse_id:$scope.formData.warehouse_from_id,item_id:$scope.detailData.item_id}}).then(function(data){
      if (data.data.qty) {
        $scope.detailData.stock=parseInt(data.data.qty)
      } else {
        $scope.detailData.stock=0
      }
    },function(error){
      console.log(error)
    })
  }

  $scope.changeWarehouseFrom=function(id) {
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
    $scope.counter=0
    $scope.formData.company_id=$rootScope.findJsonId(id,$scope.data.warehouse).company_id

    $scope.rack_from=[]
    angular.forEach($scope.data.rack,function(val,i) {
      if (val.warehouse_id==id) {
        $scope.rack_from.push({id:val.id,name:val.code})
      }
    })
    $('#appendTable tbody').empty()
  }
  $scope.changeWarehouseTo=function(id) {
    $scope.rack_to=[]
    angular.forEach($scope.data.rack,function(val,i) {
      if (val.warehouse_id==id) {
        $scope.rack_to.push({id:val.id,name:val.code})
      }
    })
  }

  $http.get(baseUrl+'/operational_warehouse/pallet_migration/create').then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })
  $scope.counter=0
  $scope.appendTable=function() {
    var dt=$scope.detailData
    var fail=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      if (val.item_id==dt.item_id) {
        toastr.error("Item sudah ditambahkan","Maaf");
        fail=1;
      }
    })
    if (fail) {
      return null
    }
    var html=""
    var item=$rootScope.findJsonId(dt.item_id,$scope.data.item)

    $scope.formData.detail.push({
      item_id:dt.item_id,
      qty:dt.qty,
      price:dt.price
    })

    html+="<tr id='row-"+$scope.counter+"'>"
    html+="<td>"+item.code+"</td>"
    html+="<td>"+item.name+"</td>"
    html+="<td class='text-right'>"+$filter('number')(dt.qty)+"</td>"
    html+="<td><a ng-click='deleteAppend("+$scope.counter+")'><span class='fa fa-trash'></span></a></td>"
    html+="</tr>"

    $('#appendTable').append($compile(html)($scope))
    $scope.counter++
    $scope.resetDetail()
    $scope.hitungAppend()
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    delete $scope.formData.detail[id]
    $scope.hitungAppend()
  }

  $scope.hitungAppend=function() {
    var count=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      count++
    })
    if (count==0) {
      $scope.disBtn=true
    } else {
      $scope.disBtn=false
    }
  }

  $scope.disBtn=true
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/pallet_migration',$scope.formData).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.go('operational_warehouse.pallet_migration');
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
});
app.controller('opWarehousePalletMigrationShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Migration";

  $http.get(baseUrl+'/operational_warehouse/pallet_migration/'+$stateParams.id).then(function(data){
    $scope.item=data.data.item
    $scope.detail=data.data.detail
  },function(error){
    console.log(error)
  })

  $scope.status=[
    {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-primary">Item Out (On Transit)</span>'},
    {id:3,name:'<span class="badge badge-info">Item Receipt (Done)</span>'},
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
    $http.post(baseUrl+'/operational_warehouse/pallet_migration/delete_detail/'+id).then(function(data) {
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
    $http.post(baseUrl+'/operational_warehouse/pallet_migration/store_detail',$scope.editData).then(function(data) {
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
    $http.post(baseUrl+'/operational_warehouse/pallet_migration/item_out/'+$stateParams.id).then(function(data) {
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
  $scope.item_in=function() {
    var cofs=confirm("Apakah anda yakin ? barang akan dimasukkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/pallet_migration/item_in/'+$stateParams.id).then(function(data) {
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
