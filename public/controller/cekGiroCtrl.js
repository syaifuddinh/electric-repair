app.controller('financeCekGiro', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Transaksi Cek / Giro";

  $scope.formData = {};
  $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    order: [[12,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Cek / Giro',
        sheetName: 'Data',
        title: 'Cek / Giro',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cek_giro_datatable',
      data : function(request) {
        request.start_date_transaction = $scope.formData.start_date_transaction;
        request.end_date_transaction = $scope.formData.end_date_transaction;
        request.start_date_effective = $scope.formData.start_date_effective;
        request.end_date_effective = $scope.formData.end_date_effective;
        request.start_amount = $scope.formData.start_amount;
        request.end_amount = $scope.formData.end_amount;
        request.company_id = $scope.formData.company_id;
        request.penerbit_id = $scope.formData.penerbit_id;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"company.name",name:"company.name"},
      {data:"giro_no",name:"giro_no"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render: resp => $filter('fullDate')(resp.date_transaction)
      },
      {
        data:null,
        orderable:false,
        searchable:false,
        render: resp => $filter('fullDate')(resp.date_effective)
      },
      {data:"penerbit.name",name:"penerbit.name"},
      {data:"penerima.name",name:"penerima.name"},
      {data:"amount",name:"amount",className:"text-right"},
      {data:"type",name:"type",className:"text-center"},
      {data:"is_kliring",name:"is_kliring",className:"text-center"},
      {data:"is_empty",name:"is_empty",className:"text-center"},
      {data:"reff_no",name:"reff_no"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.giro.detail')) {
          $(row).find('td').attr('ui-sref', 'finance.cek_giro.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '.ibox-tools' );

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
      $http.delete(baseUrl+'/finance/cek_giro/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
});

app.controller('financeCekGiroCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Cek / Giro";
  $scope.formData={};
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/cek_giro/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.formData.company_id=compId;
  $scope.formData.amount=0;
  $scope.formData.type=1;
  $scope.formData.jenis=1;
  $scope.formData.is_saldo=0;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.date_effective=dateNow;
  $scope.changeSaldo=function() {
    $scope.formData.reff_no=null;
  }
  $scope.type=[
    {id:1,name:"Cheque"},
    {id:2,name:"Giro"},
  ]
  $scope.jenis=[
    {id:1,name:"IN"},
    {id:2,name:"OUT"},
  ]
  $scope.isIn = true;
  $scope.isOut = false;
  $scope.inOrout=function() {
    inOroutChange();
  }
  function inOroutChange() {
    if($scope.formData.jenis == 1){
      $scope.isIn = true;
      $scope.isOut = false;
    }else{
      $scope.isIn = false;
      $scope.isOut = true;
    }
  }
  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/cek_giro?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.cek_giro');
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

app.controller('financeCekGiroDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Cek / Giro";
  // console.log($stateParams.id+"oke");
  $('.ibox-content').toggleClass('sk-loading');

  $scope.formData={};
  $scope.isCancel=false;
  $scope.isCancelGiro=true;
  $scope.isCancelKliring=true;
  $http.get(baseUrl+'/finance/cek_giro/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.formData={
          company_id:data.data.item.company_id,
          penerima_id:data.data.item.penerima_id,
          bank_id:data.data.item.bank_id,
          giro_no:data.data.item.giro_no,
          penerbit_id:data.data.item.penerbit_id,
          amount:data.data.item.amount,
          type:data.data.item.type,
          jenis:data.data.item.jenis,
          is_saldo:data.data.item.is_saldo,
          is_empty:data.data.item.is_empty,
          is_kliring:data.data.item.is_kliring,
          date_transaction:data.data.item.date_transaction,
          date_effective:data.data.item.date_effective,
          account_bank_id:data.data.item.account_bank_id,
        }
    if($scope.formData.is_kliring || $scope.formData.is_empty){
        $scope.isCancel=true;
        if($scope.formData.is_kliring){
        $scope.isCancelGiro=false;
        $scope.isCancelKliring=true;
        }
        if($scope.formData.is_empty){
        $scope.isCancelGiro=true;
        $scope.isCancelKliring=false;
        }
    }else{
        $scope.isCancelGiro=false;
        $scope.isCancelKliring=false;
        $scope.isCancel=false;
        if($scope.formData.is_saldo){
        $scope.isCancel=true;
        $scope.isCancelKliring=true;
        $scope.isCancelGiro=true;
        }
    }
    $('.ibox-content').toggleClass('sk-loading');
  });
  $scope.type=[
    {id:1,name:"Cheque"},
    {id:2,name:"Giro"},
  ]
  $scope.jenis=[
    {id:1,name:"IN"},
    {id:2,name:"OUT"},
  ]
  $scope.isKliring=true;
  $scope.isGiro=true;
  $scope.formCancelGiro=true;
  $scope.formCancelKliring=true;


  $scope.hideGiro=function() {
    $scope.isGiro=!$scope.isGiro;
    $scope.isKliring=true;
    $scope.formData.isKliring = false;
    $scope.formData.isGiro = true;
  }
  $scope.hideKliring=function() {
    $scope.isGiro=true;
    $scope.isKliring=!$scope.isKliring;
    $scope.formData.isKliring = true;
    $scope.formData.isGiro = false;
  }
  $scope.hideCancelKliring=function() {
    $scope.formCancelGiro=true;
    $scope.formCancelKliring=!$scope.formCancelKliring
    $scope.formData.isCancelKliring = true;
    $scope.formData.isCancelGiro = false;
  }
  $scope.hideCancelGiro=function() {
    $scope.formCancelGiro=!$scope.formCancelGiro;
    $scope.formCancelKliring=true;
    $scope.formData.isCancelKliring = false;
    $scope.formData.isCancelGiro = true;
  }

  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "put",
      url: baseUrl+'/finance/cek_giro/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.cek_giro');
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
