gateInContainers.controller('gateInContainersCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, gateInContainersService) {
    if($stateParams.id) {
        $rootScope.pageTitle="Edit";
    } else {
        $rootScope.pageTitle="Create";
    }
    $scope.formData = {}
    $scope.formData.detail = []

    $scope.formData.date = dateNow;

    $scope.show = function() {
        if($stateParams.id) {
            gateInContainersService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showDetail()
            })
        }
    }
    $scope.show()

    $scope.addDetail = function() {
        var params = {}
        params.id = Math.round(Math.random() * 999999999)
        params.container_yard_id = null
        params.item_condition_id = null
        $scope.formData.detail.push(params)
    }

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('depo.gate_in_container')
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
            gateInContainersService.api.update($scope.formData, $stateParams.id, function(){
                $scope.backward()
            })
        } else {
            gateInContainersService.api.store($scope.formData, function(){
                $scope.backward()
            })
        }
    }
});