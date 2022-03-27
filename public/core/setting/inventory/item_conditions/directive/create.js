itemConditions.controller('itemConditionsCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, itemConditionsService) {
    $rootScope.pageTitle="Create Conditions";
    $scope.formData = {}

    $scope.show = function() {
        if($stateParams.id) {
            itemConditionsService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
            })
        }
    }
    $scope.show()

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('inventory.item_condition')
        }
    }

    $rootScope.disBtn=false;
    $scope.submitForm=function() {
        $rootScope.disBtn = true;
        if($stateParams.id) {
            itemConditionsService.api.update($scope.formData, $stateParams.id, function(){
                $scope.backward()
            })
        } else {
            itemConditionsService.api.store($scope.formData, function(){
                $scope.backward()
            })
        }
    }
});