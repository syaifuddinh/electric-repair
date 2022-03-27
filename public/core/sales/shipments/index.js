shipmentSales.controller('shipmentSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, shipmentSalesService) {
    $rootScope.pageTitle = $rootScope.solog.label.general.shipment

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.shipment.create')
    }
});