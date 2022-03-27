solog.directive('sologSelect', function () {
    return {
        restrict: 'E',
        scope: {
            rows : '=',
            title : '=',
            ngModel : '=',
            ngDisabled : '='
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/base/select.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return ;



            ngModel.$render = function () {
                scope.field = ngModel.$modelValue
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.field)
                scope.$emit('download', scope.field)
            }
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile) {
        }
    }
});