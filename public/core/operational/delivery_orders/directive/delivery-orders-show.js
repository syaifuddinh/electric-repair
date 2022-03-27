deliveryOrders.directive('deliveryOrdersShow', function () {
    return {
        restrict: 'E',
        scope: {
            'index_route' : '=indexRoute',
            'id' : '=id'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/delivery_orders/view/delivery-orders-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, manifestsService, additionalFieldsService, $stateParams) {
            $('.ibox-content').addClass('sk-loading');

            $scope.status=[
                {id:1, name:'<span class="badge badge-warning">Ditugaskan</span>'},
                {id:2, name:'<span class="badge badge-success">Selesai</span>'},
            ];
            
            $http.get(baseUrl+'/operational/delivery_order_driver/' + $scope.id).then(function(data) {
                $scope.data=data.data;
                $scope.item=data.data.item;

                $scope.$broadcast('updateRouteMap', $scope.data.tracking)
                $('.ibox-content').removeClass('sk-loading');
            })

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    $state.go('operational.delivery_order_driver')
                }
            }
        }
    }
});