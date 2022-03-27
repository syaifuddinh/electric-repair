branchs.directive('contactSelectInput', function () {
    return {
        restrict: 'E',
        scope: {},
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/general/contacts/view/select-input.html',
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
        controller: function ($scope, $http, $attrs, $rootScope, contactsService) {
            $scope.chosen = null
            $scope.list = []
            contactsService.api.index(function(list){
                $scope.list = list
            })
        }
    }
});