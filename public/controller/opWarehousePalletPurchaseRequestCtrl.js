app.controller('opWarehousePalletPurchaseRequest', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Purchase Request in Pallet";
    $scope.filterData={}
});

app.controller('opWarehousePalletPurchaseRequestCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Permintaan Pembelian Pallet";
  $scope.formData={}
  $scope.formData.is_pallet=1
  $scope.formData.date_request=dateNow
  $scope.formData.date_needed=dateNow
  $scope.formData.detail=[]
  $scope.detailData={}
  $scope.detailData.qty=1

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/master_pallet_datatable',
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
    $('#modalItem').modal()
    oTable.ajax.reload();
  }

  $scope.choosePallet=function(json) {
    $('#modalItem').modal('hide')
    $scope.detailData.item_id=json.id
    $scope.detailData.item_name=json.name+' ('+json.code+')'
    $scope.detailData.item_code=json.code
  }

  $http.get(baseUrl+'/inventory/purchase_request/create').then(function(data){
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
    })

    html+="<tr id='row-"+$scope.counter+"'>"
    html+="<td>"+$scope.detailData.item_code+"</td>"
    html+="<td>"+$scope.detailData.item_name+"</td>"
    html+="<td>"+$filter('number')(dt.qty)+"</td>"
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
    $http.post(baseUrl+'/inventory/purchase_request',$scope.formData).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.go('operational_warehouse.pallet_purchase_request');
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
app.controller('opWarehousePalletPurchaseRequestShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Permintaan Pembelian Pallet";
  $scope.formData={};
  $scope.qty_approve=false;
  $scope.price_po=false;
  $scope.editForm=false;
  $http.get(baseUrl+'/inventory/purchase_request/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
  });

  $scope.status=[
    {id:0,name:'Permintaan Ditolak'},
    {id:1,name:'Belum Persetujuan'},
    {id:2,name:'Sudah Persetujuan'},
    {id:3,name:'Purchase Order'},
  ]

  $scope.editDetail=function(val) {
    $scope.detailData={}
    $scope.detailData.id=val.id
    $scope.detailData.vehicle_id=val.vehicle_id
    $scope.detailData.item_id=val.item_id
    $scope.detailData.qty=val.qty
    $('#modalEdit').modal()
  }

  $scope.approve=function() {
    $scope.qty_approve=true;
    $scope.editForm=true;
  }
  $scope.createPo=function() {
    $scope.price_po=true;
    $scope.editForm=true;
  }

  $scope.reject=function() {
    $scope.rejectData={}
    $('#modalReject').modal()
  }

  $scope.disBtn=false;
  $scope.approveSubmit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/approve/'+$stateParams.id+'?_token='+csrfToken,$scope.formData).then(function(data) {
      $state.reload();
    });
  }
  $scope.deleteDetail=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $scope.disBtn=true;
    $http.delete(baseUrl+'/inventory/purchase_request/delete_detail/'+id).then(function(data) {
      toastr.success("Detail Permintaan Telah Dihapus!")
      $state.reload();
    });
  }
  $scope.rejectSubmit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/reject/'+$stateParams.id,$scope.rejectData).then(function(data) {
      $('#modalReject').modal('hide')
      toastr.success("Permintaan Telah ditolak!")
      $timeout(function() {
        $state.reload();
      },1000)
    });
  }
  $scope.submitDetail=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/store_detail/'+$scope.detailData.id,$scope.detailData).then(function(data) {
      $('#modalEdit').modal('hide')
      toastr.success("Data Detail Telah Diubah!")
      $timeout(function() {
        $state.reload();
      },1000)
    });
  }
  $scope.poSubmit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/create_po/'+$stateParams.id+'?_token='+csrfToken,$scope.formData).then(function(data) {
      $state.reload();
    });
  }

});
app.controller('opWarehousePalletPurchaseRequestEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Permintaan Pembelian Pallet";
  $scope.formData={};
  $scope.formData.detail=[];

  $scope.detailData={};
  $scope.detailData.qty=0;
  $scope.urut=0;
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/inventory/purchase_request/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.i;
    $scope.formData.company_id=dt.company_id
    $scope.formData.warehouse_id=dt.warehouse_id
    $scope.formData.supplier_id=dt.supplier_id
    $scope.formData.description=dt.description
    $scope.formData.date_request=$filter('minDate')(dt.date_request)
    $scope.formData.date_needed=$filter('minDate')(dt.date_needed)

    $scope.companyChange(dt.company_id)

    var html=""
    angular.forEach(data.data.detail, function(val,i) {
      $scope.formData.detail.push({
        item_id:val.item_id,
        qty:val.qty,
      });

      html+="<tr id='row-"+$scope.urut+"'>";
      html+="<td>"+val.item.name+"</td>";
      html+="<td>"+$filter('number')(val.qty)+"</td>";
      html+="<td><a ng-click='deleteRow("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
      html+="</tr>";

      $scope.urut++
    })
    $('#appendTable tbody').append($compile(html)($scope));
    $scope.hitungDetail();
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.companyChange=function(id) {
    $http.get(baseUrl+'/inventory/purchase_request/cari_gudang',{params:{company_id:id}}).then(function(data) {
      $scope.warehouse=[];
      angular.forEach(data.data, function(val,i) {
        $scope.warehouse.push({id:val.id,name:val.name});
      });
    });
    $http.get(baseUrl+'/inventory/purchase_request/cari_kendaraan',{params:{company_id:id}}).then(function(data) {
      $scope.vehicle=[];
      angular.forEach(data.data, function(val,i) {
        $scope.vehicle.push({id:val.id,nopol:val.nopol});
      });
    });
  }
  $scope.appendTable=function() {
    var html="";
    html+="<tr id='row-"+$scope.urut+"'>";
    html+="<td>"+$('#item option:selected').text()+"</td>";
    html+="<td>"+$filter('number')($scope.detailData.qty)+"</td>";
    html+="<td><a ng-click='deleteRow("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $scope.formData.detail.push({
      vehicle_id:$scope.detailData.vehicle_id,
      item_id:$scope.detailData.item_id,
      qty:$scope.detailData.qty,
    })

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.urut++;
    $scope.detailData={};
    $scope.detailData.qty=0;

    $scope.hitungDetail();
  }
  $scope.total=0;
  $scope.hitungDetail=function() {
    $scope.total=0;
    angular.forEach($scope.formData.detail, function(val,i) {
      if (val) {
        $scope.total+=parseFloat(val.qty);
      }
    });
  }

  $scope.deleteRow=function(id) {
    $('#row-'+id).remove();
    delete $scope.formData.detail[id];
    $scope.hitungDetail();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "PUT",
      url: baseUrl+'/inventory/purchase_request/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('operational_warehouse.pallet_purchase_request');
        // oTable.ajax.reload();
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

});
