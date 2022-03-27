    app.controller('opWarehouseItemReceiptReport', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.moving_item_report.title;
    $scope.formData = {};

    $scope.formData = {};
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order : [[0, 'desc'], [2, 'desc']],
    dom:'Blfrtip',
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/receipt_report_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['warehouse_id'] = $scope.formData.warehouse_id;
        request['customer_id'] = $scope.formData.customer_id;
        request['is_zero'] = $scope.formData.is_zero;
        request['is_not_cancel'] = $scope.formData.is_not_cancel;

        return request;
      }
    },
    buttons: [
      {
        'extend' : 'excel',
        'action' : newExportAction,
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Laporan Pergerakan Barang - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Laporan Pergerakan Barang'
      },
    ],
    columns:[
      {data:"warehouse_receipt_code",name:"warehouse_receipts.code"},
      {
        data:null,
        searchable:false,
        render : resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"customer_name",name:"contacts.name"},
      {data:"warehouse_name",name:"warehouses.name"},
      {data:"rack_code",name:"racks.code"},
      {data:"item_name",name:"warehouse_receipt_details.item_name"},
      {data:"qty_masuk",name:"stock_transactions.qty_masuk", className:'text-right'},
      {data:"qty_keluar",name:"stock_transactions.qty_keluar", className:'text-right'},
      {data:"qty_sisa",name:"stocks.qty_sisa", className:'text-right'},
      {data:"description",name:"stock_transactions.description"}
    ],
    createdRow: function(row, data, dataIndex) {
      var status = data.warehouse_receipt_status
      if(status !== null) {
          if(status == 2) {
              $(row).addClass('text-danger')
          } 
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );
  $compile($('thead'))($scope)

    $scope.export_excel = function() {
        var request = $.param( $scope.formData );
        var url = baseUrl+'/excel/laporan_penerimaan_barang_export?' + request;

        location.href = url;
    }
    $scope.searchData = function() {
        oTable.ajax.reload();
    }
    $scope.resetFilter = function() {
        $scope.formData = {};
        oTable.ajax.reload();
    }


});

app.controller('opWarehouseItemReceiptReportShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penerimaan Barang";

  $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:4,name:'Kubikasi & Tonase'},
  ]
  $scope.is_export=[
    {id:1,name:'Export'},
    {id:0,name:'Local'},
  ]
  $scope.is_overtime=[
    {id:1,name:'YA'},
    {id:0,name:'TIDAK'},
  ]

  $http.get(baseUrl+'/operational_warehouse/receipt/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
  });

});
