app.controller('settingSaldoReceivable', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Saldo Piutang";
  $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Saldo piutang - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Saldo piutang'
      },
    ],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/saldo_receivable_datatable',
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
      { data: null, className: "text-right", name: "debet", render: e => $filter('number')(e.debet) },
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.finance.saldo.debt.detail')) {
        $(row).find('td').attr('ui-sref', 'setting.saldo_receivable.show({id:' + data.id + '})')
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
      $http.delete(baseUrl+'/setting/saldo_receivable/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
});
app.controller('settingSaldoReceivableCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $('.ibox-content').addClass('sk-loading');
  $scope.isEdit=false;

  $http.get(baseUrl+'/setting/saldo_receivable/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
  $scope.formData={
    company_id:compId,
    jatuh_tempo:1,
    nominal:0,
  };
  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/saldo_receivable?_token='+csrfToken,
      data: $scope.formData,
      dataType: 'json',
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success(data.message,"Data Berhasil disimpan!");
        $state.go('setting.saldo_receivable');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        var msgs="";
        $.each(xhr.responseJSON.errors, function(i, val) {
          msgs+=val+'<br>';
        });
        if (xhr.status==422) {
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
app.controller('settingSaldoReceivableShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/saldo_receivable/'+$stateParams.id).then(function(data) {
    $scope.item=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
});
app.controller('settingSaldoReceivableEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit";
  $('.ibox-content').addClass('sk-loading');
  $scope.isEdit=true;
  $http.get(baseUrl+'/setting/saldo_receivable/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.contact_name=dt.contact.name;
    $scope.company_name=dt.company.name;
    $scope.formData={
      company_id:dt.company_id,
      jatuh_tempo:data.data.tempo_day,
      nominal:dt.debet,
      contact_id:dt.contact_id,
      date_transaction:$filter('minDate')(dt.date_transaction),
      description:dt.description,
    };
    $('.ibox-content').removeClass('sk-loading');
  });
  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/saldo_receivable/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      dataType: 'json',
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success(data.message,"Data Berhasil disimpan!");
        $state.go('setting.saldo_receivable');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        var msgs="";
        $.each(xhr.responseJSON.errors, function(i, val) {
          msgs+=val+'<br>';
        });
        if (xhr.status==422) {
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
