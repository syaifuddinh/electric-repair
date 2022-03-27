app.controller('settingStatusProses', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Status Proses";

  $http.get(baseUrl+'/setting/status_proses').then(function(data) {
    $scope.detail=data.data.detail;
    $scope.detailVisible=data.data.detail;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    lengthMenu:[[25,50,100,-1],[25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Status Proses - ' + new Date,
        sheetName: 'Data',
        title: 'Status Proses',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/setting/status_proses?dt=true',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"description",name:"description"},
      {data:"service_type.name",name:"service_type.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.operational.status_proses')) {
        $(row).find('td').attr('ui-sref', 'setting.status_proses.show({id:' + data.id + '})')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.upward = function() {
    $('body').animate({scrollTop:0}, 500);
  }

  $scope.filterData = function() {
      var keyword = $scope.keyword.toLowerCase()
      var detail = $scope.detail
      var hasName, hasDescription, hasServiceType
      if(keyword) {
          detail = detail.filter(function(x){
              hasName =x.name.toLowerCase().search(keyword)
              if(x.description) {
                  hasDescription =x.description.toLowerCase().search(keyword)
              }
              hasServiceType =x.service_type.name.toLowerCase().search(keyword)

              if(x.description) {
                  return (hasName > -1) || (hasServiceType > -1) || (hasDescription > -1)
              } else {
                  return (hasName > -1) || (hasServiceType > -1)
              }
          })
          $scope.detailVisible = detail
      } else {
          $scope.detailVisible = $scope.detail
      }
  }
});

app.controller('settingStatusProsesShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Status Proses";
  $http.get(baseUrl+'/setting/status_proses/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail_service=data.data.detail_service;
  });
  $scope.formData={};
  $scope.is_done=[
    {id:1,name:"YA"},
    {id:0,name:"TIDAK"},
  ];
  $scope.status=[
    {id:1,name:"Mulai"},
    {id:2,name:"Proses"},
    {id:3,name:"Tidak Dihitung"},
    {id:4,name:"Selesai"},
  ];
  $scope.stMulai=false
  $scope.stProses=false
  $scope.stSelesai=false

  $scope.changeDone=function(formData) {
    if (formData.is_done) {
      $scope.stMulai=true
      $scope.stProses=true
      $scope.stSelesai=false
      formData.status=4
    } else {
      if ($rootScope.in_array(formData.status,[1,4])) {
        formData.status=2
      }
      if (formData.sort_number==1) {
        $scope.stMulai=false
        $scope.stProses=true
        $scope.stSelesai=true
      } else {
        $scope.stMulai=true
        $scope.stProses=false
        $scope.stSelesai=true
      }
    }
  }

  $scope.creates=function() {
    $scope.formData={};
    $scope.formData.service_id=$stateParams.id;
    $scope.formData.is_done=0;
    $scope.formData.status=2;
    $scope.modalTitle="Tambah Proses";
    $scope.urls=baseUrl+'/setting/status_proses';
    $scope.changeDone($scope.formData)
    $('#modal').modal('show');
  }
  $scope.edits=function(id) {
    $scope.formData={};
    $scope.urls=baseUrl+'/setting/status_proses/'+id+'?_method=PUT';
    $http.get(baseUrl+'/setting/status_proses/'+id+'/edit').then(function(data) {
      var dt=data.data.item;
      $scope.formData.name=dt.name;
      $scope.formData.sort_number=dt.sort_number;
      $scope.formData.duration=dt.duration;
      $scope.formData.service_id=dt.service_id;
      $scope.formData.is_done=dt.is_done;
      $scope.formData.status=dt.status;
      $scope.modalTitle="Edit Proses";
      $scope.changeDone($scope.formData)
      $('#modal').modal('show');
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post($scope.urls,$scope.formData).then(function(data) {
      $('#modal').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Data Berhasil Disimpan!");
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

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/status_proses/'+ids,{_token:csrfToken}).then(function success(data) {
        $state.reload();
        // oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }


});
