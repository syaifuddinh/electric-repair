app.controller('settingProvince', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Province";
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
      filename: 'Province',
      sheetName: 'Data',
      title: 'Province',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/province_datatable',
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"country_name",name:"countries.name"},
      {data:"name",name:"provinces.name"},
      {
        data:null,
        searchable:false,
        orderable:false,
        className:'text-center',
        render:function(resp) {
            var r = ''
            r += "<a ui-sref=\"setting.province.edit({id:" + resp.id + "})\"><span class='fa fa-edit' data-toggle='tooltip' title='edit data'></span></a>&nbsp;&nbsp;"
            r += "<a ng-click=\"delete(" + resp.id + ")\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus data'></span></a>"

            return r
        }
    },
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.delivery.region.edit')) {
          $(row).find('td').attr('ui-sref', 'setting.province.edit({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo( '.ibox-tools' );

  $scope.delete=function(id) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/province/'+id,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(xhr) {
          xhr.responseJSON = xhr.data
          if (xhr.status==422) {
            var msgs="";
            $.each(xhr.responseJSON.errors, function(i, val) {
              msgs+=val+'<br>';
            });
            toastr.warning(msgs,"Validation Error!");
          } else {
            toastr.error(xhr.responseJSON.message,"Error has Found!");
          }  
      });
    }
  }
});

app.controller('settingProvinceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Province";
  $scope.formData = {}
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/city/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
          $scope.emptyBuffer()
          $state.go('setting.route')
        }
      }


  $scope.show = function() {
      if($stateParams.id) {
          $http.get(baseUrl+'/setting/province/' + $stateParams.id).then(function(data) {
            $scope.formData=data.data;
            $scope.changeCityFrom()
            $scope.changeCityTo()
          });    
      }
  }
  $scope.show()

  $scope.submitForm=function() {
    $scope.disBtn = true
    var method = 'post'
    var url = baseUrl+'/setting/province'
    if($stateParams.id) {
        method = 'put'
        url = baseUrl+'/setting/province/' + $stateParams.id        
    }
    $http[method](url, $scope.formData).then(function(data) {
      toastr.success("Data Berhasil Disimpan!");
      $scope.disBtn=false;
      if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $state.go('setting.province');
        }
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
});
app.controller('settingProvinceEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Province";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/province/'+$stateParams.id+'/edit').then(function(data) {
    
    $scope.data=data.data;
    $scope.formData={
      code:data.data.item.code,
      name:data.data.item.name,
      province_id:data.data.item.province_id,
      type:data.data.item.type,
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.submitForm=function() {
    $http.post(baseUrl+'/setting/province/'+$stateParams.id+'?_method=PUT&_token='+csrfToken, $scope.formData).then(function(data) {
      if (data.status>=200 && data.status < 300) {
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.province');
      } else {
        toastr.error("Error Has Found !");
      }
    });
  }
});
