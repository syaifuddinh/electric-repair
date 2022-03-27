customerOrders.controller('customerOrders', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, customerOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.customer_order.title
    $scope.add = function() {
        $state.go('sales_order.customer_order.create')
    }

    $scope.edit = function() {
        $scope.modalTitle = $rootScope.solog.label.general.edit + ' ' + $rootScope.solog.label.customer_orders.title
        $('#modal').modal()
    }

});