app.controller('opWarehousePalletSalesOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Sales Order";
    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go("operational_warehouse.pallet_sales_order.create")
    }
});

app.controller('opWarehousePalletSalesOrderCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Pallet Sales Order";
});
app.controller('opWarehousePalletSalesOrderShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Sales Order";
    $rootScope.job_order.is_pallet = 1
})
