app.controller('financeKasBon', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {

    $rootScope.pageTitle="Kas Bon";
    $rootScope.currentPage="kas_bon";
    $('.ibox-content').addClass('sk-loading');
    $scope.loadFinishedCount = 0;
    $scope.isKasir = $rootScope.roleList.includes('finance.cash_bon.detail.money_out')
        || $rootScope.roleList.includes('finance.cash_bon.detail.save_and_approve')
        || $rootScope.roleList.includes('finance.cash_bon.detail.reject');

    $scope.isAdmin = $rootScope.roleList.includes('finance.cash_bon.detail.money_out')
        && $rootScope.roleList.includes('finance.cash_bon.detail.save_and_approve')
        && $rootScope.roleList.includes('finance.cash_bon.detail.reject');
    var cashTransactionTable = null;
    oTable = null;
    $scope.load = function() {
        oTable = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            order:[[0,'desc']],
            ajax: {
                headers : {'Authorization' : 'Bearer '+authUser.api_token},
                url : baseUrl+'/api/finance/kas_bon_datatable',
                data: {
                    isManager: ($rootScope.roleList.includes('finance.cash_bon.detail.save_and_approve') || $rootScope.roleList.includes('finance.cash_bon.detail.reject')) ? 1 : 0
                },
                dataSrc: function(d) {
                  $scope.isFinished();
                  return d.data;
                }
            },
            columns:[
                {data:"code",name:"code"},
                {data:"company.name",name:"company.name",className:"font-bold"},
                {data:"employee.name",name:"employee.name"},
                {data:"date_transaction",name:"date_transaction"},
                {data:"total_cash_advance",name:"total_cash_advance",className:"text-right"},
                {data:"description",name:"description"},
                {data:"status",name:"status"},
                {data:"reapprovals",name:"reapprovals",className:"text-right"},
                {data:"action",name:"action",className:"text-center"},
            ],
            createdRow: function(row, data, dataIndex) {
                if($rootScope.roleList.includes('finance.cash_bon.detail')) {
                    $(row).find('td').attr('ui-sref', 'finance.kas_bon.show({id:' + data.id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                } else {
                    $(oTable.table().node()).removeClass('table-hover')
                }
               $compile(angular.element(row).contents())($scope);
            }
        });

        cashTransactionTable = $('#cash_transaction_datatable').DataTable({
            processing: true,
            serverSide: true,
            order:[[8,'desc']],
            ajax: {
              headers : {'Authorization' : 'Bearer '+authUser.api_token},
              url : baseUrl+'/api/finance/cash_transaction_datatable',
              data: {
                by_user : 1
              },
              dataSrc: function(d) {
                $scope.isFinished();
                return d.data;
              }
            },
            columns:[
                {data:"code",name:"code"},
                {data:"company.name",name:"company.name"},
                {data:"date_transaction",name:"date_transaction"},
                {data:"type_transaction.name",name:"type_transaction.name"},
                {data:"total",name:"total"},
                {data:"description",name:"description"},
                {data:"status",name:"status"},
                {data:"status_cost",name:"status_cost"},
                {data:"action",name:"created_at",className:"text-center"},
            ],
            createdRow: function(row, data, dataIndex) {
                if($rootScope.roleList.includes('finance.transaction_cash.detail')) {
                    $(row).find('td').attr('ui-sref', 'finance.cash_transaction.show({id:' + data.id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                } else {
                    $(oTable.table().node()).removeClass('table-hover')
                }
                $compile(angular.element(row).contents())($scope);
            }
        });


        $http.get(baseUrl+'/finance/kas_bon')
            .then(function(data)
        {
            $scope.hasKasbon = data.data.hasKasbon;
            $scope.isFinished();
        });
    };

    $scope.isFinished = function(){
        $scope.loadFinishedCount++;
        if($scope.loadFinishedCount == 3)
            $scope.finishLoad();
    };

    $scope.finishLoad = function() {
        $('.ibox-content').removeClass('sk-loading');
    };

    $scope.approve=function(id) {
        // console.log(id)
        var conf=confirm("Apakah anda ingin mengajukan biaya ini ?")
        if (conf) {
          $http.post(baseUrl+'/finance/cash_transaction/approve/'+id).then(function(data) {
            $('.ibox-content').addClass('sk-loading');
            cashTransactionTable.ajax.reload();
            $('.ibox-content').removeClass('sk-loading');
            toastr.success("Data Berhasil Disimpan!");
          },function(err) {
            toastr.error(err.data.message,"Maaf!");
            $('.ibox-content').removeClass('sk-loading');
          });
        }
    }

    $scope.load();

});

