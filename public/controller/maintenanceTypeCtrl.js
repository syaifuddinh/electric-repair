app.controller('settingMaintenanceType', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Jenis Perawatan Kendaraan";
  $('.ibox-content').addClass('sk-loading');

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
        filename: 'Maintenance Type - ' + new Date,
        sheetName: 'Data',
        title: 'Maintenance Type',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/maintenance_type_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"type",name:"type"},
      {data:"interval",name:"interval",className:"text-right"},
      {data:"cost",name:"cost",className:"text-right"},
      {data:"is_repeat",name:"is_repeat",className:"text-center"},
      {data:"description",name:"description"},
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
      $http.delete(baseUrl+'/setting/maintenance_type/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  $scope.formData={};
  $scope.url="";
  $scope.types=[
    {id:1,name:"Time Based (Day)"},
    {id:2,name:"KM Based (Kilometer)"},
  ];
  $scope.create=function() {
    $scope.modalTitle="Tambah Jenis Perawatan Kendaraan";
    $scope.formData={};
    $scope.formData.type=1;
    $scope.formData.is_repeat=1;
    $scope.formData.interval=0;
    $scope.formData.cost=0;
    $scope.url=baseUrl+'/setting/maintenance_type?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Jenis Perawatan Kendaraan";
    $http.get(baseUrl+'/setting/maintenance_type/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.name=$scope.item.name;
      $scope.formData.type=$scope.item.type;
      $scope.formData.is_repeat=$scope.item.is_repeat;
      $scope.formData.interval=$scope.item.interval;
      $scope.formData.cost=$scope.item.cost;
      $scope.formData.description=$scope.item.description;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/maintenance_type/'+ids+'?_method=PUT&_token='+csrfToken;
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
        oTable.ajax.reload();
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
