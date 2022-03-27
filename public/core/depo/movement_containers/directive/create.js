movementContainers.controller('movementContainersCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, movementContainersService) {
    if($stateParams.id) {
        $rootScope.pageTitle="Edit";
    } else {
        $rootScope.pageTitle="Create";
    }
    $scope.formData = {}
    $scope.formData.detail = []

    $scope.formData.date = dateNow;

    $scope.setId = function(id) {
        $scope.active_id = id
    }

    $scope.$on('getGateInContainer', function(e, v){
        var idx = $scope.formData.detail.findIndex(x => x.id == $scope.active_id)
        if(idx >= 0) {
            $scope.formData.detail[idx].no_container = v.no_container
        }
    })

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

    $scope.addDetail = function() {
        var params = {}
        params.id = Math.round(Math.random() * 999999999)
        params.container_yard_destination_id = null
        params.gate_in_container_id = null
        $scope.formData.detail.push(params)
    }

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('depo.movement_container')
        }
    }

    $scope.delete = function(id) {
        var detail = $scope.formData.detail.filter(x => x.id != id)
        $scope.formData.detail = detail
    }

    $rootScope.disBtn=false;
    $scope.submitForm=function() {
        $rootScope.disBtn = true;
        if($stateParams.id) {
            movementContainersService.api.update($scope.formData, $stateParams.id, function(){
                $scope.backward()
            })
        } else {
            movementContainersService.api.store($scope.formData, function(){
                $scope.backward()
            })
        }
    }
});