app.controller('opWarehouseJO', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Job Order";
  // oTable = $('#datatable').DataTable({
  //   processing: true,
  //   serverSide: true,
  //   order:[[3,'desc'],[1,'desc']],
  //   ajax: {
  //     url : baseUrl+'/api/marketing/work_order_datatable',
  //   },
  //   columns:[
  //     {data:"company.name",name:"company.name"},
  //     {data:"code",name:"code"},
  //     {data:"customer.name",name:"customer.name",className:"font-bold"},
  //     {data:"created_at",name:"created_at"},
  //     {data:"quotation.no_contract",name:"quotation.no_contract",className:"font-bold"},
  //     {data:"total_job_order",name:"total_job_order",className:"text-right"},
  //     {data:"status",name:"status",className:""},
  //     {data:"action",name:"action",className:"text-center"},
  //   ],
  //   createdRow: function(row, data, dataIndex) {
  //     $compile(angular.element(row).contents())($scope);
  //   }
  // });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/work_order/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('opWarehouseJOCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Job Order";
  $scope.formData={}
  $scope.formData.company_id=compId
  $scope.formData.shipment_date=dateNow

  $http.get(baseUrl+'/operational_warehouse/job_order/create').then(function(data) {
    $scope.data=data.data;
  });

  var wodTable = $('#wo_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[1,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_detail_datatable',
      data: function(d) {
        d.customer_id=$scope.formData.customer_id;
        d.is_done=0;
        d.filter_qty=1;
        d.company_id=$scope.formData.company_id;
        d.service_type_id=5
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center"},
      {data:"code",name:"code"},
      {data:"service",name:"service"},
      {data:"trayek",name:"trayek"},
      {data:"commodity",name:"commodity"},
      {data:"type_tarif_name",name:"type_tarif_name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  var receiptTable = $('#receipt_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[1,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/receipt_detail_datatable',
      data: function(d) {
        d.customer_id=$scope.formData.customer_id;
        d.left_over_more=1;
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center"},
      {data:"is_export",name:"header.is_export"},
      {data:"header.code",name:"header.code"},
      {data:"header.city_to",name:"header.city_to"},
      {data:"item_name",name:"item_name"},
      {data:"piece.name",name:"piece.name"},
      {data:"qty",name:"qty"},
      {data:"leftover_warehouse",name:"leftover_warehouse"},
      {data:"imposition",name:"imposition"},
      {data:"rack.code",name:"rack.code"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.cariWO=function() {
    if (!$scope.formData.customer_id) {
      return toastr.error("Anda Harus Memilih Customer");
    }
    wodTable.ajax.reload(function() {
      $('#modalWO').modal('show');
    });
  }
  $scope.cariReceipt=function() {
    if (!$scope.formData.customer_id) {
      return toastr.error("Anda Harus Memilih Customer");
    }
    receiptTable.ajax.reload(function() {
      $('#modalReceipt').modal('show');
    });
  }

  $scope.chooseWO=function(jsn) {
    // console.log(jsn);
    $scope.formData.work_order_code=jsn.code;
    $scope.formData.imposition=jsn.imposition;
    $scope.formData.commodity_id=jsn.commodity_id;
    $('#modalWO').modal('hide');
  }

  $scope.chooseReceiptDetail=function(jsn) {
    // console.log(jsn);
    $scope.detailData={}
    $scope.detailData.qty=jsn.leftover_warehouse
    $scope.detailData.receipt_code=jsn.code+' - '+jsn.item_name
    // $scope.detailData.
    // $scope.
  }

});
