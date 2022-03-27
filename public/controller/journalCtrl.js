app.controller('financeJournal', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Jurnal Umum";
    $scope.filterData={}
    $scope.filterData.company_id=compId;
    $scope.filterData.is_audit=0;
    $scope.is_admin = authUser.is_admin;

  $http.get(baseUrl+'/finance/journal').then(function(data) {
    $scope.data=data.data;
  });

  $scope.status=[
    {id:1,name:"Draft"},
    {id:2,name:"Disetujui"},
    {id:3,name:"Posted"},
  ];

  $scope.tableData = [];
  $scope.checkData = {
    item:  []
  };
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [[9,'desc']],
    dom: 'Blfrtip',
    scrollX : true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    buttons: [
        {
          extend : 'excel',
          enabled : true,
          text : '<span class="fa fa-file-excel-o"></span> Export Excel',
          className : 'btn btn-default btn-sm pull-right',
          filename : 'Excel Summary WO - '+new Date(),
          messageTop : 'Summary WO',
          sheetName : 'Data',
          title : 'Summary WO',
        },
      ],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/journal_datatable',
      data: function(d) {
        d.filterData=$scope.filterData;
      }
    },
    columns:[
      {data:"checkbox",name:"checkbox", orderable:false},
      {data:"status",name:"status", className:"text-center"},
      {data:"company.name",name:"company.name"},
      {
        data:null,
        name:'date_transaction',
        searchable:false,
        render:resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"code",name:"code",className:'font-bold'},
      {data:"debet",name:"debet",className:'text-right'},
      {data:"credit",name:"credit",className:'text-right'},
      {data:"description",name:"description"},
      {data:"type_transaction.name",name:"type_transaction.name"},
      {data:"action",name:"created_at",className:'text-center'},
    ],
    initComplete : null,
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.journal.delete')) {
        $(row).find('td').attr('ui-sref', 'finance.journal.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
        $(row).find('td:first-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
      $scope.tableData[angular.fromJson(data).id] = 1;
    }
  });
