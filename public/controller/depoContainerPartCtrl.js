app.controller('depoContainerPart', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('depo.container_part.create')
    }
});

app.controller('depoContainerPartCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});