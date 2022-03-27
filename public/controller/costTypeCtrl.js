app.controller('settingCostType', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.cost.title;
    $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Costs',
        sheetName: 'Data',
        title: 'Costs',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/cost_type_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"vendor.name",name:"vendor.name"},
      {data:"company.name",name:"company.name"},
      {data:"initial_cost",name:"initial_cost",className:'text-right'},
      {data:"description",name:"description"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.operational.cost_type.edit')) {
        $(row).find('td').attr('ui-sref', 'setting.cost_type.edit({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
    oTable.buttons().container().appendTo('.ibox-tools')


  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/cost_type/'+ids,{_token:csrfToken}).then(function() {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      });
    }
  }

});
app.controller('settingCostTypeCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle=$rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.cost.title;
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};
    $scope.formData.company_id=compId;
    $scope.formData.charge_in=1;
    $scope.formData.type=null;
    $scope.formData.initial_cost=0;
    $scope.formData.is_bbm=0;
    $scope.formData.is_operasional=0;
    $scope.formData.is_invoice=0;
    $scope.formData.is_biaya_lain=0;
    $scope.formData.is_ppn=0;
    $scope.formData.qty=0;
    $scope.formData.cost=0;
    $scope.formData.ppn_cost=0;
    $scope.formData.is_overtime=0;
    $scope.is_cash=0;
    $scope.is_bbm_disabled = false;
    $scope.is_operasional_disabled = false;
    $scope.is_invoice_disabled = false;
    $scope.is_biaya_lain_disabled = false;
    $scope.account_kas_hutang=[]
    $scope.data = {}
    $scope.$watch('formData.type', function(value) {
        if (value==1) {
            $scope.account_kas_hutang = $scope.data.account
        } else {
            $scope.account_kas_hutang = $scope.data.account.filter(e => e.no_cash_bank==1||e.no_cash_bank==2)
        }
    })

    $http.get(baseUrl+'/setting/cost_type/create').then(function(data) {
        $scope.data=data.data;
        $scope.cash_acc_id=data.data.cash_acc_id;
        $scope.formData.type=1;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.$on('getCostRouteType', function(e, v){
        $scope.cost_route_type_slug = v.slug
    })

  $scope.changeBBM = () => {
    if ($scope.formData.is_bbm){
      $scope.formData.qty = 1;
      $scope.formData.cost = 0;
      $scope.formData.initial_cost = 0;
      $scope.formData.is_operasional = 0;
      $scope.formData.is_invoice = 0;
      $scope.formData.is_biaya_lain = 0;
      $scope.is_operasional_disabled = true;
      $scope.is_invoice_disabled = true;
      $scope.is_biaya_lain_disabled = true;
    }
    else {
      $scope.is_operasional_disabled = false;
      $scope.is_invoice_disabled = false;
      $scope.is_biaya_lain_disabled = false;
    }
  }

  $scope.changeOperasional = () => {
    if ($scope.formData.is_operasional){
      $scope.is_bbm_disabled = false;
      $scope.is_operasional_disabled = false;
      $scope.is_invoice_disabled = true;
      $scope.formData.is_invoice = 0;
      $scope.is_biaya_lain_disabled = false;
    }
    else {
      $scope.is_invoice_disabled = false;
    }
  }

  $scope.changeInvoice = () => {
    if ($scope.formData.is_invoice){
      $scope.is_bbm_disabled = true;
      $scope.is_operasional_disabled = true;
      $scope.is_invoice_disabled = false;
      $scope.is_biaya_lain_disabled = true;
      $scope.formData.is_bbm = 0;
      $scope.formData.is_operasional = 0;
      $scope.formData.is_biaya_lain = 0;
    }
    else{
      $scope.is_bbm_disabled = false;
      $scope.is_operasional_disabled = false;
      $scope.is_biaya_lain_disabled = false;
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/cost_type?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.cost_type');
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
  $scope.saveAsSubmit=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/cost_type?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
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
app.controller('settingCostTypeEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle=$rootScope.solog.label.general.edit + ' ' + $rootScope.solog.label.cost.title;
    $('.ibox-content').addClass('sk-loading');
    $scope.account_kas_hutang = []
    $scope.formData={};

    $scope.is_cash=0;

    $scope.$on('getCostRouteType', function(e, v){
        $scope.cost_route_type_slug = v.slug
    })

  $scope.changeBBM = () => {
    if ($scope.formData.is_bbm){
      $scope.formData.is_operasional = 0;
      $scope.formData.is_invoice = 0;
      $scope.formData.is_biaya_lain = 0;
      $scope.is_operasional_disabled = true;
      $scope.is_invoice_disabled = true;
      $scope.is_biaya_lain_disabled = true;
    }
    else {
      $scope.is_operasional_disabled = false;
      $scope.is_invoice_disabled = false;
      $scope.is_biaya_lain_disabled = false;
    }
  }

  $scope.$watch('formData.type', function(value) {
    if (value==1) {
      $scope.account_kas_hutang = $scope.data.account
    } else {
      $scope.account_kas_hutang = $scope.data.account.filter(e => e.no_cash_bank==1||e.no_cash_bank==2)
    }
  })

  $scope.changeOperasional = () => {
    if ($scope.formData.is_operasional){
      $scope.is_bbm_disabled = false;
      $scope.is_operasional_disabled = false;
      $scope.is_invoice_disabled = true;
      $scope.formData.is_invoice = 0;
      $scope.is_biaya_lain_disabled = false;
    }
    else {
      $scope.is_invoice_disabled = false;
    }
  }

  $scope.changeInvoice = () => {
    if ($scope.formData.is_invoice){
      $scope.is_bbm_disabled = true;
      $scope.is_operasional_disabled = true;
      $scope.is_invoice_disabled = false;
      $scope.is_biaya_lain_disabled = true;
      $scope.formData.is_bbm = 0;
      $scope.formData.is_operasional = 0;
      $scope.formData.is_biaya_lain = 0;
    }
    else{
      $scope.is_bbm_disabled = false;
      $scope.is_operasional_disabled = false;
      $scope.is_biaya_lain_disabled = false;
    }
  }

  $http.get(baseUrl+'/setting/cost_type/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.cash_acc_id=data.data.cash_acc_id;
    var dt=data.data.item;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.description=dt.description;
    $scope.formData.name=dt.name;
    $scope.formData.code=dt.code;
    $scope.formData.category=dt.parent_id;
    $scope.formData.cash_category_id=dt.cash_category_id;
    $scope.formData.akun_kas_hutang=dt.akun_kas_hutang;
    $scope.formData.akun_uang_muka=dt.akun_uang_muka;
    $scope.formData.akun_biaya=dt.akun_biaya;
    $scope.formData.vendor_id=dt.vendor_id;
    $scope.formData.cost_route_type_id=parseInt(dt.cost_route_type_id);
    $scope.formData.type=dt.type;
    $scope.formData.initial_cost=dt.initial_cost;
    $scope.formData.is_bbm=dt.is_bbm;
    $scope.formData.is_operasional=dt.is_operasional;
    $scope.formData.is_shipment=dt.is_shipment;
    $scope.formData.is_invoice=dt.is_invoice;
    $scope.formData.is_biaya_lain=dt.is_biaya_lain;
    $scope.formData.is_ppn=dt.is_ppn;
    $scope.formData.ppn_cost=dt.ppn_cost;
    $scope.formData.qty=dt.qty;
    $scope.formData.cost=dt.cost;
    $scope.formData.is_overtime = dt.is_overtime;
    $scope.formData.is_insurance = dt.is_insurance;
    $scope.formData.is_auto_invoice = dt.is_auto_invoice;
    $scope.formData.percentage = dt.percentage;

    if (dt.is_invoice == 1) $scope.changeInvoice();
    else if (dt.is_bbm == 1) $scope.changeBBM();
    else if (dt.is_operasional == 1) $scope.changeOperasional();

    $scope.akunKasHutang=dt.akun_kas_hutang;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/cost_type/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.cost_type');
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

  $scope.saveAsSubmit=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/cost_type?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
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
