app.controller('financeNotaCredit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Nota Potong Penjualan";
  $scope.formData = {};
  $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  }); 

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/nota_credit_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;

        return request;
      }
    },
    order:[[2,'desc']],
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"code",name:"code"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render:resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"contact.name",name:"contact.name"},
      {data:"amount",name:"amount",className:"text-right"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.noted.sell.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.nota_credit.show({id:' + data.id + '})')
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
      $http.delete(baseUrl+'/finance/nota_credit/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
      // console.log("a");
    }
  }

});
app.controller('financeNotaCreditCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Nota Potong Penjualan";
  $('.ibox-content').toggleClass('sk-loading');

  $scope.formData={};
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.jenis='1';
  $scope.formData.contra='2';
  $scope.formData.amount=0;

  $http.get(baseUrl+'/finance/nota_credit/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.cariPiutang=function(ids) {
    $scope.receivables=[];
    $http.get(baseUrl+'/finance/nota_credit/cari_piutang/'+ids).then(function(data) {
      // var dt=data.data;
      
      angular.forEach(data.data, function(dt,i) {
        $scope.receivables.push({id:dt.id,name:'No. '+dt.code+' - Jumlah: Rp. '+$filter('number')(dt.debet-dt.credit)});
        $scope.piutang = dt.debet-dt.credit;
        if($scope.piutang < $scope.formData.amount && $scope.formData.jenis == 2){
          $scope.formData.amount = 0;
        }
      })
    });
  }

  $scope.transaksiKas=function(data) {
    $scope.formData.amount=data.total;
    if($scope.piutang < $scope.formData.amount && $scope.formData.jenis == 2){
      $scope.formData.amount = 0;
      $scope.formData.cash_transaction = 0;
    }
  }

  $scope.contraChange=function() {
    $scope.formData.amount=0;
    $scope.formData.cash_transaction=null;
    $scope.formData.account_id=null;
    if($scope.piutang < $scope.formData.amount && $scope.formData.jenis == 2){
      toastr.warning("<b>Nominal</b> harus lebih kecil atau sama dengan <b>Total Piutang</b>","Error!");
      $scope.formData.amount = 0;
    }
  }
  $scope.nominalChange=function(){
    if($scope.piutang < $scope.formData.amount && $scope.formData.jenis == 2){
      toastr.warning("<b>Nominal</b> harus lebih kecil atau sama dengan <b>Total Piutang</b>","Error!");
      $scope.formData.amount = 0;
    }
    // console.log($scope.formData.amount);
  }
  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/nota_credit?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.nota_credit');
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
app.controller('financeNotaCreditShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Nota Potong Penjualan";
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/nota_credit/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $('.ibox-content').toggleClass('sk-loading');
  });

});
