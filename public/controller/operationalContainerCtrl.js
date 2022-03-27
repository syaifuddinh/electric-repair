app.controller('operationalContainer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
});

app.controller('operationalContainerCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});

app.controller('operationalContainerShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Kontainer";
    $('.ibox-content').addClass('sk-loading');

    $http.get(baseUrl+'/operational/container/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $scope.emptyBuffer()
            $state.go('operational.container')
        }
    }

    $scope.type=[
        {id:0,name:'FTL'},
        {id:1,name:'FCL'},
    ]

});
app.controller('operationalContainerEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Edit Container";
});
