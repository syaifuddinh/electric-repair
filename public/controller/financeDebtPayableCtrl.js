app.controller('financeDebtPayable', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Pembayaran Hutang";
  $('.ibox-content').addClass('sk-loading');

  $scope.formData = {};
  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/debt_payable/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/debt_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"date_request",name:"date_request"},
      {data:"company.name",name:"company.name"},
      {data:"kode_invoice",name:"kode_invoice"},
      {data:"description",name:"description"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.credit.draft.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.debt_payable.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

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
    var url = baseUrl + '/excel/draf_pelunasan_hutang_export?';
    url += params;
    location.href = url; 
  }
});
app.controller('financeDebtPayableCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Penagihan Hutang";
  $('.ibox-content').toggleClass('sk-loading');

  $scope.isEdit = false
  $scope.formData={}
  // $scope.formData.company_id=compId
  $scope.formData.total=0
  $scope.formData.date_transaction=dateNow

  $scope.create = function() {
      $http.get(baseUrl+'/finance/debt_payable/create').then(function(data) {
          $scope.data=data.data;
          $('.ibox-content').toggleClass('sk-loading');
      }, function(){
          $scope.create()
      });
  }
  $scope.create()

  $scope.showAccount = function() {
      $http.get(baseUrl+'/setting/account').then(function(data) {
          $scope.account=data.data.account;
      }, function(){
          $scope.showAccount()          
      });
  }
  $scope.showAccount()

  $scope.companyChange=function(id) {
    $scope.supplier={}
    $http.get(baseUrl+'/finance/debt_payable/cari_supplier_list/'+id).then(function(data) {
      $scope.supplier=data.data;
    });
    $scope.formData.detail=[]
    $scope.payable_appends=[]
    $scope.resetDetail()
    $('#appendTable tbody').html("")
    $scope.hitungTotal()
    $scope.urut=0
  }

  $scope.supplierChange=function() {
    $scope.formData.detail=[]
    $scope.payable_appends=[]
    $scope.resetDetail()
    $('#appendTable tbody').html("")
    $scope.hitungTotal()
    $scope.urut=0
    if ($scope.formData.supplier_id) {
      // payableTable.ajax.reload()
    }
  }
  // $scope.companyChange(compId)

  $scope.cariHutang=function() {
    if ($scope.formData.supplier_id) {
      payableTable.ajax.reload(function() {
        $('#modalPayable').modal('show');
      },false)
      // $timeout(function() {
      //   payableTable.ajax.reload()
      // },1000)
    } else {
      toastr.error("Anda Belum Memilih Vendor!");
    }
  }
  $scope.detailData={}
  $scope.detailData.is_all=0
  $scope.detailData.payable_code='-'
  $scope.detailData.payable_amount=0
  $scope.detailData.debt=0
  $scope.detailData.leftover=0
  $scope.detailData.description='-'

  $scope.choosePayable=function(json) {
    // console.log(json);
    $scope.detailData.payable_code=json.code;
    $scope.detailData.payable_id=json.id;
    $scope.detailData.payable_amount=json.total;
    $scope.detailData.is_all=0;
    $scope.detailData.debt=0
    $scope.detailData.leftover=json.total;
    $('#modalPayable').modal('hide');
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.is_all=0
    $scope.detailData.payable_code='-'
    $scope.detailData.payable_amount=0
    $scope.detailData.debt=0
    $scope.detailData.leftover=0
    $scope.detailData.description='-'
  }

  $scope.urut=0;
  $scope.formData.detail=[]
  $scope.payable_appends=[]
  $scope.total_leftover = 0
  $scope.appendTable=function() {
    $scope.formData.detail.push({
      payable_id:$scope.detailData.payable_id,
      debt:$scope.detailData.debt,
      leftover:$scope.detailData.leftover,
      total:$scope.detailData.payable_amount,
      description:$scope.detailData.description,
    })
    $scope.total_leftover += $scope.detailData.leftover
    $scope.isMandatoryAkunSelisih()
    $scope.payable_appends.push({
      id:$scope.detailData.payable_id
    })
    var html=""
    html+='<tr id="row-'+$scope.urut+'">'
    html+='<td>'+$scope.detailData.payable_code+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.payable_amount)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.debt)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.leftover)+'</td>'
    html+='<td>'+$scope.detailData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'

    $('#appendTable tbody').append($compile(html)($scope))
    $scope.urut++
    $scope.resetDetail()
    $scope.hitungTotal()
  }

  $scope.isMandatoryAkunSelisih = function() {
      var akun_selisih = $('#akun_selisih')
      if($scope.total_leftover > 0) {
          if(!akun_selisih.hasClass('required')) {
              akun_selisih.addClass('required')
          } 
      } else {
          if(akun_selisih.hasClass('required')) {
              akun_selisih.removeClass('required')
          }         
      }
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    $scope.total_leftover -= $scope.formData.detail[id].leftover
    $scope.isMandatoryAkunSelisih()
    delete $scope.formData.detail[id]
    delete $scope.payable_appends[id]
    $scope.hitungTotal()
  }

  $scope.hitungTotal=function() {
    $scope.formData.total=0
    $scope.formData.overpayment=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (val) {
        $scope.formData.total+=parseFloat(val.debt)
        $scope.formData.overpayment+=parseFloat(val.leftover)
      }
    })
  }

  $scope.tagihkanAll=function() {
    var vls=$scope.detailData.is_all;
    // console.log(vls)
    if (vls) {
      $scope.detailData.debt=$scope.detailData.payable_amount;
      $scope.detailData.leftover=0;
    } else {
      $scope.detailData.debt=0;
      $scope.detailData.leftover=$scope.detailData.payable_amount;
    }
  }

  var payableTable = $('#payable_datatable').DataTable({
    processing: true,
    serverSide: true,
    // scrollX:'100%',
    
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/saldo_payable_datatable',
      data: function(d) {
        d.contact_id=$scope.formData.supplier_id;
        d.is_payable=true;
        d.exclude_zero=true;
        d.not_in_id=$scope.payable_appends;
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center",orderable:false},
      {data:"code",name:"code"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"type_trans",name:"type_trans"},
      {data:"date_tempo",name:"date_tempo"},
      {data:"umur",name:"umur",className:"text-right"},
      {data:"total",name:"total",className:"text-right"},
      {data:"description",name:"description"},
    ],
    initComplete : null,
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/debt_payable',$scope.formData).then(function(data) {
      $state.go('finance.debt_payable');
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
});
app.controller('financeDebtPayableEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Penagihan Hutang";
  $('.ibox-content').toggleClass('sk-loading');

  // initialize
  $scope.isEdit=true;
  $scope.disBtn=false;
  $scope.formData={}
  // $scope.formData.company_id=compId
  $scope.formData.total=0
  $scope.formData.date_transaction=dateNow

  $scope.detailData={}
  $scope.detailData.is_all=0
  $scope.detailData.payable_code='-'
  $scope.detailData.payable_amount=0
  $scope.detailData.debt=0
  $scope.detailData.leftover=0
  $scope.detailData.description='-'

  $scope.urut=0;
  $scope.formData.detail=[]
  $scope.payable_appends=[]

  $scope.totalAll=0

  // fetch data from server
  $http.get(baseUrl+'/finance/debt_payable/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.formData.company_id = $scope.data.item.company_id;
    $scope.formData.date_transaction = $filter('minDate')($scope.data.item.date_request);

    $scope.companyChange($scope.formData.company_id)

    angular.forEach($scope.data.detail,function(val,i) {
      $scope.detailData.debtDetail_id=val.id
      $scope.detailData.payable_code=val.code
      $scope.detailData.payable_id = val.payable_id
      $scope.detailData.payable_amount=val.total_debt
      $scope.detailData.debt = val.debt
      $scope.detailData.leftover = val.leftover
      $scope.detailData.description = val.description

      $scope.formData.supplier_id = val.payable.contact_id
      $scope.appendTable();
    })
    $('.ibox-content').toggleClass('sk-loading');
  })

  // methods
  $scope.companyChange=function(id) {
    $scope.supplier={}
    $http.get(baseUrl+'/finance/debt_payable/cari_supplier_list/'+id).then(function(data) {
      $scope.supplier=data.data;
    });
    $scope.formData.detail=[]
    $scope.payable_appends=[]
    $scope.resetDetail()
    $('#appendTable tbody').html("")
    $scope.hitungTotal()
    $scope.urut=0
  }
  $scope.supplierChange=function() {
    $scope.formData.detail=[]
    $scope.payable_appends=[]
    $scope.resetDetail()
    $('#appendTable tbody').html("")
    $scope.hitungTotal()
    $scope.urut=0
    if ($scope.formData.supplier_id) {
      // payableTable.ajax.reload()
    }
  }
  $scope.cariHutang=function() {
    if ($scope.formData.supplier_id) {
      payableTable.ajax.reload(function() {
        $('#modalPayable').modal('show');
      },false)
    } else {
      toastr.error("Anda Belum Memilih Supplier!");
    }
  }
  $scope.choosePayable=function(json) {
    // console.log(json);
    $scope.detailData.payable_code=json.code;
    $scope.detailData.payable_id=json.id;
    $scope.detailData.payable_amount=json.total;
    $scope.detailData.is_all=0;
    $scope.detailData.debt=0
    $scope.detailData.leftover=json.total;
    $('#modalPayable').modal('hide');
  }
  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.is_all=0
    $scope.detailData.payable_code='-'
    $scope.detailData.payable_amount=0
    $scope.detailData.debt=0
    $scope.detailData.leftover=0
    $scope.detailData.description='-'
  }
  $scope.appendTable=function() {
    $scope.formData.detail.push({
      debtDetail_id:$scope.detailData.debtDetail_id,
      payable_id:$scope.detailData.payable_id,
      debt:$scope.detailData.debt,
      leftover:$scope.detailData.leftover,
      total:$scope.detailData.payable_amount,
      description:$scope.detailData.description,
    })
    $scope.payable_appends.push({
      id:$scope.detailData.payable_id
    })
    var html=""
    html+='<tr id="row-'+$scope.urut+'">'
    html+='<td>'+$scope.detailData.payable_code+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.payable_amount)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.debt)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.leftover)+'</td>'
    html+='<td>'+$scope.detailData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'

    $('#appendTable tbody').append($compile(html)($scope))
    $scope.urut++
    $scope.resetDetail()
    $scope.hitungTotal()
  }
  $scope.deleteAppend=function(id) {
    debtDetail = $scope.formData.detail[id]
    requestDelete = $scope.deletes(debtDetail.debtDetail_id)
    if (requestDelete) {
      $('#row-'+id).remove()
      delete $scope.formData.detail[id]
      delete $scope.payable_appends[id]
      $scope.hitungTotal()
    }
  }
  $scope.hitungTotal=function() {
    $scope.formData.total=0
    $scope.formData.overpayment=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (val) {
        $scope.formData.total+=parseFloat(val.debt)
        $scope.formData.overpayment+=parseFloat(val.leftover)
      }
    })
  }
  $scope.tagihkanAll=function() {
    var vls=$scope.detailData.is_all;
    // console.log(vls)
    if (vls) {
      $scope.detailData.debt=$scope.detailData.payable_amount;
      $scope.detailData.leftover=0;
    } else {
      $scope.detailData.debt=0;
      $scope.detailData.leftover=$scope.detailData.payable_amount;
    }
  }
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/finance/debt_payable/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.debt_payable');
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
  $scope.deletes= async function(ids) {
    let res = false
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      await $http.delete(baseUrl+'/finance/debt_payable/'+ids+'/detail',{_token:csrfToken}).then(function success(data) {
        toastr.success("Data Berhasil Dihapus!");

        res = true;
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
        res = false;
      });
    }

    return res
  }

  var payableTable = $('#payable_datatable').DataTable({
    processing: true,
    serverSide: true,
    // scrollX:'100%',
    // 
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/saldo_payable_datatable',
      data: function(d) {
        d.contact_id=$scope.formData.supplier_id;
        d.is_payable=true;
        d.exclude_zero=true;
        d.not_in_id=$scope.payable_appends;
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center",orderable:false},
      {data:"code",name:"code"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"type_trans",name:"type_trans"},
      {data:"date_tempo",name:"date_tempo"},
      {data:"umur",name:"umur",className:"text-right"},
      {data:"total",name:"total",className:"text-right"},
      {data:"description",name:"description"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
});
app.controller('financeDebtPayableShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penagihan Hutang";
  $scope.formData={}
  $scope.formData.paymentbp=[]
  $('.ibox-content').toggleClass('sk-loading');

  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">BELUM TERBAYAR</span>'},
    {id:2,name:'<span class="badge badge-success">TERBAYAR TANPA BP</span>'},
    {id:3,name:'<span class="badge badge-warning">TERBAYAR DENGAN BP</span>'}
  ];

  $http.get(baseUrl+'/finance/debt_payable/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.payment=data.data.payment;
    $scope.paymentbp=data.data.paymentbp;
    $scope.formData.paymentbp=$scope.paymentbp;
    $scope.totalAll=0
    angular.forEach($scope.detail,function(val,i) {
      $scope.totalAll+=val.debt
    })

    $scope.totalPayment=0
    angular.forEach($scope.payment,function(val,i) {
      $scope.totalPayment+=val.total
    })

    $scope.totalPaymentBP=0
    angular.forEach($scope.paymentbp,function(val,i) {
      $scope.totalPaymentBP+=val.total
    })

    $scope.totalPayment=$scope.totalPayment+$scope.totalPaymentBP;
    $scope.leftOver=$scope.totalAll-$scope.totalPayment;
    $('.ibox-content').toggleClass('sk-loading');


  });

});
app.controller('financeDebtPayablePayment', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Pembayaran Penagihan Hutang";
  $scope.formData={}
  $('.ibox-content').toggleClass('sk-loading');
  $scope.disBtn=true;

  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">BELUM TERBAYAR</span>'},
    {id:2,name:'<span class="badge badge-success">TERBAYAR TANPA BP</span>'},
    {id:3,name:'<span class="badge badge-warning">TERBAYAR DENGAN BP</span>'}
  ];

  $scope.keyupZero=function(i) {
    var asli=$scope.formData.detail[i].total_debt;
    var st=parseFloat($scope.formData.detail[i].debt)
    if (st>asli) {
      $scope.formData.detail[i].debt=asli;
      $scope.total_tagih()
    }
  }

  $scope.addPayment=function() {
    $scope.paymentData={}
    $scope.paymentData.type_bayar=1
    $scope.paymentData.amount=0
    $scope.paymentData.description='-'
    $('#modalPayment').modal('show');
  }

  $scope.addBuktiPotong=function() {
    $scope.BPData={}
    $scope.BPData.type_bayar=1
    $scope.BPData.buktiptg='-'
    $scope.BPData.amountptg=0
    $scope.BPData.description='-'
    $('#modalPaymentBP').modal('show');
  }

  $scope.addUM=function() {
    $scope.umData={}
    $scope.umData.total=0
    $scope.umData.description='-'
    $('#modalUm').modal('show');
  }
  $scope.addCNDN=function() {
    $scope.cndnData={}
    $scope.cndnData.jenis=1
    $scope.cndnData.total=0
    $scope.cndnData.description='-'
    $('#modalCNDN').modal('show');
  }

  $scope.umChange=function(id) {
    var js=$rootScope.findJsonId(id,$scope.data.uang_muka);
    $scope.umData.total=js.total;
  }

  $scope.addPayable=function() {
    $scope.payableData={}
    $scope.payableData.payable_code='-'
    $scope.payableData.is_all=0
    $scope.payableData.payable_amount=0
    $scope.payableData.debt=0
    $scope.payableData.leftover=0
    $scope.payableData.description='-'
    $('#modalPayable').modal('show');
  }

  $scope.tagihkanAll=function() {
    var vls=$scope.payableData.is_all;
    // console.log(vls)
    if (vls) {
      $scope.payableData.debt=$scope.payableData.payable_amount;
      $scope.payableData.leftover=0;
    } else {
      $scope.payableData.debt=0;
      $scope.payableData.leftover=$scope.payableData.payable_amount;
    }

  }

  $scope.payableChange=function(id) {
    var js=$rootScope.findJsonId(id,$scope.data.payable)
    $scope.payableData.payable_amount=js.total
    $scope.payableData.leftover=js.total
    $scope.payableData.debt=0
    $scope.payableData.is_all=0
  }

  $scope.appendUm=function() {
    var html=""
    var js=$rootScope.findJsonId($scope.umData.um_supplier_id,$scope.data.uang_muka)
    html+='<tr id="rowD-'+$scope.urut+'">'
    html+='<td>'+js.code+'</td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-right">'+$filter('number')($scope.umData.total)+'</td>'
    // html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].debt"></td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-left">'+$scope.umData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      um_supplier_id:$scope.umData.um_supplier_id,
      debt:parseFloat($scope.umData.total),
      leftover:0,
      total_debt:0,
      description:$scope.umData.description,
    })
    $scope.urut++

    $('#appendDetail tbody').append($compile(html)($scope))
    $('#modalUm').modal('hide');
    $scope.total_tagih()
  }
  $scope.appendCNDN=function() {
    var html=""
    var js=$rootScope.findJsonId($scope.cndnData.account_id,$scope.data.account)
    html+='<tr id="rowD-'+$scope.urut+'">'
    html+='<td>'+js.code+' - '+js.name+'</td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-right">'+$filter('number')($scope.cndnData.total)+'</td>'
    // html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].debt"></td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-left">'+$scope.cndnData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      jenis:$scope.cndnData.jenis,
      account_id:$scope.cndnData.account_id,
      debt:parseFloat($scope.cndnData.total),
      no_cash_bank:js.no_cash_bank,
      leftover:0,
      total_debt:0,
      description:$scope.cndnData.description,
    })
    $scope.urut++

    $('#appendDetail tbody').append($compile(html)($scope))
    $('#modalCNDN').modal('hide');
    $scope.total_tagih()
  }


  $scope.appendPayable=function() {
    var html=""
    var js=$rootScope.findJsonId($scope.payableData.payable_id,$scope.data.payable)
    html+='<tr id="rowD-'+$scope.urut+'">'
    html+='<td>'+js.code+'</td>'
    html+='<td class="text-right">'+$filter('number')(js.total)+'</td>'
    html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].debt"></td>'
    html+='<td class="text-right">'+$scope.payableData.leftover+'</td>'
    html+='<td class="text-right"><input style="width:100%;" type="text" ng-model="formData.detail['+$scope.urut+'].description"></td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      payable_id:$scope.payableData.payable_id,
      debt:$scope.payableData.debt,
      leftover:$scope.payableData.leftover,
      total_debt:js.total,
      description:$scope.payableData.description,
    })
    $scope.urut++

    $('#appendDetail tbody').append($compile(html)($scope))
    $('#modalPayable').modal('hide');
    $scope.total_tagih()
  }

  $scope.deleteAppend=function(id) {
    $('#rowD-'+id).remove()
    delete $scope.formData.detail[id]
    $scope.total_tagih()
  }

  $scope.payment_type=[
    {id:1,name:'Kas/Bank'},
    {id:2,name:'Cek/Giro'},
  ];

  $scope.formData.detail=[]
  $scope.urut=0
  $scope.formData.total_tagih=0
  $scope.formData.leftover_payment=0
  $scope.formData.total_payment=0
  $scope.formData.cash_account_id_krg=0

  $scope.total_tagih=function() {
    $scope.formData.total_tagih=0
    $scope.formData.total_payment=0
    $scope.formData.total_paymentbp=0

    angular.forEach($scope.formData.detail, function(val,i) {
      if (val) {
        $scope.formData.total_tagih+=parseFloat(val.debt);
      }
    });
    angular.forEach($scope.formData.payment_detail, function(val,i) {
      if (val) {
        $scope.formData.total_payment+=parseFloat(val.total);
      }
    })
    angular.forEach($scope.formData.paymentbp_detail, function(val,i) {
      if (val) {
        $scope.formData.total_paymentbp+=parseFloat(val.totalbp);
      }
    })
    //7 agustus 2019 andre
    $scope.formData.total_payment=$scope.formData.total_payment+$scope.formData.total_paymentbp;
    //end andre
    $scope.formData.leftover_payment=$scope.formData.total_tagih-$scope.formData.total_payment;
    if ($scope.formData.leftover_payment>=0) {
      $scope.disBtn = true;
      $scope.plus_minus_payment="Kurang"
    } else {
      $scope.disBtn = false;
      $scope.plus_minus_payment="Lebih"
      $scope.formData.leftover_payment=Math.abs($scope.formData.leftover_payment);
    }

    if($scope.formData.leftover_payment != 0 && $scope.formData.cash_account_id_krg == 0)
        $scope.disBtn=true;
    else
        $scope.disBtn=false;
  }

  $scope.hitungLeftOver=function(i) {
    $scope.formData.detail[i].leftover=parseFloat($scope.formData.detail[i].total_debt-$scope.formData.detail[i].debt)
    $scope.total_tagih()
  }

  $scope.changeTypeBayar=function() {
    $scope.paymentData.amount=0
    $scope.paymentData.cash_account_id=null
    $scope.BPData.cash_account_id=null
    $scope.paymentData.cek_giro_id=null
  }

  $scope.changeGiro=function(id) {
    var cg=$rootScope.findJsonId(id,$scope.data.cek_giro)
    if (cg) {
      $scope.paymentData.amount=cg.amount
    } else {
      $scope.paymentData.amount=0
    }
  }

  $scope.paymentUrut=0
  $scope.paymentBpUrut=0
  $scope.formData.payment_detail=[]
  $scope.formData.paymentbp_detail=[]
  $scope.appendPayment=function() {
    var html=""
    html+='<tr id="rowP-'+$scope.paymentUrut+'">'
    html+='<td>'+$rootScope.findJsonId($scope.paymentData.type_bayar,$scope.payment_type).name+'</td>'
    if ($scope.paymentData.type_bayar==1) {
      html+='<td>'+$rootScope.findJsonId($scope.paymentData.cash_account_id,$scope.data.account).name+'</td>'
    } else {
      html+='<td>'+$rootScope.findJsonId($scope.paymentData.cek_giro_id,$scope.data.cek_giro).code+'</td>'
    }
    html+='<td>'+$scope.paymentData.description+'</td>'
    html+='<td class="text-right">'+$filter('number')($scope.paymentData.amount)+'</td>'
    html+='<td><a  ng-show=\"roleList.includes(\'finance.credit.draft.detail.input_payment.delete_payment_method\')\" ng-click="deletePayment('+$scope.paymentUrut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'

    $scope.formData.payment_detail.push({
      payment_type: $scope.paymentData.type_bayar,
      total: $scope.paymentData.amount,
      description: $scope.paymentData.description,
      cek_giro_id: $scope.paymentData.cek_giro_id,
      cash_account_id: $scope.paymentData.cash_account_id,
    })
    $('#paymentTable tbody').append($compile(html)($scope))
    $scope.paymentUrut++
    $scope.total_tagih()

    $('#modalPayment').modal('hide');
  }

  $scope.appendBpotong=function() {
    var html=""
    html+='<tr id="rowB-'+$scope.paymentBpUrut+'">'
    html+='<td>'+($scope.BPData.buktiptg)+'</td>'
    // html+='<td>'+$rootScope.findJsonId($scope.BPData.type_bayar,$scope.payment_type).name+'</td>'
    html+='<td>'+$filter('number')($scope.BPData.amountptg)+'</td>'
    html+='<td>'+$rootScope.findJsonId($scope.BPData.cash_account_id,$scope.data.accountall).name+'</td>'
    // html+='<td>''</td>'
    // html+='<td>''</td>'
    html+='<td><a ng-show="roleList.includes(\'finance.debt.draft.detail.input_payment.delete_payment_method\')" ng-click="deleteBP('+$scope.paymentBpUrut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'


    var html2=""
    html2+='<td colspan="3" class="text-right font-bold">Bukti Potong : </td>'
    html2+='<td class="text-right">'+$filter('number')($scope.BPData.amountptg)+'</td>'
    html2+='<td></td>'

    $scope.formData.paymentbp_detail.push({
      payment_type: $scope.BPData.type_bayar,
      idxpayment:$scope.paymentUrut,
      nmrbp:$scope.BPData.buktiptg,
      bp_cash_account_id: $scope.BPData.cash_account_id,
      totalbp:$scope.BPData.amountptg,
      // total: $scope.paymentData.amount,
      // description: $scope.paymentData.description,
      // cek_giro_id: $scope.paymentData.cek_giro_id,
      // cash_account_id: $scope.paymentData.cash_account_id,
    })
    $('#BPTable tbody').append($compile(html)($scope))
    $scope.paymentBpUrut++
    $scope.total_tagih()
    // $('#BPTable tfoot .potong').append($compile(html2)($scope))

    $('#modalPaymentBP').modal('hide');
  }

  $scope.deletePayment=function(i) {
    $('#rowP-'+i).empty()
    delete $scope.formData.payment_detail[i]
    $scope.total_tagih()
  }

  $scope.deleteBP=function(i) {
    $('#rowB-'+i).empty();
    delete $scope.formData.paymentbp_detail[i];
    
    $scope.BPData.amountptg = 0;
    $scope.total_tagih();
  }

  $http.get(baseUrl+'/finance/debt_payable/payment/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.data=data.data;
    $scope.accounts=[];
    $scope.accountsall=[]

    $scope.formData.date_receive=dateNow
    angular.forEach($scope.detail, function(val,i) {
      $scope.formData.detail.push({
        id: val.id,
        payable_id: val.payable_id,
        payable_detail_id:val.payable_detail_id,
        debt: val.debt,
        leftover: val.leftover,
        total_debt: val.total_debt,
        description: val.description,
      })
      $scope.urut++
    })
    angular.forEach(data.data.account,function(val,i) {
      // console.log(val)
      if (val.no_cash_bank!=0) {
        $scope.accounts.push({
          id:val.id,
          name:val.code+' - '+val.name,
        })
      }
    })

    angular.forEach(data.data.accountall,function(val,i) {
      // console.log(val)
      // if (val.deep==2) {
        $scope.accountsall.push({
          id:val.id,
          name:val.code+' - '+val.name,
        })
      // }
    })
    $scope.total_tagih()
    $('.ibox-content').toggleClass('sk-loading');
  });

  

  //backup versi awal 7 agustus 2019
  // $scope.submitForm=function() {
  //   $scope.disBtn=true;
  //   $http.post(baseUrl+'/finance/debt_payable/store_payment/'+$stateParams.id,$scope.formData).then(function(data) {
  //     $state.go('finance.debt_payable.show',{id:$stateParams.id});
  //     toastr.success("Data Berhasil Disimpan.","Berhasil!");
  //     $scope.disBtn=false;
  //   }, function(error) {
  //     $scope.disBtn=false;
  //     if (error.status==422) {
  //       var det="";
  //       angular.forEach(error.data.errors,function(val,i) {
  //         det+="- "+val+"<br>";
  //       });
  //       toastr.warning(det,error.data.message);
  //     } else {
  //       toastr.error(error.data.message,"Error Has Found !");
  //     }
  //   });
  // }
  //end backup


  //by andre 7 agustus 2019
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/debt_payable/store_payment/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.debt_payable.show',{id:$stateParams.id});
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
console.log('haiiifferwdd '+$scope.formData.cash_account_id_krg);
// console.log('haii '+$scope.paymentData.cash_account_id);
  console.log("hello"+JSON.stringify($scope.formData));
  }
  //end by andre

});
