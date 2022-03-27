app.controller('settingVehicleOwner', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Kepemilikan Kendaraan";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm',
        filename: 'Vehicle Owner - ' + new Date,
        sheetName: 'Data',
        title: 'Vehicle Owner',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/vehicle_owner_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {data:"action",name:"action", sorting:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/vehicle_owner/'+ids,{_token:csrfToken}).then(function success(data) {
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
    $scope.modalTitle="Tambah Kepemilikan Kendaraan";
    $scope.formData={};
    $scope.url=baseUrl+'/setting/vehicle_owner?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Kepemilikan Kendaraan";
    $http.get(baseUrl+'/setting/vehicle_owner/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.name=$scope.item.name;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/vehicle_owner/'+ids+'?_method=PUT&_token='+csrfToken;
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
