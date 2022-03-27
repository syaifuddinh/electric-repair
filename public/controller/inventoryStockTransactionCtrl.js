app.controller('inventoryStockTransaction', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Stock Card";
  $scope.filterData={};
  $scope.dataList=[]
  oTable = $('#datatable').DataTable({
    ordering:false,
    // scrollX:'100%',
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    data: $scope.dataList,
    columns:[
      {data:"date_transaction"},
      {data:"code"},
      {data:"type_transactions"},
      {data:null,className:"text-right",render: e => $filter('number')(e.qty_masuk)},
      {data:null,className:"text-right",render: e => $filter('number')(e.qty_keluar)},
      {data:null,className:"text-right",render: e => $filter('number')(e.saldo)},
      {data:"description"},
      // {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(baseUrl+'/api/inventory/gudang_dan_item').then(function(data) {
    $scope.data=data.data;
  });

  $scope.getDataList=function() {
    const {warehouse_id, item_id} = $scope.filterData
    if (!warehouse_id || !item_id) {
      return toastr.error("Please select warehouse and item")
    } else {
      $scope.dataList=[]
      $http.get(`${baseUrl}/api/inventory/stock_transaction_datatable`, {params: $scope.filterData}).then(function(e) {
        let saldo = 0;
        for (var i = 0; i < e.data.length; i++) {
          const data = e.data[i]
          saldo+=(data.qty_masuk-data.qty_keluar)
          let row = Object.assign({},{...data,...{saldo: saldo}})
          $scope.dataList.push(row)
        }
        oTable.clear()
        oTable.rows.add($scope.dataList)
        oTable.draw()
        console.log($scope.dataList)
      })
    }
  }

  $scope.searchData=function() {
    $scope.getDataList()
  }

  $scope.export = function() {
    var params = $.param( $scope.filterData );
    var url = baseUrl + '/excel/stock_transaction_export?';
    url = url + params;
    location.href = url;
  }

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

});
