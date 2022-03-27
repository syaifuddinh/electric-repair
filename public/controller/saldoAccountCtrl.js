app.controller('settingSaldoAccount', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Saldo Akun";
  $('.ibox-content').addClass('sk-loading');
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    // ordering: false,
    order:[[3,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Saldo Akun - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Saldo Akun'
      },
    ],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/saldo_account_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"date_transaction",name:"date_transaction"},
      {data:"description",name:"description"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.finance.saldo.account.detail')) {
        $(row).find('td').attr('ui-sref', 'setting.saldo_account.show({id:' + data.id + '})')
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
      $http.delete(baseUrl+'/setting/saldo_account/'+ids,{_token:csrfToken}).then(function() {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      });
    }
  }
});
app.controller('settingSaldoAccountShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Saldo Awal Akun";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/saldo_account/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
});
app.controller('settingSaldoAccountCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Saldo Akun";
  $('.ibox-content').addClass('sk-loading');
  $scope.data={}
  $scope.formData={}
  $scope.formData.company_id=compId
  $scope.formData.date_transaction=dateNow
  $scope.formData.detail=[]

  $http.get(baseUrl+'/setting/saldo_account/create').then(function(data) {
    $scope.data=data.data;
    $scope.formData.detail.push({
      account_id:$scope.data.default.saldo_awal,
      cash_category_id:null,
      description:'Saldo Awal Akun',
      debet:0,
      credit:0,
    });
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.totDebet=0;
  $scope.totCredit=0;

  // init AccountDefault

  var html="";
  var urutan=1;
  $scope.account=[];
  $scope.append=function() {
    html="";
    html+="<tr id='row-"+urutan+"'>";
    html+="<td><select class=\"form-control\" ng-change=\"viewCoa()\" ng-model='formData.detail["+urutan+"].account_id' data-placeholder-text-single=\"'Pilih Akun'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.code+' - '+s.name group by s.parent.name for s in data.account\"><option value=''></option></select></td>";
    html+="<td><input type='text' class='form-control' ng-model='formData.detail["+urutan+"].description'></td>";
    html+="<td><select class=\"form-control\" ng-model='formData.detail["+urutan+"].cash_category_id' ng-disabled=\"cash_list.indexOf(formData.detail["+urutan+"].account_id)===-1\" data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    html+="<td><input type='text' jnumber2 only-num ng-model='formData.detail["+urutan+"].debet' ng-disabled=\"formData.detail["+urutan+"].credit>0\" ng-keyup='hitungDK()' class='form-control debet'></td>";
    html+="<td><input type='text' jnumber2 only-num ng-model='formData.detail["+urutan+"].credit' ng-disabled=\"formData.detail["+urutan+"].debet>0\" ng-keyup='hitungDK()' class='form-control credit'></td>";
    html+="<td><a ng-click='hapus("+urutan+")' class='btn btn-sm btn-rounded btn-danger'>Delete</td>";
    html+="</tr>";

    $scope.formData.detail.push({
      account_id:null,
      cash_category_id:null,
      description:null,
      debet:0,
      credit:0,
    })

    $('#appendTable tbody').append($compile(html)($scope));
    urutan++;
    $scope.viewCoa()
  }

  $scope.hapus=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];
    hitungDeKr();
    $scope.viewCoa()
  }

  var totD=0;
  var totK=0;
  function hitungDeKr() {
    totD=0;
    totK=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      if (!val.debet) {
        val.debet=0
      }
      if (!val.credit) {
        val.credit=0
      }
      if (i!=0 && val) {
        totD+=parseFloat(val.debet);
        totK+=parseFloat(val.credit);
      }
    });
    if (totD>totK) {
      $scope.formData.detail[0].credit=totD-totK
      $scope.formData.detail[0].debet=0
      $scope.totDebet=totD;
      $scope.totCredit=totD;
    } else {
      $scope.formData.detail[0].debet=totK-totD
      $scope.formData.detail[0].credit=0
      $scope.totDebet=totK;
      $scope.totCredit=totK;
    }
  }

  $scope.hitungDK=function() {
    hitungDeKr();
    $scope.viewCoa()

  }

  $scope.viewCoa=function() {
    angular.forEach($scope.formData.detail, function(val,i) {
      if (!val) {
        return;
      }
      if (!val.account_id) {
        $scope.disBtn=true
        console.log('disable')
        return null;
      } else {
        $scope.disBtn=false
        console.log('enable')
      }
    })
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/saldo_account?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.saldo_account');
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
app.controller('settingSaldoAccountEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Akun";
  $('.ibox-content').addClass('sk-loading');
  $state.go('setting.saldo_account');

  $http.get(baseUrl+'/setting/account/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.formData={
      _token:csrfToken,
      _method:'PUT',
      is_base:data.data.item.is_base,
      group_report:data.data.item.group_report,
      jenis:data.data.item.jenis,
      code:data.data.item.code,
      name:data.data.item.name,
      type_id:data.data.item.type_id,
    }
    if (data.data.item.deep==1) {
      $scope.formData.category=data.data.item.parent_id;
    } else if (data.data.item.deep==2) {
      $http.get(baseUrl+'/setting/get_account/'+data.data.item.parent_id).then(function(res) {
        $scope.formData.category=res.data.parent_id;
        $scope.formData.sub_category=res.data.id;
      });
    } else if (data.data.item.deep==3) {
      $http.get(baseUrl+'/setting/get_account/'+data.data.item.parent_id).then(function(res) {
        $http.get(baseUrl+'/setting/get_account/'+res.data.parent_id).then(function(res2) {
          $scope.formData.category=res2.data.parent_id;
          $scope.formData.sub_category=res2.data.id;
          $scope.formData.sub_sub_category=res.data.id;
        });
      });
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.submitForm=function() {
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/account/'+$stateParams.id,
      data: $scope.formData,
      success: function(data){
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.account');
      },
    });
  }
});
