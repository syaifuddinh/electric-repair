voyageSchedules.directive('voyageSchedulesShow', function () {
    return {
        restrict: 'E',
        scope: {
            id : "=id"
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/voyage_schedules/view/voyage-schedules-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, voyageSchedulesService) {    
            $('.ibox-content').addClass('sk-loading');
            $http.get(baseUrl+'/operational/voyage_schedule/'+$scope.id).then(function(data) {
                $scope.item=data.data.item;
                $scope.detail=data.data.detail;
                $('.ibox-content').removeClass('sk-loading');
            });

        }
    }
});