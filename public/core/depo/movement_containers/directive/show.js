movementContainers.controller('movementContainersShow', function ($scope, $http, $rootScope, $filter, $state, $stateParams, $timeout, $compile, movementContainersService) {    

    $scope.showDetail = function() {
        if($stateParams.id) {
            movementContainersService.api.showDetail($stateParams.id, function(dt){
                $scope.formData.detail = dt
            })
        }
    }

    $scope.show = function() {
        if($stateParams.id) {
            movementContainersService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showDetail()
            })
        }
    }
    $scope.show()

    $rootScope.disBtn = false
    $scope.approve = function() {
        is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            movementContainersService.api.approve($stateParams.id, function(dt){
                $scope.show()
            })        
        }
    }

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('depo.movement_container')
        }
    }
});