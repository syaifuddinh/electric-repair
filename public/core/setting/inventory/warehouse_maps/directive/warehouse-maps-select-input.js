warehouseMaps.directive('warehouseMapsSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            ngDisabled : '=ngDisabled'
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.map' ng-disabled='ngDisabled' rows='list'></solog-select>",
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
            }

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, warehouseMapsService) {
            $scope.chosen = null
            $scope.list = []
            $scope.show = function() {
                if($scope.warehouse_id) {
                    warehouseMapsService.api.index($scope.warehouse_id, function(list){
                        $scope.list = list
                    })
                }
            }

            $scope.$watch('warehouse_id', function(){
                $scope.show()
            })
        }
    }
});