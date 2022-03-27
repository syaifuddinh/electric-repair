app.controller('opWarehousePalletPOReturn', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Purchase Order Return";
});

app.controller('opWarehousePalletPOReturnCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Create PO Return";
  $scope.formData={}
  $scope.formData.date_transaction=dateNow
  $scope.formData.detail=[]

  oTable = $('#po_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/pallet_purchase_order_datatable',
      data: function(d) {
        d.po_status = 2
        d.warehouse_id = $scope.formData.warehouse_id
        d.not_po_retur=1
      }
    },
    columns:[
      {data:"action_choose",name:"created_at",className:"text-center"},
      {data:"code",name:"code"},
      {data:"po_date",name:"po_date"},
      {data:"supplier.name",name:"supplier.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.cariPoModal=function() {
    console.log("jos")
    if (!$scope.formData.warehouse_id) {
      toastr.error("Anda harus memilih gudang dahulu!","Maaf")
      return null;
    }
    $('#modalPo').modal()
    oTable.ajax.reload();
  }

  $scope.choosePo=function(id) {
    $http.get(baseUrl+'/operational_warehouse/pallet_purchase_order_return/cari_po',{params:{id:id}}).then(function(data){
      var it=data.data.item;
      $scope.formData.purchase_order_id=it.id
      $scope.formData.po_code=it.code

      var det=data.data.detail;
      var html=""
      angular.forEach(det,function(val,i) {
        html+="<tr>"
        html+="<td>"+val.barcode+"</td>"
        html+="<td>"+val.item_name+"</td>"
        html+="<td class='text-right'>"+$filter('number')(val.qtys)+"</td>"
        html+="<td class='text-right'>Rp. "+$filter('number')(val.price)+"</td>"
        html+="<td class='text-center'><input type='text' class='form-control' ng-change='cekInput(formData.detail["+i+"])' ng-model='formData.detail["+i+"].qty_return' jnumber2 only-num></td>"
        html+="</tr>"

        $scope.formData.detail.push({
          item_id:val.item_id,
          qty:val.qtys,
          qty_return:0,
        })
      })

      $('#appendTable tbody').html($compile(html)($scope))
      $('#modalPo').modal('hide')

    },function(error){
      console.log(error)
    })
  }

  $scope.cekInput=function(data) {
    var qty=parseFloat(data.qty)
    var qty_return=parseFloat(data.qty_return)
    if (qty_return>qty) {
      data.qty_return = data.qty
    }
  }

  $http.get(baseUrl+'/operational_warehouse/pallet_purchase_order_return/create').then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })
  $scope.counter=0

  $scope.resetAppend=function(json) {
    $scope.formData={}
    $scope.formData.detail=[]
    $scope.formData.warehouse_id=json.warehouse_id
    $scope.formData.date_transaction=json.date_transaction
    $scope.formData.company_id=$rootScope.findJsonId(json.warehouse_id,$scope.data.warehouse).company_id
    $('#appendTable tbody').empty()
    $scope.counter=0
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    delete $scope.formData.detail[id]
    $scope.hitungAppend()
  }

  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/pallet_purchase_order_return',$scope.formData).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.go('operational_warehouse.pallet_po_return');
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
app.controller('opWarehousePalletPOReturnShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Purchase Order Return";

  $http.get(baseUrl+'/operational_warehouse/pallet_purchase_order_return/'+$stateParams.id).then(function(data){
    $scope.item=data.data.item
    $scope.detail=data.data.detail
  },function(error){
    console.log(error)
  })

  $scope.status=[
    {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-primary">Dikirimkan</span>'},
    {id:3,name:'<span class="badge badge-info">Diterima Sebagian</span>'},
    {id:4,name:'<span class="badge badge-info">Diterima Lengkap</span>'},
  ]

  $scope.approve=function() {
    var cofs=confirm("Apakah anda yakin ? barang akan dikeluarkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/pallet_purchase_order_return/approve/'+$stateParams.id).then(function(data) {
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
