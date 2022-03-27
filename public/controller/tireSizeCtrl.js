app.controller('settingTireSize', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Ukuran Ban";

  $http.get(baseUrl+'/setting/tire_size').then(function(data) {
    $scope.data=data.data;
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/tire_size/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        $state.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  $scope.formData={};
  $scope.url="";
  $scope.create=function() {
    $scope.modalTitle="Tambah Ukuran Ban";
    $scope.formData={};
    $scope.url=baseUrl+'/setting/tire_size?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Ukuran Ban";
    $http.get(baseUrl+'/setting/tire_size/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.name=$scope.item.name;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/tire_size/'+ids+'?_method=PUT&_token='+csrfToken;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: $scope.url,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#modal').modal('hide');
        $timeout(function() {
          $state.reload();
        },1000)
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+=val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
