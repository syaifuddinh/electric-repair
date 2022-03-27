app.controller('financeBillReceivable', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Penagihan Piutang";
  $scope.formData = {};

   $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  });
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[6,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/bill_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;
        request['customer_id'] = $scope.formData.customer_id;
        request['status'] = $scope.formData.status;

        return request;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"company.name",name:"company.name"},
      {data:"date_request",name:"date_request"},
      {data:"customer.name",name:"customer.name"},
      {data:"total",name:"total",className:"text-right"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.debt.draft.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.bill_receivable.show({id:' + data.id + '})')
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
});
app.controller('financeBillReceivableCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Penagihan Piutang";
  $scope.formData={}
  $scope.formData.company_id=compId
  $scope.formData.total=0
  $scope.formData.date_request=dateNow
  $scope.formData.date_receive=dateNow

  $http.get(baseUrl+'/finance/bill_receivable/create').then(function(data) {
    $scope.data=data.data;
  });

  // $scope.companyChange=function(id) {
  //   $scope.customer={}
  //   $http.get(baseUrl+'/operational/invoice_jual/cari_customer_list/'+id).then(function(data) {
  //     $scope.customer=data.data.item;
  //   });
  //   $scope.formData.detail=[]
  //   $scope.resetDetail()
  //   $('#appendTable tbody').html("")
  //   $scope.hitungTotal()
  //   $scope.urut=0
  // }
  $scope.companyChange=function() {
    $scope.customerChange()    
  }
  $scope.customerChange=function() {
    $scope.formData.detail=[]
    $scope.resetDetail()
    $('#appendTable tbody').html("")
    $scope.hitungTotal()
    $scope.urut=0
    if ($scope.formData.customer_id) {
      receivableTable.ajax.reload()
    }
  }
  // $scope.companyChange(compId)

  $scope.cariPiutang=function() {
    if ($scope.formData.customer_id) {
      $('#modalReceivable').modal('show');
      $timeout(function() {
        receivableTable.ajax.reload()
      },1000)
    } else {
      toastr.error("Anda Belum Memilih Customer!");
    }
  }
  $scope.detailData={}
  $scope.detailData.is_all=0
  $scope.detailData.receivable_code='-'
  $scope.detailData.receivable_amount=0
  $scope.detailData.bill=0
  $scope.detailData.leftover=0
  $scope.detailData.description='-'

  $scope.chooseReceivable=function(id,code,amount) {
    $scope.detailData.receivable_code=code;
    $scope.detailData.receivable_id=id;
    $scope.detailData.receivable_amount=amount;
    $('#modalReceivable').modal('hide');
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.is_all=0
    $scope.detailData.receivable_code='-'
    $scope.detailData.receivable_amount=0
    $scope.detailData.bill=0
    $scope.detailData.leftover=0
    $scope.detailData.description='-'
  }

  $scope.urut=0;
  $scope.formData.detail=[]
  $scope.appendTable=function() {
    $scope.formData.detail.push({
      receivable_id:$scope.detailData.receivable_id,
      bill:$scope.detailData.bill,
      leftover:$scope.detailData.leftover,
      total:$scope.detailData.receivable_amount,
      description:$scope.detailData.description,
    })
    var html=""
    html+='<tr id="row-'+$scope.urut+'">'
    html+='<td>'+$scope.detailData.receivable_code+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.receivable_amount)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.bill)+'</td>'
    html+='<td class="text-right">'+$filter("number")($scope.detailData.leftover)+'</td>'
    html+='<td>'+$scope.detailData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'

    $('#appendTable tbody').append($compile(html)($scope))
    $scope.urut++
    $scope.resetDetail()
    $scope.hitungTotal()
  }

  $scope.appendedReceivable=function() {
    var str=""
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      str+=val.receivable_id+',';
    });
    return str.substring(0, str.length - 1);
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    delete $scope.formData.detail[id]
    $scope.hitungTotal()
  }

  $scope.hitungTotal=function() {
    $scope.formData.total=0
    $scope.formData.overpayment=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (val) {
        $scope.formData.total+=parseFloat(val.bill)
        $scope.formData.overpayment+=parseFloat(val.leftover)
      }
    })
  }

  $scope.tagihkanAll=function() {
    var vls=$scope.detailData.is_all;
    // console.log(vls)
    if (vls) {
      $scope.detailData.bill=$scope.detailData.receivable_amount;
      $scope.detailData.leftover=0;
    } else {
      $scope.detailData.bill=0;
      $scope.detailData.leftover=$scope.detailData.receivable_amount;
    }
  }

  var receivableTable = $('#receivable_datatable').DataTable({
    processing: true,
    serverSide: true,
    
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/saldo_receivable_datatable',
      data: function(d) {
        d.customer_id=$scope.formData.customer_id;
        d.company_id=$scope.formData.company_id;
        d.is_receivable=1;
        d.exclude_zero=1;
        d.exclude_receivable=$scope.appendedReceivable;
        // d.is_posting=1;
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

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/bill_receivable',$scope.formData).then(function(data) {
      $state.go('finance.bill_receivable');
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
app.controller('financeBillReceivableShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penagihan Piutang";

  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">BELUM TERBAYAR</span>'},
    {id:2,name:'<span class="badge badge-success">TERBAYAR TANPA BP</span>'},
    {id:3,name:'<span class="badge badge-success">TERBAYAR DENGAN BP</span>'}
  ];

  $http.get(baseUrl+'/finance/bill_receivable/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;

    $scope.totalAll=0
    angular.forEach($scope.detail,function(val,i) {
      $scope.totalAll+=val.bill
    })
  });

});
app.controller('financeBillReceivablePayment', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Pembayaran Penagihan Piutang";
  $scope.formData={}
  $scope.disBtn=true;

  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">BELUM TERBAYAR</span>'},
    {id:2,name:'<span class="badge badge-success">TERBAYAR TANPA BP</span>'},
    {id:3,name:'<span class="badge badge-success">TERBAYAR DENGAN BP</span>'}
  ];

  $scope.keyupZero=function(i) {
    var asli=$scope.formData.detail[i].total_bill;
    var st=parseFloat($scope.formData.detail[i].bill)
    if (st>asli) {
      $scope.formData.detail[i].bill=asli;
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
    // $scope.BPData.type_bayar=1
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

  $scope.addReceivable=function() {
    $scope.receivableData={}
    $scope.receivableData.receivable_code='-'
    $scope.receivableData.is_all=0
    $scope.receivableData.receivable_amount=0
    $scope.receivableData.bill=0
    $scope.receivableData.leftover=0
    $scope.receivableData.description='-'
    $('#modalReceivable').modal('show');
  }

  $scope.tagihkanAll=function() {
    var vls=$scope.receivableData.is_all;
    // console.log(vls)
    if (vls) {
      $scope.receivableData.bill=$scope.receivableData.receivable_amount;
      $scope.receivableData.leftover=0;
    } else {
      $scope.receivableData.bill=0;
      $scope.receivableData.leftover=$scope.receivableData.receivable_amount;
    }

  }

  $scope.receivableChange=function(id) {
    var js=$rootScope.findJsonId(id,$scope.data.receivable)
    $scope.receivableData.receivable_amount=js.total
    $scope.receivableData.leftover=js.total
    $scope.receivableData.bill=0
    $scope.receivableData.is_all=0
  }

  $scope.appendUm=function() {
    var html=""
    var js=$rootScope.findJsonId($scope.umData.um_customer_id,$scope.data.uang_muka)
    html+='<tr id="rowD-'+$scope.urut+'">'
    html+='<td>'+js.code+'</td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-right">'+$filter('number')($scope.umData.total)+'</td>'
    // html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].bill"></td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-left">'+$scope.umData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      um_customer_id:$scope.umData.um_customer_id,
      bill:-1 * parseFloat($scope.umData.total),
      leftover:0,
      total_bill:0,
      description: 'Uang muka - ' . $scope.umData.description,
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
    // html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].bill"></td>'
    html+='<td class="text-right">0</td>'
    html+='<td class="text-left">'+$scope.cndnData.description+'</td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      jenis:$scope.cndnData.jenis,
      account_id:$scope.cndnData.account_id,
      bill:parseFloat($scope.cndnData.total),
      no_cash_bank:js.no_cash_bank,
      leftover:0,
      total_bill:0,
      description:$scope.cndnData.description,
    })
    $scope.urut++

    $('#appendDetail tbody').append($compile(html)($scope))
    $('#modalCNDN').modal('hide');
    $scope.total_tagih()
  }


  $scope.appendReceivable=function() {
    var html=""
    var js=$rootScope.findJsonId($scope.receivableData.receivable_id,$scope.data.receivable)
    html+='<tr id="rowD-'+$scope.urut+'">'
    html+='<td>'+js.code+'</td>'
    html+='<td class="text-right">'+$filter('number')(js.total)+'</td>'
    html+='<td class="text-right"><input ng-keyup="hitungLeftOver('+$scope.urut+')" type="text" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].bill"></td>'
    html+='<td class="text-right">'+$scope.receivableData.leftover+'</td>'
    html+='<td class="text-right"><input style="width:100%;" type="text" ng-model="formData.detail['+$scope.urut+'].description"></td>'
    html+='<td class="text-center"><a ng-click="deleteAppend('+$scope.urut+')"><i class="fa fa-trash"></i></a></td>'
    html+='</tr>'

    $scope.formData.detail.push({
      receivable_id:$scope.receivableData.receivable_id,
      bill:$scope.receivableData.bill,
      leftover:$scope.receivableData.leftover,
      total_bill:js.total,
      description:$scope.receivableData.description,
    })
    $scope.urut++

    $('#appendDetail tbody').append($compile(html)($scope))
    $('#modalReceivable').modal('hide');
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

  $scope.total_tagih=function() {
    $scope.disBtn=true;
    $scope.formData.total_tagih=0
    $scope.formData.total_payment=0
    $scope.formData.total_paymentbp=0
    angular.forEach($scope.formData.detail, function(val,i) {
      if (val) {
        $scope.formData.total_tagih+=parseFloat(val.bill);
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
    $scope.formData.leftover_payment=$scope.formData.total_tagih-$scope.formData.total_payment;
    if ($scope.formData.leftover_payment>=0) {
      $scope.plus_minus_payment="Kurang"
    } else {
      $scope.plus_minus_payment="Lebih"
    }
    if ($scope.formData.total_payment>0) {
      $scope.disBtn=false;
    }
  }

  $scope.hitungLeftOver=function(i) {
    $scope.formData.detail[i].leftover=parseFloat($scope.formData.detail[i].total_bill-$scope.formData.detail[i].bill)
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
    }
    html+='<td>'+$scope.paymentData.description+'</td>'
    html+='<td class="text-right">'+$filter('number')($scope.paymentData.amount)+'</td>'
    html+='<td><a ng-show="roleList.includes(\'finance.debt.draft.detail.input_payment.delete_payment_method\')" ng-click="deletePayment('+$scope.paymentUrut+')"><span class="fa fa-trash"></span></a></td>'
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
    html+='<td>'+$rootScope.findJsonId($scope.BPData.cash_account_id,$scope.data.account).name+'</td>'
    // html+='<td>''</td>'
    // html+='<td>''</td>'
    html+='<td><a ng-show="roleList.includes(\'finance.debt.draft.detail.input_payment.delete_payment_method\')" ng-click="deleteBP('+$scope.paymentBpUrut+')"><span class="fa fa-trash"></span></a></td>'
    html+='</tr>'


    var html2=""
    html2+='<td colspan="3" class="text-right font-bold">Bukti Potong : </td>'
    html2+='<td class="text-right">'+$filter('number')($scope.BPData.amountptg)+'</td>'
    html2+='<td></td>'

    $scope.formData.paymentbp_detail.push({
      // payment_type: $scope.paymentData.type_bayar,
      idxpayment:$scope.paymentUrut,
      bp_cash_account_id: $scope.paymentData.cash_account_id,
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
    $('#rowB-'+i).empty()
    delete $scope.formData.paymentbp_detail[i]
    // $('#BPTable tfoot .potong').empty()
    $scope.BPData.amountptg=0
    $scope.total_tagih()
  }

  $http.get(baseUrl+'/finance/bill_receivable/payment/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.data=data.data;
    $scope.accounts=[]

    $scope.formData.date_receive=dateNow
    angular.forEach($scope.detail, function(val,i) {
      $scope.formData.detail.push({
        id: val.id,
        receivable_id: val.receivable_id,
        receivable_detail_id:val.receivable_detail_id,
        bill: val.bill,
        leftover: val.leftover,
        total_bill: val.total_bill,
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
    $scope.total_tagih()
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/bill_receivable/store_payment/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.bill_receivable.show',{id:$stateParams.id});
      toastr.success("Data Berhasil Disimpan.","Berhasileee!");
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
  // console.log("hello"+JSON.stringify($scope.formData));
  }

});
