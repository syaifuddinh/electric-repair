containerInspections.controller('containerInspectionsCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, containerInspectionsService) {
    if($stateParams.id) {
        $rootScope.pageTitle="Edit";
    } else {
        $rootScope.pageTitle="Create";
    }
    $scope.formData = {}
    $scope.formData.detail = []

    $scope.formData.date = dateNow;

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
            $state.go('depo.container_inspection')
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
            containerInspectionsService.api.update($scope.formData, $stateParams.id, function(){
                $scope.backward()
            })
        } else {
            containerInspectionsService.api.store($scope.formData, function(){
                $scope.backward()
            })
        }
    }
});