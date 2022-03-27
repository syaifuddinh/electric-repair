gateInContainers.controller('gateInContainersShow', function ($scope, $http, $rootScope, $filter, $state, $stateParams, $timeout, $compile, gateInContainersService) {    

    $scope.show = function() {
        if($stateParams.id) {
            gateInContainersService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
            })
        }
    }
    $scope.show()

    $rootScope.disBtn = false
    $scope.approve = function() {
        is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            gateInContainersService.api.approve($stateParams.id, function(dt){
                $scope.show()
            })        
        }
    }

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('depo.gate_in_container')
        }
    }
});