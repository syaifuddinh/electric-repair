salesOrders.controller('salesOrdersShowShipment', function ($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, salesOrdersService) {
    $scope.route_id = $stateParams.id
    $scope.shipment_id = $stateParams.id_shipment
    
    $scope.openInfo = function() {
        $('.tab-item').hide()
        $('#info_detail').show()
    }

    $timeout(function() {
        $scope.openInfo()
    }, 500)

    $scope.openDeliveryOrderDriver = function() {
          $('.tab-item').hide()
          $('#delivery_order_driver_detail').show()
    }


    $scope.$on('getDeliveryOrderId', function(e, v){
        if(v) {
            $scope.delivery_order_driver_id = v
        }
    })
});