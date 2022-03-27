
shipmentSales.controller('shipmentSalesShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, shipmentSalesService) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order.title;
    $scope.formData = {}
    $scope.isShipment = true


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
    
    $scope.show = function() {
        shipmentSalesService.api.show($stateParams.id, function(dt) {
            $scope.formData = dt
            $rootScope.customer_id = dt.customer_id
            $rootScope.job_order.showDetail(dt.job_order_id)
        })
    }
    $scope.show()
    $scope.add = $rootScope.job_order.addItem
    $scope.job_order.addItem = function() {
        $scope.add()
        $rootScope.itemData.is_warehouse = 1
    }

    $timeout(function(){
        $compile($('#modalStatus'))($scope)
    }, 300)
});