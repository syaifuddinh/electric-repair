app.controller('operationalVendorJob', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.source = null
    $rootScope.pageTitle="Vendor Job";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {}
    $http.get(baseUrl+'/operational/job_order').then(function(data) {
        $scope.data=data.data;
    });
    
    $scope.showCustomer = function() {
        $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
            $scope.customers=data.data;
        }, function(){
            $scope.showCustomer()
        });
    }
    $scope.showCustomer()
    
    $scope.showVendor = function() {
        $http.get(baseUrl+'/contact/contact/vendor').then(function(data) {
            $scope.vendors=data.data;
        }, function(){
            $scope.showVendor()
        });
    }
    $scope.showVendor()
    
    $scope.showVendorJobStatus = function() {
        $http.get(baseUrl+'/setting/vendor_job_status').then(function(data) {
            $scope.vendor_job_statuses=data.data;
        }, function(){
            $scope.showVendorJobStatus()
        });
    }
    $scope.showVendorJobStatus()
    
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX:false,
        dom: 'Blfrtip',
        initComplete : null,
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/operational/vendor_job_datatable',
            data: d => Object.assign(d, $scope.filterData),
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        buttons: [
            {
                'extend' : 'excel',
                'enabled' : true,
                'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                'className' : 'btn btn-default btn-sm',
                'filename' : 'Vendor Job - '+new Date(),
                'sheetName' : 'Data',
                'title' : 'Vendor Job'
            },
        ],
        
        columns:[
            {data:"company_name"},
            {data:"customer_name"},
            {data:"source_name"},
            {data:"code"},
            {data:"vendor_name"},
            {data:"cost_type_name"},
            {
                data:null,
                className:'text-right',                 
                render : function(r){
                    return $filter('number')(r.qty)
                } 
            },
            {
                data:null,
                className:'text-right',                 
                render : function(r){
                    return $filter('number')(r.price)
                } 
            },
            {
                data:null,
                className:'text-right',                 
                render : function(r){
                    return $filter('number')(r.total_price)
                } 
            },
            {data:"vendor_job_status_name"},
            {
                data:null,
                className:"text-center",                
                render : function(resp) {
                    var r = '<a ng-click=\'edit(' + JSON.stringify(resp) + ')\'><span class="fa fa-edit"></span></a>'
                    return r;
                }
            },
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo( '#export_button' );

    $scope.edit =  function(r) {
        $scope.formData.vendor_job_status_id = parseInt(r.vendor_job_status_id)
        $scope.formData.source = r.source
        $scope.formData.id = r.id
        $('#modal').modal()
    }
    
    $scope.toggleFilter=function()
    {
        $scope.isFilter = !$scope.isFilter
    }
    $scope.filter=function()
    {
        oTable.ajax.reload()
    }
    
    $scope.notifData={}
    $scope.sendNotification=function() {
        $('#notifModal').modal()
    }
    
    $scope.submitNotif=function() {
        var cofs=confirm("Apakah anda yakin ?");
        if (!cofs) {
            return null;
        }
        $http.post(baseUrl+'/operational/job_order/send_notification',$scope.notifData).then(function(data) {
            toastr.success("Pesan Berhasil Dikirim!");
            $scope.notifData={}
            $('#notifModal').modal('hide')
        });
    }
    
    $scope.resetFilter=function()
    {
        $scope.filterData = {}
        oTable.ajax.reload()
    }

    $scope.submitForm=function() {
        var url = baseUrl + '/operational/vendor_job/' + $scope.formData.source + '/' + $scope.formData.id
        $scope.disBtn=true;
        $http.put(url,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            toastr.success("Data Berhasil Disimpan!");
            oTable.ajax.reload();
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

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/operational/job_order/'+ids,{_token:csrfToken}).then(function success(data) {
                // $state.reload();
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }
    
});
