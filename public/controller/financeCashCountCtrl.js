app.controller('financeCashCount', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Cash Count";
  $scope.formData = {};
  
  $http.get(baseUrl+'/finance/cash_count').then(function(data) {
    $scope.data=data.data
  });


  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[4,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cash_count_datatable',
      data : function(d){
        d.company_id = $scope.formData.company_id;
        d.start_date = $scope.formData.start_date;
        d.end_date = $scope.formData.end_date;
      }
    },
    columns:[
      {data:"company.name",name:"company.name",className:"font-bold"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"saldo_awal",name:"saldo_awal"},
      {data:"approved_by.name",name:"approved_by.name"},
      {data:"description",name:"description"},
      {
        data:null,
        name:"status",
        className : 'text-center',
        render : function(resp) {
          var label = resp.status == 0 ? 'Belum Disetujui' : 'Sudah Disetujui';
          var className = resp.status == 0 ? 'label-danger' : 'label-primary';
          var outp = '<span class="label ' + className + '">' + label + '</span>';

          return outp;
        }
      },
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.cash_count.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.cash_count.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  // $scope.deletes=function(ids) {
  //   var cfs=confirm("Apakah Anda Yakin?");
  //   if (cfs) {
  //     $http.delete(baseUrl+'/operational/voyage_schedule/'+ids,{_token:csrfToken}).then(function success(data) {
  //       oTable.ajax.reload();
  //       toastr.success("Data Berhasil Dihapus!");
  //     }, function error(data) {
  //       toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
  //     });
  //   }
  // }

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/cash_count_export?';
    url += params;
    location.href = url; 
  }
});
app.controller('financeCashCountCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Tambah Cash Count";
  $scope.formData={}
  $scope.formData.date_transaction=dateNow
  $scope.formData.company_id=compId
  $scope.formData.saldo_awal=0
  $scope.formData.total_saldo=0
  $scope.formData.bailout=0
  $scope.is_freeze=false;
  $scope.disBtn = false;

  $scope.cash=[
    {id:1,name:"100.000",value:100000,amount:0,total:0},
    {id:2,name:"50.000",value:50000,amount:0,total:0},
    {id:3,name:"20.000",value:20000,amount:0,total:0},
    {id:4,name:"10.000",value:10000,amount:0,total:0},
    {id:5,name:"5.000",value:5000,amount:0,total:0},
    {id:6,name:"2.000",value:2000,amount:0,total:0},
    {id:7,name:"1.000",value:1000,amount:0,total:0},
    {id:8,name:"500",value:500,amount:0,total:0},
    {id:9,name:"200",value:200,amount:0,total:0},
    {id:10,name:"100",value:100,amount:0,total:0},
  ];

  $scope.formData.detail=$scope.cash

  $scope.addMoney = function() {
      $scope.cash.push(
        {name:"0",value:0,amount:0,total:0, is_editable:1}
      )
  }
  $scope.removeMoney = function() {
      $scope.cash.splice( $scope.cash.length - 1, 1 )
      $scope.hitungTotal()
  }
  $scope.companyChange=function(cid) {
    $http.get(baseUrl+'/finance/cash_count/cari_saldo/'+cid).then(function(data) {
      $scope.formData.saldo_awal=data.data.saldo;
      $scope.is_freeze=data.data.is_freeze;
      $scope.hitungTotal();
      $http.get(baseUrl+'/finance/cash_count/create?company_id=' + cid).then(function(data) {
        $scope.data=data.data
        $scope.formData.bkk_hari_ini = $scope.data.bkk_hari_ini;
        $scope.formData.bkm_hari_ini = $scope.data.bkm_hari_ini;
        $scope.formData.saldo_akhir = $scope.formData.saldo_awal - $scope.formData.bkk_hari_ini + $scope.formData.bkm_hari_ini;
      });
    });
  }
  $scope.companyChange(compId)

  $scope.toggleFreeze=function(cid) {
    $http.post(baseUrl+'/finance/cash_count/toggle_freeze/'+cid,{freeze:$scope.is_freeze}).then(function(data) {
      toastr.success(data.data.message);
      $scope.companyChange(cid)
    });
  }

  $scope.formData.total_cash_fisik = 0;
  $scope.hitungTotal=function() {
    $scope.formData.total_saldo=0
    angular.forEach($scope.cash,function(val,i) {
      $scope.formData.total_saldo+= (parseFloat(val.total) || 0);
    });
    $scope.formData.total_cash_fisik= $scope.formData.total_saldo;
    $scope.formData.bailout=$scope.formData.saldo_awal-$scope.formData.total_saldo;
  }

  $scope.goBack=function(cid) {
    if (cid) {
      if ($scope.is_freeze) {
        $http.post(baseUrl+'/finance/cash_count/toggle_freeze/'+cid,{freeze:$scope.is_freeze}).then(function(data) {
          $state.go('finance.cash_count');
        });
      } else {
        $state.go('finance.cash_count');
      }
    } else {
      $state.go('finance.cash_count');
    }
  }

  

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.formData.cash_advance = oTable.rows().data().toArray();
    $scope.formData.detail=$scope.cash
    $http.post(baseUrl+'/finance/cash_count',$scope.formData).then(function(data) {
      $state.go('finance.cash_count');
      toastr.success("Data Berhasil Disimpan.","Berhasil!");
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


  $scope.formData.total_kasbon= 0;
  oTable = $('#kas_bon_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[2,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/kas_bon_datatable',
      data : function(req) {
        req['is_today'] = 1;
        return req;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"company.name",name:"company.name"},
      {data:"employee.name",name:"employee.name"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"total_cash_advance",name:"total_cash_advance",className:"text-right"},
      {data:"description",name:"description"},
      {data:"status",name:"status", className : 'text-center'},
      {
        data:null,
        name:"total_approve",
        className: 'text-right',
        render: function(resp) {
          var total_approve = parseInt(resp.total_approve);
          var outp = $filter('number')(total_approve);
          return outp;
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    },
    initComplete : function(settings) {
      var data = this.api().data().toArray();
      for(x in data) {
        $scope.formData.total_kasbon += parseInt(data[x].total_approve);
      }
    }
  });
});
app.controller('financeCashCountShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Detail Cash Count";
  $scope.totalAll=0;
  $scope.disBtn=false;

  $http.get(baseUrl+'/finance/cash_count/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.status = $scope.item.status == 0 ? "Belum Disetujui" : "Sudah Disetujui";
    $scope.statusClass = $scope.item.status == 0 ? "danger" : "primary";
    angular.forEach($scope.detail,function(val,i) {
      $scope.totalAll+=val.total;
    });

    kas_bon_datatable = $('#kas_bon_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[2,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/kas_bon_datatable',
      data : function(req) {
        req['cash_count_id'] = $scope.item.id;
        return req;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"company.name",name:"company.name"},
      {data:"employee.name",name:"employee.name"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"total_cash_advance",name:"total_cash_advance",className:"text-right"},
      {data:"description",name:"description"},
      {data:"status",name:"status", className : 'text-center'},
      {
        data:null,
        name:"total_approve",
        className: 'text-right',
        render: function(resp) {
          var total_approve = parseInt(resp.total_approve);
          var outp = $filter('number')(total_approve);
          return outp;
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    },
    initComplete : function(settings) {
      var data = this.api().data().toArray();
      for(x in data) {
        $scope.formData.total_kasbon += parseInt(data[x].total_approve);
      }
    }
  });
  });

  $scope.submitValidation = function() {
      $scope.disBtn=true
      $http.post(baseUrl+'/finance/cash_count/approve/' + $stateParams.id,$scope.formData).then(function(data) {
        $('#modalValidation').modal('hide');
        location.reload()
        toastr.success("Cash count berhasil divalidasi");
      }, function error(error) {
        $scope.disBtn=false;
      if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
          det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
      } else {
        console.log(error);
        toastr.error(error.data.message,"Informasi !");
      }
      });
  }

  $scope.kembali = function() {
    $state.go('finance.cash_count');
  }

  // $http.put(baseUrl+'/finance/cash_count/approve/'+$stateParams.id).then(function(data) {
  //   $scope.item=data.data.item;
  //   $scope.detail=data.data.detail;

  //   angular.forEach($scope.detail,function(val,i) {
  //     $scope.totalAll+=val.total;
  //   })
  // });
});
