app.controller('settingCity', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Wilayah/Kota";
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
      filename: 'Wilayah / Kota',
      sheetName: 'Data',
      title: 'Wilayah / Kota',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/city_datatable',
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"province.country.name",name:"province.country.name"},
      {data:"province.name",name:"province.name"},
      {data:"name",name:"name"},
      {data:"type",name:"type"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.delivery.region.edit')) {
          $(row).find('td').attr('ui-sref', 'setting.city.edit({id:' + data.id + '})')
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
      $http.delete(baseUrl+'/setting/city/'+id,{_token:csrfToken}).then(function success(data) {
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

app.controller('settingCityCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Wilayah/Kota";
  $('.ibox-content').addClass('sk-loading');

  $scope.showCountry = function() {
      $http.get(baseUrl+'/setting/country').then(function(data) {
        $scope.country=data.data;
      });
  }
  $scope.showCountry() 

    $scope.changeProvince = function() {
        if($scope.formData.country_id) {
            $scope.province = $scope.data.province.filter(x => x.country_id == $scope.formData.country_id)
        } else {
            $scope.province = $scope.data.province
        }
    }


  $scope.show = function() {
     if($stateParams.id) {
        $http.get(baseUrl+'/setting/city/' + $stateParams.id).then(function(data) {
            $scope.formData=data.data;
            $scope.changeProvince()
            $scope.setCountry()
        });        
     }
  }

    $scope.setCountry = function() {
        if(!$scope.formData.country_id) {
            var province = $scope.data.province.find(x => x.id == $scope.formData.province_id)
            if(province) {
                $scope.formData.country_id = province.country_id 
                $scope.changeProvince()
            }
        }
    }

  $scope.showData = function() {
      $http.get(baseUrl+'/setting/city/create').then(function(data) {
        $scope.data=data.data;
        $scope.province=data.data.province;
        $scope.show()
        $('.ibox-content').removeClass('sk-loading');
      });
  }
  $scope.showData()


  $scope.submitForm=function() {
    var method = 'post'
    var url = baseUrl+'/setting/city?_token='+csrfToken
    if($stateParams.id) {
        var method = 'put'
        var url = baseUrl+'/setting/city/' + $stateParams.id + '?_token='+csrfToken    
    }
    $http[method](url, $scope.formData).then(function(data) {
      if (data.status>=200 && data.status < 300) {
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.city');
      } else {
        toastr.error("Error Has Found !");
      }
    });
  }
});
