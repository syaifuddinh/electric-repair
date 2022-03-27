costTypes.directive('costTypesSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            type : '=type'
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.cost' ng-disabled='ngDisabled' rows='list'></solog-select>",
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
        controller: function ($scope, $http, $attrs, $rootScope, costTypesService) {
            $scope.chosen = null
            $scope.list = []
            var params = {}
            var type = $scope.type
            if(type == 'operasional') {
                params.is_operasional = 1
            }

            if(type == 'invoice') {
                params.is_invoice = 1
            }
            costTypesService.api.index(params, function(list){
                $scope.list = list
            })
        }
    }
});