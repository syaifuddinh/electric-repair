salesOrders.directive('salesOrderDetailsModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            customerId : '=',
            is_multiple : '=isMultiple',
            is_pallet : '=isPallet'
        },
        require:'ngModel',
        templateUrl: '/core/sales/sales_orders/view/sales-order-details-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false


            scope.$on('chooseSalesOrderDetail', function(e, v){
                $('#sales_order_modal').modal('hide')
                scope.sales_order_id = v.header_id
                ngModel.$setViewValue(v.id)
                scope.item_name = v.code
                scope.$emit('getSalesOrderDetail', v)
            })

            scope.$on('chooseSalesOrderDetails', function(e, v){
                $('#sales_order_modal').modal('hide')
                scope.$emit('getSalesOrderDetails', v)
            })

            ngModel.$render = function () {
                var value = ngModel.$modelValue
            }
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, salesOrdersService) {
            $scope.chosen = null
            $scope.list = []

            if($attrs.buttonLabel) {
                $scope.button_label = $attrs.buttonLabel
            }
            if(!$scope.button_label) {
                $scope.button_label = $rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.general.item
            }

            $scope.adjustField = function() {
                $scope.hide_input = false
                $scope.hide_button = false

                if($attrs.type == 'button') {
                    $scope.hide_input = true
                } else {
                    $scope.hide_button = true
                }
            }
            $scope.adjustField()

            $scope.adjustWatch = function() {
                if($attrs.customerId) {
                    $scope.$watch('customerId', function(){
                        var params = {}
                        params.customer_id = $scope.customerId
                        $timeout(function(){
                            $scope.$broadcast('reloadSalesOrder', params)
                        }, 400)
                    })
                }
            }
            $scope.adjustWatch()

            $scope.cariItem=function() {        
                $('#sales_order_modal').modal()
                $scope.$broadcast('reloadSalesOrders', 0)
                $timeout(function() {
                    $('.modal-backdrop').removeClass('in')
                    $('.modal-backdrop').remove()
                }, 400)
            }

            $scope.show = function(id, sales_order_detail_id) {
                salesOrdersService.api.showDetailInfo(id, sales_order_detail_id, function(dt){
                    $scope.item_name = dt.name
                })
            }

        }
    }
});