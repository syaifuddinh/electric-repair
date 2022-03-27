receiptSales.controller('receiptSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.warehouse_receipt.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.receipt.create')
    }
});