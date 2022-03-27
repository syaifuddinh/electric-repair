app.controller('depoContainerYard', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('depo.container_yard.create')
    }
});

app.controller('depoContainerYardCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});