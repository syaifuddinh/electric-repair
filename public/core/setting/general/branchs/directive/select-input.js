branchs.directive('branchSelectInput', function () {
    return {
        restrict: 'E',
        scope: {},
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/general/branchs/view/select-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            scope.chosen = null

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
                if(scope.chosen == null) {
                    scope.chosen = compId
                    scope.change()
                }
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
            }

            scope.ngModel = ngModel

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $timeout, $http, $attrs, $rootScope, branchsService) {
            $timeout(function(){
                if(!$scope.ngModel.$modelValue && !userProfile.is_admin) {
                    $scope.chosen = userProfile.company_id
                    $scope.change()
                }
            }, 400)
            $scope.companies = []
            branchsService.api.index(function(list){
                $scope.companies = list
            })
        }
    }
});