racks.directive('racksSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            warehouse_id : '=warehouseId'
        },
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.rack' ng-disabled='ngDisabled' rows='list'></solog-select>",
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
                if(!scope.chosen && !scope.warehouse_id) {
                    scope.ngDisabled = true
                } else {
                    scope.ngDisabled = false
                }
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
                scope.show()
                $scope.ngDisabled = false
            }

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, racksService) {
            $scope.formData = {}
            $scope.chosen = null
            $scope.list = []

            if($attrs.warehouseId) {
                $scope.$watch('warehouse_id', function(){
                    $scope.formData.warehouse_id = $scope.warehouse_id
                    $scope.index()
                })
            }

            $scope.index = function() {
                racksService.api.index($scope.formData, function(list){
                    $scope.list = list
                })
            }
            $scope.index()

            $scope.show = function() {
                if($attrs.allowGetRack) {
                    racksService.api.show($scope.chosen, function(dt){
                        $scope.$emit('getRack', dt)
                    })
                }
            }
        }
    }
});