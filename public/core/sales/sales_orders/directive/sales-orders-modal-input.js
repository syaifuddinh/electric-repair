salesOrders.directive('salesOrdersModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            forInvoicing : '=',
            customerId : '=',
            is_multiple : '=isMultiple'
        },
        require:'ngModel',
        templateUrl: '/core/sales/sales_orders/view/sales-orders-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false


            scope.$on('chooseSalesOrder', function(e, v){
                $('#sales_order_modal').modal('hide')
                ngModel.$setViewValue(v.id)
                scope.item_name = v.code
                scope.$emit('getSalesOrder', v)
            })

            scope.$on('chooseSalesOrders', function(e, v){
                $('#sales_order_modal').modal('hide')
                scope.$emit('getSalesOrders', v)
            })

            ngModel.$render = function () {
                var value = ngModel.$modelValue
                scope.show(value)
            }
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, itemsService) {
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

            $scope.show = function(id) {
                itemsService.api.show(id, function(dt){
                    $scope.item_name = dt.name
                })
            }

        }
    }
});