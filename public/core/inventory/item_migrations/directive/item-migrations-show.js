itemMigrations.directive('itemMigrationsShow', function () {
    return {
        restrict: 'E',
        scope: {
            'id' : '=id'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/item_migrations/view/item-migrations-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, itemMigrationsService) {
            if(!$scope.id) {
                return false;
            }
            
            $http.get(baseUrl+'/operational_warehouse/mutasi_transfer/'+ $scope.id).then(function(data){
                $scope.item=data.data.item
                $scope.detail=data.data.detail
            },function(error){
                console.log(error)
            })

            $scope.back = function() {
                if($rootScope.hasBuffer()) {
                    $rootScope.accessBuffer()
                } else {
                    $rootScope.emptyBuffer()
                    $state.go('operational_warehouse.mutasi_transfer')
                }
            }

            $scope.status = [
                {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
                {id:2,name:'<span class="badge badge-primary">Item Out (On Transit)</span>'},
                {id:3,name:'<span class="badge badge-info">Item Receipt (Done)</span>'},
            ]

          $scope.editDetail=function(json) {
            $('#editModal').modal()
            $scope.editData={}
            $scope.editData.id=json.id
            $scope.editData.qty=json.qty
          }

          $scope.deleteDetail=function(id) {
            var cofs=confirm("Apakah anda yakin ?")
            if (!cofs) {
              return false;
            }
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational_warehouse/mutasi_transfer/delete_detail/'+id).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Data Berhasil Dihapus !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
              if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                  det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
              } else {
                toastr.error(error.data.message,"Error Has Found !");
              }
            });
          }

          $scope.submitEdit=function() {
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational_warehouse/mutasi_transfer/store_detail',$scope.editData).then(function(data) {
              $('#editModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Data Berhasil Disimpan !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
              if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                  det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
              } else {
                toastr.error(error.data.message,"Error Has Found !");
              }
            });
          }

          $scope.approve=function() {
            var cofs=confirm("Apakah anda yakin ? barang akan dikeluarkan dalam gudang!")
            if (!cofs) {
              return false;
            }
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational_warehouse/mutasi_transfer/item_out/'+ $scope.id).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Data Berhasil Disimpan !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
              if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                  det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
              } else {
                toastr.error(error.data.message,"Error Has Found !");
              }
            });
          }
          $scope.item_in=function() {
            var cofs=confirm("Apakah anda yakin ? barang akan dimasukkan dalam gudang!")
            if (!cofs) {
              return false;
            }
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational_warehouse/mutasi_transfer/item_in/'+ $scope.id).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Data Berhasil Disimpan !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
              if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                  det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
              } else {
                toastr.error(error.data.message,"Error Has Found !");
              }
            });
          }
        }
    }
});