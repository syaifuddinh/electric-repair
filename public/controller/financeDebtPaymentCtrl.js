app.controller('financeDebtPayment', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Pembayaran Hutang";
  $scope.formData = {};
  $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/debt_payment_datatable',
      data : function(request) {
        request['start_date_request'] = $scope.formData.start_date_request;
        request['end_date_request'] = $scope.formData.end_date_request;
        request['start_date_receive'] = $scope.formData.start_date_receive;
        request['end_date_receive'] = $scope.formData.end_date_receive;
        request['company_id'] = $scope.formData.company_id;
        request['status'] = $scope.formData.status;

        return request;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"date_request",name:"date_request"},
      {data:"date_receive",name:"date_receive"},
      {data:"company.name",name:"company.name"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
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
    var url = baseUrl + '/excel/pelunasan_hutang_export?';
    url += params;
    location.href = url;
  }
});
app.controller('financeDebtPaymentShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Penagihan Piutang";
  $scope.formData={}
  $scope.formData.paymentbp=[]
  $scope.hidevalid=true;
  $scope.status=[
    {id:1,name:'<span class="badge badge-warning">BELUM TERBAYAR</span>'},
    {id:2,name:'<span class="badge badge-success">TERBAYAR TANPA BP</span>'},
    {id:3,name:'<span class="badge badge-warning">TERBAYAR DENGAN BP</span>'}
  ];

  $scope.payment_type=[
    {id:1,name:'Kas/Bank'},
    {id:2,name:'Cek/Giro'},
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
  });

  $scope.validBP=function(i) {
    $scope.formData.paymentbp=$scope.paymentbp[i];
    console.log(JSON.stringify($scope.formData));
    // delete $scope.formData.paymentbp_detail[i]
    // $scope.BPData.amountptg=0
    // $scope.total_tagih()

    $http.post(baseUrl+'/finance/debt_payable/validasiBP/'+$stateParams.id,$scope.formData.paymentbp).then(function(data) {
      // $state.go('finance.bill_receivable.show',{id:$stateParams.id});
      $state.go('finance.debt_payment.show',{id:$stateParams.id});
      $state.reload();
      toastr.success("Data Berhasil Disimpan.","Berhasileee!");
      // $scope.hidevalid=true;
    }, function(error) {
      // $scope.hidevalid=true;
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

    // console.log('hellllllllo' +JSON.stringify($scope.paymentbp[i]));
  }

  $scope.uploadfileny=function(id) {
    $scope.buktipotong=id
    // $scope.paymentData={}
    // $scope.paymentData.type_bayar=1
    // $scope.paymentData.amount=0
    // $scope.paymentData.description='-'
    console.log('hellldohf');
    $('#modalUpload').modal('show');
    
  }
  $scope.ambildok=function(dom) {
    console.log(dom);
  }
  $scope.submitFormny=function(dom) {
    // $scope.hidevalid=false;
    $('#modalUpload').modal('hide');
    var fd= new FormData(dom);
    $.ajax({
      url:baseUrl+'/finance/debt_payable/uploadBP/'+$scope.buktipotong+'?_token='+csrfToken,
      contentType : false,
      processData : false,
      type : 'POST',
      data : fd,
      beforeSend : function(request) {
        request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
      },
      success:function(data) {
        toastr.success("Data Berhasil Disimpan!");
        // $state.go('finance.bill_payment.show');
        
        $state.reload();
         $scope.disBtn=false;
      },
      error : function(xhr) {
        var resp = JSON.parse(xhr.responseText);
         toastr.error(resp.message,"Error Has Found !");
         $scope.disBtn=false;
      }
   });


//     $scope.disBtn=true;
//     $http.post(baseUrl+'/finance/bill_receivable/store_payment/'+$stateParams.id,$scope.formData).then(function(data) {
//       $state.go('finance.bill_receivable.show',{id:$stateParams.id});
//       toastr.success("Data Berhasil Disimpan.","Berhasileee!");
//       $scope.disBtn=false;
//     }, function(error) {
//       $scope.disBtn=false;
//       if (error.status==422) {
//         var det="";
//         angular.forEach(error.data.errors,function(val,i) {
//           det+="- "+val+"<br>";
//         });
//         toastr.warning(det,error.data.message);
//       } else {
//         toastr.error(error.data.message,"Error Has Found !");
//       }
//     });
// // console.log('haiiifferwdd '+$scope.formData.cash_account_id_krg);
// // console.log('haii '+$scope.paymentData.cash_account_id);
//   // console.log("hello"+JSON.stringify($scope.formData));
console.log('testtttt');
  }
});
