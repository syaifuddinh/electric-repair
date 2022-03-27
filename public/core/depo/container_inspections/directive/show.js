containerInspections.controller('containerInspectionsShow', function ($scope, $http, $rootScope, $filter, $state, $stateParams, $timeout, $compile, containerInspectionsService) {    

    $scope.showDetail = function() {
        if($stateParams.id) {
            containerInspectionsService.api.showDetail($stateParams.id, function(dt){
                $scope.formData.detail = dt
            })
        }
    }

    $scope.show = function() {
        if($stateParams.id) {
            containerInspectionsService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showDetail()
            })
        }
    }
    $scope.show()

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('depo.container_inspection')
        }
    }
});