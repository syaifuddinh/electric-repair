salesOrders.controller('salesOrders', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, salesOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order.title
    $scope.add = function() {
        $state.go('sales_order.sales_order.create')
    }

    $scope.edit = function() {
        $scope.modalTitle = $rootScope.solog.label.general.edit + ' ' + $rootScope.solog.label.sales_orders.title
        $('#modal').modal()
    }

});