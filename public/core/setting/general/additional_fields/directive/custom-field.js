additionalFields.directive('customField', function () {
    return {
        restrict: 'E',
        scope: {
            'ngModel' : '=',
            'ngModelHeader' : '=',
            'ngModelFooter' : '=',
            'type' : '='
        },
        require : 'ngModel',
        template: "<div></div>",
        link: function (scope, el, attr, ngModel) {
            var input = $('<input />')
            input.addClass('form-control')
            $(el).append(input)
            input.attr('ng-model', 'modelValue')
            input.attr('ng-change', 'changed()')

            input.attr('type', 'text')
            if(scope.type == 'number') {
                input.attr('only-num', '')
            }
            scope.el = input
            scope.changed = function(model) {
                var val = scope.modelValue
                ngModel.$setViewValue(val)
            }

            ngModel.$render = function () {
                scope.modelValue = ngModel.$modelValue
            }

        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $timeout, contactsService) {
            $timeout(function(){
                $compile($scope.el)($scope)
            }, 400)
        }
    }
});