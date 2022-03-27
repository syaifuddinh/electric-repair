warehouses.directive('warehousesSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            company_id : '=companyId'
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.warehouse' ng-disabled='ngDisabled' rows='list'></solog-select>",
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
                scope.showItem(scope.chosen)
            }

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, warehousesService) {
            $scope.chosen = null
            $scope.list = []
            $scope.formData = {}
            if($attrs.companyId) {
                $scope.$watch('company_id', function(){
                    $scope.formData.company_id = $scope.company_id
                    $scope.show()
                })
            }
            $scope.show = function() {
                warehousesService.api.index($scope.formData, function(list){
                    $scope.list = list
                })
            }
            $scope.show()

            $scope.showItem = function(id) {
                warehousesService.api.show(id, function(dt){
                    $scope.$emit('getWarehouse', dt)
                })
            }
        }
    }
});