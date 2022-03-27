units.directive('unitsSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled'
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.unit' ng-disabled='ngDisabled' rows='list'></solog-select>",
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
                if(scope.chosen == null) {
                    scope.chosen = scope.setDefault()
                    scope.change()
                }
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
            }

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, unitsService) {
            $scope.chosen = null
            $scope.list = []
            unitsService.api.index(function(list){
                $scope.list = list
            })

            $scope.setDefault = function() {
                var r = $rootScope.settings.work_order.default_piece_id
                return r
            }
        }
    }
});