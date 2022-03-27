app.controller('closing', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Closing";

    $scope.isEdit=false
    $scope.isSubmit=false

    $scope.status = [
        {id: 0, name: 'Un Closing'},
        {id: 1, name: 'Closing'},
    ]

    $scope.formData = {}
    $scope.formData.closingDate=dateNow
    $scope.formData.companyId=''
    $scope.formData.startPeriode=''
    $scope.formData.endPeriode=''
    $scope.formData.closingDate=''
    $scope.formData.description='-'
    $scope.formData.isLock=''
    $scope.formData.isDepresiasi=''
    $scope.formData._token=csrfToken

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/finance/closing',
            data: function(d) {
                d.filterData=$scope.filterData;
            }
        },
        columns:[
            {data:"code",name:"code"},
            {data:"company.name",name:"company.name"},
            {data:"closing_date",name:"closing_date"},
            {data:"periode", name:"periode"},
            {data:"description",name:"description"},
            {
                data:null,
                orderable:false,
                searchable:false,
                render: function(resp) {
                    var r = '';
                    if(resp.is_lock == 0) {
                        r = "Tidak"
                    } else if(resp.is_lock == 1) {
                        r = "Ya"
                    }

                    return r
                }
            },
            {data:"status_label",name:"status_label", className:"text-center"},
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('finance.closing.edit')) {
                $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
                $(row).find('td:last-child').removeAttr('ng-click')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.reset=function(){
        $scope.formData.closingDate=dateNow
        $scope.formData.companyId=''
        $scope.formData.periode=''
        $scope.formData.closingDate=''
        $scope.formData.description=''
        $scope.formData.isLock=''
        $scope.formData.isDepresiasi=''
        $scope.formData.status=''
    }

    $scope.create=function() {
        $scope.isEdit = false;
        $scope.formData.closingDate=dateNow

        $http.get(baseUrl+'/finance/closing/create').then(function success(data) {
            $scope.data = data.data;
            var date= new Date();
            if(data.data.lastClosingDate == null) 
            var date= new Date(data.data.lastClosingDate.end_periode);

            $scope.formData.periode = (date.getMonth()+2)+'-'+date.getFullYear()
        });

        $scope.modalFormTitle='Closing';
        $scope.url=baseUrl+'/finance/closing';
        $scope.method='post';

        $scope.formData.isLock=1
        $scope.formData.isDepresiasi=1

        $('#formModal').modal('show');
    }

    $scope.edit=function(ids) {
        $scope.isEdit=true
        $scope.modalFormTitle='Edit Closing';
        $scope.url=baseUrl+'/finance/closing/'+ids;
        $scope.method='put';

        $http.get(baseUrl+'/finance/closing/'+ids+'/edit').then(function success(data) {
            $scope.data = data.data

            let periode= new Date(data.data.closing.end_periode);
            let closingDate= new Date(data.data.closing.closing_date);

            $scope.formData.companyId=data.data.closing.company_id
            $scope.formData.periode=(periode.getMonth()+1)+'-'+periode.getFullYear()
            $scope.formData.closingDate=$filter('minDate')($scope.data.closing.closing_date)
            $scope.formData.description=data.data.closing.description
            $scope.formData.isDepresiasi=data.data.closing.is_depresiasi
            $scope.formData.isLock=data.data.closing.is_lock
            $scope.formData.status=data.data.closing.status

            $('#formModal').modal('show');
        });
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/finance/cash_transaction/'+ids,{_token:csrfToken}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }
  
    $scope.posting=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.post(baseUrl+'/finance/closing/'+ids + '/posting',{_token:csrfToken}).then(function success(data) {
            oTable.ajax.reload();
            toastr.success("Closing berhasil diposting");
        }, function error(data) {
            toastr.error("Error Has Found!");
        });
        }
    }  
  
    $scope.cancelPosting=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
        $http.delete(baseUrl+'/finance/closing/'+ids + '/posting',{_token:csrfToken}).then(function success(data) {
            oTable.ajax.reload();
            toastr.success("Closing berhasil batal posting");
        }, function error(data) {
            toastr.error("Error Has Found!");
        });
        }
    }
  
    $scope.submit=function(ids) {
        $scope.isSubmit=true;
        var payload = $scope.formData
        $http[$scope.method]($scope.url, payload).then(function(resp) {
            $scope.isSubmit=false;
            toastr.success(resp.data.message)
            $('#formModal').modal('hide');
            oTable.ajax.reload();
        }, function(error) {
            $scope.isSubmit=false;
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
  
    $scope.rollback=function(ids) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            $scope.isSubmit=true;
            var payload = $scope.formData
            $http.put(baseUrl+'/finance/closing/'+ids + '/rollback').then(function(resp) {
                $scope.isSubmit=false;
                toastr.success(resp.data.message)
                oTable.ajax.reload();
            }, function(error) {
                $scope.isSubmit=false;
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

});
