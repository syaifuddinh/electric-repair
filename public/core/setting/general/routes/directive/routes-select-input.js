routes.directive('routesSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled'
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.route' ng-disabled='ngDisabled' rows='list'></solog-select>",
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
        controller: function ($scope, $http, $attrs, $rootScope, $compile, routesService) {
            $scope.chosen = null
            $scope.list = []
            routesService.api.index(function(list){
                $scope.list = list
            })


        }
    }
});