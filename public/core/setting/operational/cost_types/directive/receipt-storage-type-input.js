warehouseReceipts.directive('receiptStorageTypeInput', function () {
    return {
        restrict: 'E',
        scope : {
            type : '=?type'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/receipt-storage-type-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel']) {
                return false
            }


            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
            }

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
                if(scope.chosen == null) {
                    setTimeout(function () {
                        scope.chosen = scope.setDefault()
                        scope.change()
                    }, 400)
                }
            }
            scope.$on('download', function(e, v){
                scope.change()
            })

        },
        controller: function ($scope, $http, $attrs, $rootScope, contactsService, $timeout, $rootScope) {
            if(!$scope.type) {
                $scope.type = 'radio'
            }
            
            $scope.setDefault = function() {
                var r = $rootScope.settings.good_receipt.default_storage_type
                return r
            }
        }
    }
});