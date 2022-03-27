app.controller('inventoryAdjustment', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Penyesuaian Stok Barang";
  $scope.formData = {};
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl + '/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/inventory/adjustment_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;
        request['warehouse_id'] = $scope.formData.warehouse_id;

        return request;
      },
        dataSrc: function(d) {
            $('.ibox-content').removeClass('sk-loading');
            return d.data;
        }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"warehouse.name",name:"warehouse.name"},
      // {data:"purchase_order.code",name:"purchase_order.code",className:"font-bold"},
      {data:"code",name:"code"},
      {
        data:null,
        name:"date_transaction",
        searchable:false,
        render:resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"creates.name",name:"creates.name"},
      // {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }
});

app.controller('inventoryAdjustmentCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Penyesuaian Stok";
  $scope.formData={
     'company_id' : compId
  };

  $scope.formData.detail=[];
  $scope.formData.date_transaction=dateNow;

  $scope.detailData={};
  $scope.detailData.qty=0;
  $scope.detailData.warehouse_stock=0;
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/inventory/adjustment/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.companyChange=function(id) {
    $('.ibox-content').toggleClass('sk-loading');
    $http.get(baseUrl+'/inventory/purchase_request/cari_gudang',{params:{company_id:id}}).then(function(data) {
      $scope.warehouse=[];
      angular.forEach(data.data, function(val,i) {
        $scope.warehouse.push({id:val.id,name:val.name});
      });
      $('.ibox-content').toggleClass('sk-loading');
    });
  }
  $scope.companyChange(compId)

  $scope.warehouseChange=function(id) {
    $('.ibox-content').toggleClass('sk-loading');
    $http.get(baseUrl+'/inventory/adjustment/cari_item',{params:{warehouse_id:id}}).then(function(data) {
      $scope.items=[];
      angular.forEach(data.data, function(val,i) {
        $scope.items.push({id:val.item_id,name:val.name,group:val.category,qty:val.qty});
      });
      $('.ibox-content').toggleClass('sk-loading');
    });
  }
  $scope.urut=0;
  $scope.appendTable=function() {
    var html="";

    html+="<tr id='row-"+$scope.urut+"'>";
    html+="<td>"+$('#item option:selected').text()+"</td>";
    html+="<td>"+$filter('number')($scope.detailData.qty)+"</td>";
    html+="<td><a ng-click='deleteRow("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $scope.formData.detail.push({
      item_id:$scope.detailData.item_id.id,
      stock:$scope.detailData.item_id.qty,
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
      type: "post",
      url: baseUrl+'/inventory/adjustment?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('inventory.adjustment');
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

app.controller('inventoryAdjustmentShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penyesuaian Stok Barang";
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/inventory/adjustment/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $('.ibox-content').toggleClass('sk-loading');
  });
});
