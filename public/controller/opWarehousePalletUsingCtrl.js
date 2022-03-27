app.controller('opWarehousePalletUsing', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.pallet_usage.title;
});

app.controller('opWarehousePalletUsingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.pallet_usage.title;
});

app.controller('opWarehousePalletUsingShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Penggunaan Pallet";

  $http.get(baseUrl+'/operational_warehouse/pallet_using/'+$stateParams.id).then(function(data){
    $scope.data=data.data
  },function(error){
    console.log(error)
  })

  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">Pengajuan</span>'},
    {id:2,name:'<span class="badge badge-success">Storage Used</span>'},
    {id:3,name:'<span class="badge badge-info">Shipping Used</span>'},
  ]

  $scope.approve=function() {
    var cofs=confirm("Apakah anda ingin melakukan konfirmasi penggunaan barang ? jumlah barang akan mengurangi stok");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/operational_warehouse/pallet_using/approve/'+$stateParams.id).then(function(data){
      $state.reload()
      toastr.success("Penggunaan Telah Dikonfirmasi")
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
    })
  }
  $scope.shipping=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/operational_warehouse/pallet_using/shipping/'+$stateParams.id).then(function(data){
      $state.reload()
      toastr.success("Data Berhasil Disimpan")
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
    })
  }
});
