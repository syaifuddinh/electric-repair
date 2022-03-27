app.controller('inventoryUsingItem', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.item_usage.title ;
});

app.controller('inventoryUsingItemCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, itemUsagesService) {
    $rootScope.pageTitle= $rootScope.solog.label.general.add ;
});
app.controller('inventoryUsingItemShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.detail;
    $scope.totalHarga=0;

    $scope.show = function() {
        $http.get(baseUrl+'/inventory/using_item/'+$stateParams.id).then(function(data) {
            $scope.item=data.data.item;
            $scope.detail=data.data.detail;
            angular.forEach(data.data.detail,function(val,i) {
                  $scope.totalHarga+=val.total;
            });
        });
    }
    $scope.show()

    $scope.back = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('inventory.using_item')
        }
    }

    $scope.approve = function() {
        is_confirm = confirm('Apakah anda yakin ?')
        if(is_confirm) {
            $scope.disBtn = true
            $http.put(baseUrl+'/inventory/using_item/' + $stateParams.id + '/approve').then(function(data){
                $scope.disBtn = false
                toastr.success('Data berhasil di-approve')
                $state.reload()
            },function(error){
                $scope.disBtn = false
                if (error.status==422) {
                    var det="";
                    angular.forEach(error.data.errors,function(val,i) {
                        det+="- "+val+"<br>";
                    });
                    toastr.warning(det,error.data.message);
                } else {
                    toastr.error(error.data.message,"Error Has Found !");
                }
            })
        }
    }

    $scope.back = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('inventory.using_item')
        }
    } 
});