app.controller('financeKasBonCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Tambah Kas Bon";
    var now = new Date();
    $scope.formData={}
    $scope.formData.company_id=compId
    $scope.formData.date_transaction=now.toLocaleDateString('en-GB');
    $scope.formData.total_cash_advance=0
    
    $http.get(baseUrl+'/finance/kas_bon/create')
        .then(function(data)
    {
        $scope.data = data.data;
        $scope.formData.employee_id = $scope.data.employee[0].id;
    });

    $scope.onModifyDate = function() {
        var splitDate = $scope.formData.date_transaction.split("-");
        var trDate = new Date(splitDate[2]+"-"+splitDate[1]+"-"+splitDate[0]);
        trDate.setDate(trDate.getDate() + 1);

        $scope.formData.due_date = trDate.toLocaleDateString('en-GB'); //dd + '-' + mm + '-' + yyyy;
    }
    $scope.onModifyDate();

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/finance/kas_bon',$scope.formData)
            .then(function(data)
        {
            $state.go('finance.kas_bon');
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

app.controller('financeKasBonShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Kas Bon";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={}
    $scope.created_user_id=userProfile.id
    $scope.kasbonUrl = baseUrl+'/finance/kas_bon/';
    $scope.postUrl=baseUrl+'/finance/kas_bon/approve/'+$stateParams.id;
    $scope.reapprovalUrl = $scope.kasbonUrl+'reapproval/'+$stateParams.id;
    $scope.canCreateKas = true;
    $scope.canFinish = false;
    $scope.status=[
        {id:1, name:'Belum Disetujui'},
        {id:2, name:'Sudah Disetujui'},
        {id:3, name:'Aktif'},
        {id:4, name:'Reapproval'},
        {id:5, name:'Kedaluwarsa'},
        {id:6, name:'Selesai'},
        {id:7, name:'Ditolak'}
    ];

    $scope.hasRole = function(role) {
        var roleStr = 'finance.cash_bon.detail.' + role;
        return $rootScope.roleList.includes(roleStr);
    };

    $scope.roles = {};
    $scope.roles.canReject = $scope.hasRole('reject');
    $scope.roles.canApprove = $scope.hasRole('save_and_approve');
    $scope.roles.canCan = $scope.hasRole('money_out');
    $scope.roles.isManager = $scope.hasRole('reject') || $scope.hasRole('save_and_approve');
    $scope.roles.isKasir = $scope.hasRole('money_out');
    $scope.roles.isAdmin = $scope.roles.isManager && $scope.roles.isKasir;
    $scope.roles.isOther = !($scope.roles.isManager || $scope.roles.isKasir);

    $scope.edit = function() {
        $state.go('finance.kas_bon.edit',{id:$stateParams.id})
    }

    $scope.show = function() {
        $http.get(baseUrl+'/finance/kas_bon/'+$stateParams.id).then(function(data) {
            $scope.item=data.data.item;
            $scope.cash_transaction_amount=data.data.cash_transaction_amount;
            $scope.item.cash_transactions = data.data.cash_transaction;
            $scope.item.status = $scope.item.status_akhir.status;
            $scope.data=data.data;
            var def=data.data.default;

            if($scope.item.cash_transaction_id != null)
                $scope.canCreateKas = false;
            
            if(!$scope.canCreateKas &&
                $scope.item.cash_transactions.status_cost == 3)
                $scope.canFinish = true;
            
            $scope.formData.due_date=$filter('minDate')($scope.item.due_date);
            $scope.formData.total_approve=$scope.item.total_cash_advance;
            $scope.formData.account_advance_id=def.account_kasbon_id;

            if ($scope.item.status==1) {
                $scope.postUrl=baseUrl+'/finance/kas_bon/approve/'+$stateParams.id;
            } else if ($scope.item.status==2) {
                $scope.postUrl=baseUrl+'/finance/kas_bon/cash_out/'+$stateParams.id;
            }

            $('.ibox-content').removeClass('sk-loading');
        }, function(){
            $scope.show()
        });
    }

    $scope.show()
    

    $scope.gotoCash = function(ct_id) {
        $state.go('finance.cash_transaction.show',{id: ct_id});
    }

    $scope.cancelData={}
    $scope.cancelModal=function() {
        $scope.cancelData={}
        $('#modalTolak').modal('show');
    }

    $scope.cancel=function() {
        $http.post(baseUrl+'/finance/kas_bon/cancel/'+$stateParams.id,$scope.cancelData).then(function(data) {
            $('#modalTolak').modal('hide');
            toastr.success("Data Berhasil Disimpan.","Berhasil!");
            $timeout(function() {
                $state.reload();
            },1000)
        });
    }

    $scope.reapproval = function() {
        $http.post($scope.reapprovalUrl).then(function(data) {
            toastr.success("Pengajuan reapproval Berhasil Disimpan.","Berhasil!");
            $timeout(function() {
                $state.reload();
            },1000)
        });
    }

    $scope.reapprove = function() {
        $http.post($scope.kasbonUrl +'reapprove/'+ +$stateParams.id)
            .then(function(data) {
                toastr.success("Reapproval Berhasil Disimpan.","Berhasil!");
                $timeout(function() {
                    $state.reload();
                },1000)
            });
    }

    $scope.activate = function() {
        if(confirm('Apakah uang sudah diserahkan?')){
            $http.post($scope.kasbonUrl +'activate/'+ +$stateParams.id)
                .then(function(data) {
                    toastr.success("Data Berhasil Disimpan.","Berhasil!");
                    $timeout(function() {
                        $state.reload();
                    },1000)
                });
        }
    }

$scope.close = function() {
        var offset = $scope.item.total_approve  - $scope.cash_transaction_amount
        if(offset != 0) {
            is_approve = confirm('Selisih = ' + $filter('number')(offset) + '. Apakah anda yakin ?')
            if(is_approve) {
                $http.post($scope.kasbonUrl +'close/'+ +$stateParams.id)
                .then(function(data) {
                    toastr.success("Data Berhasil Disimpan.","Berhasil!");
                    $timeout(function() {
                        $state.reload();
                    },1000)
                });
            }
        } else {
            $http.post($scope.kasbonUrl +'close/'+ +$stateParams.id)
            .then(function(data) {
                toastr.success("Data Berhasil Disimpan.","Berhasil!");
                $timeout(function() {
                    $state.reload();
                },1000)
            });
        }
    }
    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post($scope.postUrl,$scope.formData).then(function(data) {
            // $state.go('finance.kas_bon');
            $state.reload();
            toastr.success("Kasbon telah disetujui.","Berhasil!");
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

app.controller('financeKasBonEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Kas Bon";
  $scope.formData={}

  $http.get(baseUrl+'/finance/kas_bon/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.company_id=dt.company_id
    $scope.formData.date_transaction=$filter('minDate')(dt.date_transaction)
    $scope.formData.due_date=$filter('minDate')(dt.due_date)
    $scope.formData.driver_id=dt.employee_id
    $scope.formData.total_cash_advance=dt.total_cash_advance
    $scope.formData.description=dt.description
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/finance/kas_bon/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.kas_bon');
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
