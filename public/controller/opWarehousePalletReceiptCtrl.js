app.controller('opWarehousePalletReceipt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Penerimaan Pallet";
});

app.controller('opWarehousePalletReceiptCreatePo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Add Penerimaan Pallet";
});

app.controller('opWarehousePalletReceiptShow', function($scope, $http, $rootScope,$location,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penerimaan Barang";
  $http.get(baseUrl+'/inventory/receipt/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.list=data.data.list;
    $scope.detail=data.data.detail;

    var it=$scope.item
    if (it.po_id) {
      $scope.source_type_id=1
      $scope.transaction_id=it.po_id
    } else if (it.po_return_id) {
      $scope.source_type_id=2
      $scope.transaction_id=it.po_return_id
    } else if (it.sales_return_id) {
      $scope.source_type_id=3
      $scope.transaction_id=it.sales_return_id
    } else {
      $scope.source_type_id=4
      $scope.transaction_id=it.usage_return_id
    }
  });

  $scope.source=[
    {id:1,name:"Purchase Order"},
    {id:2,name:"Purchase Order Return"},
    {id:3,name:"Sales Order Return"},
    {id:4,name:"Usage Return"},
  ]

});
app.controller('opWarehousePalletReceiptCreate', function($scope, $http, $location, $rootScope,$location,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Add Receipt";
});
