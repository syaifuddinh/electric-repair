app.controller('settingVehicleType', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, vehicleTypesService) {
  $rootScope.pageTitle = $rootScope.solog.label.vehicle_type.title;

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
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Tipe Kendaraan',
        sheetName: 'Data',
        title: 'Tipe Kendaraan',
        exportOptions: {
          rows: {
            selected: true
          }
        },
      }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/vehicle_type_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {data:"type_name",searchable:false, orderable:false},
      {data:"action",name:"action", sorting:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.vehicle.vehicle_type.edit')) {
          $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
          $(row).find('td:last-child').removeAttr('ng-click')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')
  $compile($('thead'))($scope)


  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(vehicleTypesService.url.destroy(ids), {_token:csrfToken}).then(function success(data) {
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
        $scope.modalTitle = $rootScope.solog.label.general.add
        $scope.formData={type : 1};
        $scope.url = vehicleTypesService.url.store()
        $('#modal').modal('show');
    }

    $scope.edit=function(ids) {
        $scope.modalTitle = $rootScope.solog.label.general.add;
        $http.get( vehicleTypesService.url.show(ids) ).then(function(data) {
            $scope.item=data.data;
            // startdata
            $scope.formData.name=$scope.item.name;
            $scope.formData.type=$scope.item.type;
            // endata
            $('#modal').modal('show');
        });
        $scope.url = vehicleTypesService.url.update(ids)
    }

  $scope.submitForm=function() {
    $rootScope.disBtn=true;
    $.ajax({
      type: "post",
      url: $scope.url,
      data: $scope.formData,
      beforeSend: function (request) {
            request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
       },
      success: function(data){
        $rootScope.$apply(function() {
          $rootScope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#modal').modal('hide');
        oTable.ajax.reload()
      },
      error: function(xhr, response, status) {
        $rootScope.$apply(function() {
          $rootScope.disBtn=false;
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
