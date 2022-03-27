app.controller('settingCompanyIndex', function($scope, $http, $rootScope,$state,$timeout,$compile) {
  $rootScope.pageTitle='Branch';
  $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'Blfrtip',
    order: [],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    buttons: [
        {
            extend : 'excel',
            enabled : true,
            action: newExportAction,
            text : '<span class="fa fa-file-excel-o"></span> Export Excel',
            className : 'btn btn-default btn-sm pull-right',
            filename : 'Cabang',
            sheetName : 'Data',
            title : 'Cabang',
            exportOptions: {
              rows: { selected: true }
            },
        },
    ],
    ajax : {
      url : baseUrl+'/api/setting/company_datatable',
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      dataSrc: function (d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"area.name",name:"area.name"},
      {data:"code",name:"companies.code"},
      {data:"name",name:"companies.name"},
      {data:"address",name:"companies.address"},
      {data:"action",name:"action", sorting:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.company.detail')) {
          $(row).find('td').attr('ui-sref', 'setting.company.show.info({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }

  });

  oTable.buttons().container().appendTo( '.ibox-tools' );
  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/company/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});

app.controller('settingCompanyCreate', function($scope,$http,$rootScope,$state,$timeout,$compile) {
  $rootScope.pageTitle='Add Branch';
  $scope.formData = {
    _token : csrfToken
  };
  $scope.data = {}

    $scope.showAccount = function() {
        $http.get(baseUrl+'/setting/account').then(function(data) {
            $scope.data.account=data.data.account;
        }, function(){
            $scope.showAccount()
        });
    }
    $scope.showAccount()


    $scope.showArea = function() {
        $http.get(baseUrl+'/setting/area').then(function(data) {
            $scope.data.area = data.data.data;
        }, function(){
            $scope.showArea()
        });
    }
    $scope.showArea()

    $scope.showArea = function() {
        $http.get(baseUrl+'/setting/area').then(function(data) {
            $scope.data.area = data.data.data;
        }, function(){
            $scope.showArea()
        });
    }
    $scope.showArea()

    $scope.showCity = function() {
        $http.get(baseUrl+'/setting/area').then(function(data) {
            $scope.data.city = data.data;
        }, function(){
            $scope.showCity()
        });
    }
    $scope.showCity()

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/company?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.company');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
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
app.controller('settingCompanyEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Edit Branch';

  // Loading dan enable tooltips
  $scope.isLoading = true;
  $('.ibox-content').toggleClass('sk-loading');

  $http({method: 'GET', url: baseUrl+'/setting/company/'+$stateParams.id+'/edit'})
  .then(function successCallback(data, status, headers, config) {
    
    $scope.data=data.data;
    $scope.formData = {
      _token : csrfToken,
      code : data.data.item.code,
      area_id : data.data.item.area_id,
      city_id : data.data.item.city_id,
      name : data.data.item.name,
      address : data.data.item.address,
      phone : data.data.item.phone,
      email : data.data.item.email,
      website : data.data.item.website,
      plafond : data.data.item.plafond,
      rek_no_1 : data.data.item.rek_no_1,
      rek_name_1 : data.data.item.rek_name_1,
      rek_bank_1 : data.data.item.rek_bank_1,
      rek_no_2 : data.data.item.rek_no_2,
      rek_name_2 : data.data.item.rek_name_2,
      rek_bank_2 : data.data.item.rek_bank_2,
      cash_account_id : data.data.item.cash_account_id,
      bank_account_id : data.data.item.bank_account_id,
      mutation_account_id : data.data.item.mutation_account_id,
      is_pusat : data.data.item.is_pusat,
      _method : 'PUT'
    };

    $scope.isLoading = false;
    $('.ibox-content').toggleClass('sk-loading');
  },
  function errorCallback(data, status, headers, config) {

  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/company/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.company');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
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

app.controller('settingCompanyShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Branch Detail';

  // Loading
  $scope.isLoading = true;
  $('#detailInfoTab').toggleClass('sk-loading');

  $http({method: 'GET', url: baseUrl+'/setting/company/'+$stateParams.id})
  .then(function successCallback(data, status, headers, config) {
    $scope.mainItem=data.data;

    $scope.isLoading = false;
    $('#detailInfoTab').toggleClass('sk-loading');
  },
  function errorCallback(data, status, headers, config) {});
  $scope.states=$state;
});

app.controller('settingCompanyShowDashboard', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Detail Cabang : Dashboard';
});

app.controller('settingCompanyShowInfo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Detail Cabang : Info & Setting';
  $scope.states=$state;
});
app.controller('settingCompanyShowInfoDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Detail Cabang : Info & Setting > Detail Info';
});
app.controller('settingCompanyShowInfoNumbering', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle='Detail Cabang : Info & Setting | Penomoran Transaksi';
  $('.sk-container').addClass('sk-loading');

  $http({method: 'GET', url: baseUrl+'/setting/company/numbering_index'})
  .then(function successCallback(data, status, headers, config) {
    $scope.masterPenomoran=data.data;
    $('.sk-container').removeClass('sk-loading');
  },
  function errorCallback(data, status, headers, config) {

  });
});
app.controller('settingCompanyShowInfoGudang', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
      $rootScope.pageTitle = $rootScope.solog.label.warehouses.title 
      $scope.company_id = $stateParams.id
});

app.controller('settingCompanyShowInfoGudangCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
      $rootScope.pageTitle = $rootScope.solog.label.add 
      $scope.company_id = $stateParams.id
      $scope.id = $stateParams.warehouse_id
});
app.controller('settingCompanyShowInfoNumberingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$controller) {
  $rootScope.pageTitle='Detail Cabang : Info & Setting | Penomoran Transaksi';
  $scope.formData={};
  $('.sk-container').toggleClass('sk-loading');

  $scope.deleteFormat=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/company/delete_format/'+ids,{_token:csrfToken}).then(function success(data) {
        $state.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.tipeFormat=[
    {id:'roman',name:'Roman'},
    {id:'date',name:'Date'},
    {id:'counter',name:'Counter'},
  ];

  $scope.formatDate=[
    {id:'Y',name:'Year 4-Digit (XXXX)'},
    {id:'y',name:'Year 2-Digit (XX)'},
    {id:'m',name:'Month (01-12)'},
    {id:'F',name:'Month Text (January-December)'},
    {id:'d',name:'Day (01-31)'},
  ];

  $scope.formatRoman=[
    {id:'Y',name:'Year 4-Digit (XXXX)'},
    {id:'y',name:'Year 2-Digit (XX)'},
    {id:'m',name:'Month (01-12)'},
  ];

  $scope.formData.company_id = $stateParams.id;
  $scope.formData.type_transaction_id = $stateParams.idFormat;

  $http({method: 'GET', url: baseUrl+'/setting/company/company_numbering/'+$stateParams.id+'/'+$stateParams.idFormat})
  .then(function successCallback(data, status, headers, config) {
    $scope.companyNumbering=data.data.detail;
    $scope.item=data.data.item;
    $('.sk-container').toggleClass('sk-loading');
  },
  function errorCallback(data, status, headers, config) {

  });

  $scope.addFormat=function(idcabang,idformat) {
    $scope.formatTitle="Tambah Format Penomoran";
    $scope.formData.urut=null;
    $scope.formData.prefix=null;
    $scope.formData.type=null;
    $scope.formData.last_value=1;
    $scope.formData.ids=null;
    $('#modalFormat').modal('show');
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/setting/company/format_store',$scope.formData).then(function(data) {
      $('#modalFormat').modal('hide');
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

  $scope.editFormat=function(ids) {
    $scope.formatTitle="Tambah Format Penomoran";

    // Loading State
    $scope.isLoading = true;
    $('#modify-penomoran-modal').toggleClass('sk-loading');

    $http({method: 'GET', url: baseUrl+'/setting/company/edit_format/'+ids})
    .then(function successCallback(data, status, headers, config) {
      var dt=data.data;
      $scope.formData.urut=dt.urut;
      $scope.formData.prefix=dt.prefix;
      $scope.formData.type=dt.type;
      $scope.formData.last_value=dt.last_value;
      $scope.formData.ids=dt.id;
      if (dt.type=="date") {
        $scope.formData.format_date=dt.format_data;
      } else if (dt.type=="roman") {
        $scope.formData.format_roman=dt.format_data;
      }
      $scope.isLoading = false;
      $('#modify-penomoran-modal').toggleClass('sk-loading');

      $('#modalFormat').modal('show');
    },
    function errorCallback(data, status, headers, config) {

    });
  }
});
