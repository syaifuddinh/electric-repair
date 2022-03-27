racks.directive('racksCreate', function(){
    return {
        restrict: 'E',
        scope : false,
        templateUrl : '/core/setting/inventory/racks/view/create.html',
        controller : function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, racksService, $filter) {
        }
    }
});