// console.log($scope.data);
  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/journal/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function(error) {
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
  }

  $scope.submitAudit = function() {
      $('#yearModal').modal('hide');
      $timeout(function(){
          $state.go('finance.journal.create_audit', {'year' : $scope.year})
      }, 300)
  }

  $scope.showYearModal = function() {
      $('#yearModal').modal('show');
  }

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset_filter=function() {
    $scope.filterData={};
    $scope.refreshTable()
  }

  $scope.approve=function() {
    // console.log($scope.checkData);
    $http.post(baseUrl+'/finance/journal/approve?_token='+csrfToken,$scope.checkData).then(function(data) {
      toastr.success("Jurnal Telah Disetujui","Berhasil!");
      $state.reload();
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }

  $scope.approvePost=function() {
    // console.log($scope.checkData);
    $http.post(baseUrl+'/finance/journal/approve_post?_token='+csrfToken,$scope.checkData).then(function(data) {
      toastr.success("Jurnal Telah Diposting","Berhasil!");
      $state.reload();
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }
  $scope.isHide=true;
  $scope.hideFilter=function() {
    if ($scope.isHide==true) {
      $scope.isHide=false;
    } else {
      $scope.isHide=true;
    }
  }

  $scope.testModalPosting=function() {
    $('#postingRevModal').modal();
  }

  $scope.postingOne=function(id) {
    $http.post(baseUrl+'/finance/journal/posting_rev/'+id).then(function(data) {
      $scope.revData={}
      $scope.revData.journal_id=data.data.item.id;
      $scope.revData.detail=[]
      $scope.revItem=data.data.item;
      var html="";
      angular.forEach(data.data.detail, function(val,i) {
        html+='<tr>'
        html+='<td>'+val.account.account_name+'</td>'
        if ($rootScope.in_array(val.account.no_cash_bank,[1,2])) {
          html+="<td><select class=\"form-control\" ng-model='revData.detail["+i+"].cash_category_id' data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
        } else {
          html+='<td>-</td>'
        }
        html+='<td>'+$filter("number")(val.debet)+'</td>'
        html+='<td>'+$filter("number")(val.credit)+'</td>'
        html+='</tr>'

        $scope.revData.detail.push({id:val.id,cash_category_id:val.cash_category_id})
      })
      $('#appendDetail tbody').html($compile(html)($scope))
      $('#postingRevModal').modal('show');
    });
  }

  $scope.submitPosting=function() {
    $http.post(baseUrl+'/finance/journal/store_posting',$scope.revData).then(function(data) {
      toastr.success("Jurnal Telah Diposting","Berhasil!");
      $('#postingRevModal').modal('hide');
      oTable.ajax.reload();
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }
  // function checkBox() {
  // }
  function headeCheckBox() {
    $scope.checkData.item= angular.copy($scope.tableData);
  }
  $scope.checkAll=function() {
    headeCheckBox();
  }
  // $scope.trCheck=function() {
  //   checkBox();
  // }
  oTable.buttons().container().appendTo( '#export_button' );
});
app.controller('financeJournalShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Detail Jurnal Umum";
  $scope.data = {
    item : {}
  };
  $scope.disPostingBtn = false;
  $scope.disCancelBtn = false;
  $('.ibox-content').addClass('sk-loading');

  $scope.show = function() {
      $http.get(baseUrl+'/finance/journal/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.journalData = {
          id : $scope.data.item.id,
          unposting_reason : ''
        };
        $('.ibox-content').removeClass('sk-loading');
      });
  }
  $scope.show()

  $scope.postingOne=function() {
    var id = $scope.data.item.id
    $http.post(baseUrl+'/finance/journal/posting_rev/'+id).then(function(data) {
      $scope.revData={}
      $scope.revData.journal_id=data.data.item.id;
      $scope.revData.detail=[]
      $scope.revItem=data.data.item;
      var html="";
      angular.forEach(data.data.detail, function(val,i) {
        html+='<tr>'
        html+='<td>'+val.account.account_name+'</td>'
        if ($rootScope.in_array(val.account.no_cash_bank,[1,2])) {
          html+="<td><select class=\"form-control\" ng-model='revData.detail["+i+"].cash_category_id' data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
        } else {
          html+='<td>-</td>'
        }
        html+='<td>'+$filter("number")(val.debet)+'</td>'
        html+='<td>'+$filter("number")(val.credit)+'</td>'
        html+='</tr>'

        $scope.revData.detail.push({id:val.id,cash_category_id:val.cash_category_id})
      })
      $('#appendDetail tbody').html(html)
      $compile(angular.element($('#appendDetail tbody')[0]).contents())($scope);
      $('#postingRevModal').modal('show');
    });
  }



    $scope.approve=function() {
        var id = $scope.data.item.id
        var params = {}
        params.item = {}
        params.item[id] = 1
        $scope.disBtn = true;
        $http.post(baseUrl+'/finance/journal/approve', params).then(function(data) {
            $scope.disBtn = false;
            toastr.success(data.data.message)
            $scope.show()
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


  $scope.submitPosting=function() {
    $http.post(baseUrl+'/finance/journal/store_posting',$scope.revData).then(function(data) {
      toastr.success("Jurnal Telah Diposting","Berhasil!");
      $('#postingRevModal').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }

    $scope.undo_approve = function() {
        is_running = confirm("Apakah anda ingin membatalkan jurnal ini ?");
        if(is_running) {

            var id = $scope.data.item.id;
            $scope.disCancelBtn = true;
            $http.put(baseUrl+'/finance/journal/undo_approve/'+$stateParams.id).then(function(data) {
                toastr.success("Jurnal ini telah dibatalkan","Berhasil!");
                $scope.disCancelBtn = false;
                $scope.show()
            }, function(error) {
                $scope.disCancelBtn = false;
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
    }
    $scope.unposting = function() {
        $scope.disPostingBtn = true;
        $http.put(baseUrl+'/finance/journal/unposting', $scope.journalData).then(function(data) {
            $('#unpostingModal').modal('hide');
            toastr.success("Posting jurnal ini telah dibatalkan","Berhasil!");
            $scope.disPostingBtn = false;
            $scope.show()
        }, function(error) {
            $scope.disPostingBtn=false;
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
  $scope.unposting_modal = function() {
    $scope.journalData.unposting_reason = '';
    $('#unpostingModal').modal('show');
  }
});
app.controller('financeJournalCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $scope.data = {}
  $rootScope.pageTitle="Tambah Jurnal Umum";
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/journal/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.totDebet=0;
  $scope.totCredit=0;

  $scope.formData={
    company_id:compId,
    date_transaction: dateNow
  }

  if($stateParams.year != null) {
      $scope.formData.is_audit = 1
      $scope.formData.date_transaction = '31-12-' + $stateParams.year
  }
  var html="";
  var urutan=0;
  $scope.account=[];
  $scope.append=function() {
    html="";
    html+="<tr id='row-"+urutan+"'>";
    html+="<td><select ng-change='hitungDK()' class=\"form-control\" ng-model='formData.account_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Akun'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.code+' - '+s.name group by s.parent.name for s in data.account\"><option value=''></option></select></td>";
    // html+="<td><select ng-change='hitungDK()' class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' ng-disabled=\"formData.account_id["+urutan+"].type.id!==1 || formData.account_id["+urutan+"].parent_id !=2 \" data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    html+="<td><select ng-change='hitungDK()' class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    // html+="<td><input type='text' name='description_detail[]' class='form-control'></td>";
    html+="<td><input type='text' jnumber2 only-num ng-disabled=\"formData.credit["+urutan+"]>0\" ng-model='formData.debet["+urutan+"]' ng-keyup='hitungDK()' class='form-control debet' ng-init='formData.debet["+urutan+"]=0'></td>";
    html+="<td><input type='text' jnumber2 only-num ng-disabled=\"formData.debet["+urutan+"]>0\" ng-model='formData.credit["+urutan+"]' ng-keyup='hitungDK()' class='form-control credit' ng-init='formData.credit["+urutan+"]=0'></td>";
    html+="<td><input type='text' ng-model='formData.keterangan["+urutan+"]' class='form-control'></td>";
    html+="<td><a ng-click='hapus("+urutan+")'><span class='fa fa-trash' style='color:red;'></span></td>";
    html+="</tr>";
    html = $(html)

    $('#appendTable tbody').append($compile(html)($scope));
    urutan++;
    hitungDeKr();
  }

  $scope.hapus=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.account_id[ids];
    delete $scope.formData.debet[ids];
    delete $scope.formData.credit[ids];
    delete $scope.formData.keterangan[ids];
    delete $scope.formData.cash_category_id[ids];
    hitungDeKr();
  }

  var totD=0;
  var totK=0;
  function hitungDeKr() {
    // console.log($scope.formData.debet);
    totD=0;
    totK=0;
    totTr=Object.keys($scope.formData.credit).length;
    angular.forEach($scope.formData.debet,function(val,i) {
      if (!val) {
        return;
      }
      totD+=parseFloat(val);
    });
    angular.forEach($scope.formData.credit,function(val,i) {
      if (!val) {
        return;
      }
      totK+=parseFloat(val);
    });
    $scope.totDebet=totD;
    $scope.totCredit=totK;
    if(angular.isObject($scope.formData.account_id)){
      if(Object.keys($scope.formData.account_id).length < Object.keys($scope.formData.credit).length){
        $scope.disBtn=true;
      }
      else{
        angular.forEach($scope.formData.account_id,function(val,i) {
          if(val.parent_id==2){
            if(angular.isObject($scope.formData.cash_category_id)){
              console.log($scope.formData.cash_category_id[i]);
              if(!angular.isUndefined($scope.formData.cash_category_id[i])){
                totTr=parseInt(totTr)-parseInt(1);
              }
            }
          }else{
            totTr=parseInt(totTr)-parseInt(1);
          }
        });
        if(totTr==0){
          $scope.disBtn=false;
        }else{
          $scope.disBtn=true;
        }
      }
    }else{
      $scope.disBtn=true;
    }
    // angular.forEach($scope.formData.credit,function(val,i) {
    //   totK+=parseFloat(val);
    //   if(val==0 && $scope.formData.debet[i]==0){
    //     $scope.disBtn=true;
    //   }
    //
    // });
    // $scope.totDebet=totD;
    // $scope.totCredit=totK;
  }

  $scope.hitungDK=function() {
    hitungDeKr();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/journal?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.journal');
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
app.controller('financeJournalEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Jurnal Umum";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.disBtn = false
  $scope.totDebet=0;
  $scope.totCredit=0;
  $scope.cash_list=[];
  $scope.formData={}

  $http.get(baseUrl+'/finance/journal/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.accountAll=data.data.account;

    $scope.formData.company_id=data.data.item.company_id
    $scope.formData.date_transaction=$filter('minDate')(data.data.item.date_transaction)
    $scope.formData.type_transaction_id=data.data.item.type_transaction_id
    $scope.formData.description=data.data.item.description
    $scope.formData.parent_id=[]
    $scope.formData.debet=[]
    $scope.formData.credit=[]
    $scope.formData.cash_category_id=[]
    $scope.formData.account_id=[]

    angular.forEach(data.data.item.details, function(val,i) {
      $scope.formData.parent_id.push(val.account.parent_id);
      $scope.formData.cash_category_id.push(val.cash_category_id);
      $scope.formData.account_id.push(val.account.id);
      $scope.formData.debet.push(parseFloat(Math.round(val.debet*100)/100).toFixed(2))
      $scope.formData.credit.push(parseFloat(Math.round(val.credit*100)/100).toFixed(2))
      // $scope.totDebet+=val.debet;
      // $scope.totCredit+=val.credit;
      urutan++;
    });

    angular.forEach(data.data.account, function(val,i) {
      if (val.type.id==1) {
        $scope.cash_list.push(val.id);
      }
    });
    $scope.hitungDK()
    $('.ibox-content').toggleClass('sk-loading');
  }, function(err) {
    toastr.error("Transaksi selain dari menu Jurnal Umum tidak dapat di Edit.","Maaf!");
    $state.go('finance.journal');
  });

  $scope.totDebet=0;
  $scope.totCredit=0;

  var html="";
  var urutan=0;
  $scope.account=[];
  $scope.append=function() {
    html="";
    html+="<tr id='row-"+urutan+"'>";
    html+="<td><select  ng-change='hitungDK()' class=\"form-control\" ng-model='formData.account_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Akun'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.id as s.code+' - '+s.name group by s.parent.name for s in data.account\"><option value=''></option></select></td>";
    // html+="<td><select ng-change='hitungDK()' class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' ng-disabled=\"cash_list.indexOf(formData.account_id["+urutan+"])===-1 ||  formData.parent_id["+urutan+"]!=2\" data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    html+="<td><select ng-change='hitungDK()' class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-model='account["+urutan+"]' ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    // html+="<td><input type='text' name='description_detail[]' class='form-control'></td>";
    html+="<td><input type='text' jnumber2 only-num ng-model='formData.debet["+urutan+"]' ng-disabled=\"formData.credit["+urutan+"]>0\" ng-keyup='avoidZero(formData.debet["+urutan+"]);' class='form-control debet' ng-init='formData.debet["+urutan+"]=0'></td>";
    html+="<td><input type='text' jnumber2 only-num ng-model='formData.credit["+urutan+"]' ng-disabled=\"formData.debet["+urutan+"]>0\" ng-keyup='avoidZero(formData.credit["+urutan+"]);' class='form-control credit' ng-init='formData.credit["+urutan+"]=0'></td>";
    html+="<td><a ng-click='hapus("+urutan+")' class='btn btn-sm btn-rounded btn-danger'>Delete</td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    urutan++;
    hitungDeKr();
    $scope.disBtn = false
  }

  $scope.avoidZero=function(model) {
    $scope.hitungDK()
  }

  $scope.hapus=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.account_id[ids];
    delete $scope.formData.debet[ids];
    delete $scope.formData.credit[ids];
    delete $scope.formData.cash_category_id[ids];
    hitungDeKr();
  }

  $compile($('#sbtBtn'))($scope)

  $scope.truncate_float = function () {
    angular.forEach($scope.formData.debet,function(val,i) {
        $scope.formData.debet[i] = parseFloat(val).toFixed(2);
    });
    angular.forEach($scope.formData.credit,function(val,i) {
        $scope.formData.credit[i] = parseFloat(val).toFixed(2);
    });
    hitungDeKr();
  }

  var totD=0;
  var totK=0;
  function hitungDeKr() {
    // console.log($scope.formData.debet);
    totD=0;
    totK=0;
    totTr=Object.keys($scope.formData.credit).length;
    angular.forEach($scope.formData.debet,function(val,i) {
      if (!val) {
        val=0;
      }
      totD+=parseFloat(val);
    });

    if(Object.keys($scope.formData.account_id).length < Object.keys($scope.formData.credit).length){
      $scope.disBtn=true;
    }
    else{
      angular.forEach($scope.formData.account_id,function(val,i) {
        if($scope.accountAll[val] !== undefined) {
            $scope.formData.parent_id[i]=$scope.accountAll[val].parent_id;
            if($scope.formData.parent_id[i]==2){
                // console.log($scope.formData.cash_category_id);
                if(!angular.isUndefined($scope.formData.cash_category_id[i])){
                totTr=parseInt(totTr)-parseInt(1);
                }
            }else{
            totTr=parseInt(totTr)-parseInt(1);
            }
        }
      });
      if(totTr==0){
        $scope.disBtn=false;
      }else{
        $scope.disBtn=true;
      }
    }
    angular.forEach($scope.formData.credit,function(val,i) {
      if (!val) {
        val=0;
      }
      totK+=parseFloat(val);
      if(val==0 && $scope.formData.debet[i]==0){
        $scope.disBtn=true;
      }

    });
    $scope.totDebet=totD;
    $scope.totCredit=totK;

  }

  $scope.hitungDK=function() {
    hitungDeKr();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/journal/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.journal');
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
app.controller('financeJournalFavorite', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Transaksi Favorit";
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/journal/create_favorite').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.formData={
    company_id:compId,
    date_transaction:dateNow,
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/journal/store_favorite?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.journal');
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
