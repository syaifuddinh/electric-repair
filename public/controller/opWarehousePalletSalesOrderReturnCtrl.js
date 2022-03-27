app.controller('opWarehousePalletSalesOrderReturn', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Sales Order Return";
});

app.controller('opWarehousePalletSalesOrderReturnCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Create Sales Order Return";
});

app.controller('opWarehousePalletSalesOrderReturnCreateReceipt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order_return.title;
    if($stateParams.id) {
        $scope.id = $stateParams.id;
        $scope.storeUrl = baseUrl+'/operational_warehouse/pallet_sales_order_return/' + $stateParams.id + '/receipt'
    }
})

app.controller('opWarehousePalletSalesOrderReturnShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $scope.id = $stateParams.id
    $rootScope.pageTitle="Detail Sales Order Return";

    $http.get(baseUrl+'/operational_warehouse/pallet_sales_order_return/'+$stateParams.id).then(function(data){
        $scope.item=data.data.item
        $scope.detail=data.data.detail
    },function(error){
        console.log(error)
    })

    $scope.status=[
        {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
        {id:2,name:'<span class="badge badge-primary">Sales Order Return</span>'},
        {id:3,name:'<span class="badge badge-info">Item Return (Done)</span>'},
    ]

    $scope.openInfo = function() {
        $('.tab-item').hide()
        $('#info_detail').show()
    }
    $scope.openInfo()

    $scope.openReceipt = function() {
        $('.tab-item').hide()
        $('#receipt_detail').show()
    }
})
