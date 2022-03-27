app.controller('financeUmCustomer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Deposit Customer";
  $scope.formData = {};
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    
    //order:[[7,'desc']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/um_customer_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['start_debet'] = $scope.formData.start_debet;
        request['end_debet'] = $scope.formData.end_debet;
        request['start_credit'] = $scope.formData.start_credit;
        request['end_credit'] = $scope.formData.end_credit;
        request['start_sisa'] = $scope.formData.start_sisa;
        request['end_sisa'] = $scope.formData.end_sisa;
        request['company_id'] = $scope.formData.company_id;
        request['customer_id'] = $scope.formData.customer_id;

        return request;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"cname",name:"contacts.name"},
      {data:"coname",name:"companies.name"},
      {data:"debet",name:"debet",className:"text-right"},
      {data:"sisa",name:"sisa",className:"text-right"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.deposite.customer.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.um_customer.show({id:' + data.id + '})')
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

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/um_customer/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/um_customer_export?';
    url += params;
    location.href = url; 
  }
});

app.controller('financeUmCustomerCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Deposit Customer";
  $('.ibox-content').addClass('sk-loading');

  $scope.formData={};
  $scope.formData.detail=[];
  $scope.detail={};
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.type=[
    {id:1,name:"Kas/Bank"},
    {id:2,name:"Cek/Giro"},
  ];
  $scope.detail.type=1;
  $scope.detail.amount=0;
  $scope.formData.amount=0;
  $scope.formData.total_bayar=0;
  $scope.formData.lebih_bayar=0;
  $scope.typeChange=function(type) {
    $scope.detail.amount=0;
    if (type==2) {
      $scope.detail.cash_account_id=null;
    } else {
      $scope.detail.cek_giro_name=null;
      $scope.detail.cek_giro_id=null;
    }
  }
  $scope.modalCekGiro=function() {
    if ($scope.formData.contact_id) {
      oTable.ajax.reload();
      $('#modalCekGiro').modal('show');
    } else {
      toastr.warning("Customer Belum Dipilih!");
    }
  }

  $scope.chooseCekGiro=function(id,name,amount) {
    $scope.detail.cek_giro_id=id;
    $scope.detail.cek_giro_name=name;
    $scope.detail.amount=amount;
    $('#modalCekGiro').modal('hide');
  }
  var urut=0;
  $scope.appendTable=function() {
    var dt=$scope.detail;
    var reff="";
    if ($scope.detail.type==1) {
      reff+=$('#accountName option:selected').text();
    } else {
      reff+=$scope.detail.cek_giro_name;
    }
    var html="";
    html+="<tr id='row-"+urut+"'>";
    html+="<td>"+$('#typeBayar option:selected').text()+"</td>";
    html+="<td>"+reff+"</td>";
    html+="<td>"+$('#desc').val()+"</td>";
    html+="<td>"+$filter('number')($scope.detail.amount)+"</td>";
    html+="<td><a ng-click='deleteAppend("+urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push($scope.detail);

    $scope.detail={};
    $scope.detail.type=1;
    $scope.detail.amount=0;
    urut++;

    $scope.hitungTotalBayar();
  }

  $scope.hitungTotalBayar=function() {
    var total=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      total+=parseFloat(val.amount);
    });
    $scope.formData.total_bayar = total;
    $scope.formData.amount = total;
  }

  $scope.deleteAppend=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];
    $scope.hitungTotalBayar();
  }

  oTable = $('#cekGiroDatatable').DataTable({
    processing: true,
    serverSide: true,
    
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cek_giro_datatable',
      data:function(d) {
        d.penerbit_id=$scope.formData.contact_id;
        d.is_used=0;
        d.is_kliring=1;
        d.journal_status=3;
      }
    },
    order:[[2,'asc']],
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center"},
      {data:"company.name",name:"company.name"},
      {data:"giro_no",name:"giro_no"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"date_effective",name:"date_effective"},
      {data:"date_transaction",name:"date_transaction",className:"hidden"},
      {data:"date_effective",name:"date_effective",className:"hidden"},
      {data:"type",name:"type",className:"text-center"},
      {data:"amount",name:"amount",className:"text-right"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(baseUrl+'/finance/um_customer/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.formData.amount = $scope.formData.total_bayar;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/um_customer?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.um_customer');
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
app.controller('financeUmCustomerShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Deposit Customer";
    $('.ibox-content').addClass('sk-loading');

    $scope.total_bayar=0;
    $http.get(baseUrl+'/finance/um_customer/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.paid=data.data.paid;
        $scope.total=data.data.item.credit;

        angular.forEach($scope.paid,function(val,i) {
            $scope.total_bayar+=val.amount;
        });

        $scope.lebih_bayar = $scope.total_bayar-$scope.total;
        $scope.sisa = $scope.data.item.credit - $scope.data.item.debet;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.showAkunModal = function() {
        $scope.formData.cash_account_id = "";
        $("#modalAkun").modal("show");
    }

    $scope.simpanKembali = function() {
        $scope.formData.sisa = $scope.sisa;
        $scope.disBtn = true;
        $http.post(baseUrl+'/finance/um_customer/'+$stateParams.id+'/return_sisa', $scope.formData)
            .then(function(data) {
                $("#modalAkun").modal("hide");
                $state.reload();
                toastr.success("Data Berhasil Disimpan.","Berhasil!");
                $scope.disBtn = false;
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
