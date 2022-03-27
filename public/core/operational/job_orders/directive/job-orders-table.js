jobOrders.directive('jobOrdersTable', function () {
    return {
        restrict: 'E',
        scope: {
            is_depo_service : '=isDepoService',
            show_invoice : '=showInvoice',
        },
        transclude:true,
        templateUrl: '/core/operational/job_orders/view/job-orders-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $state, $timeout, jobOrdersService, additionalFieldsService) {
            $rootScope.source = null
            $rootScope.pageTitle="Job Order";
            $('.ibox-content').addClass('sk-loading');
            $scope.isFilter = false;
            $scope.serviceStatus = [];
            $scope.formData = {}
            $scope.checkData = {}
            $http.get(baseUrl+'/operational/job_order').then(function(data) {
                $scope.data=data.data;
            });

            additionalFieldsService.dom.getInIndexKey('jobOrder', function(list){
                var additional_fields = list
                var using_branch = $rootScope.settings.job_order.using_branch
                var columns = [
                        {
                            data:null,
                            name:"job_orders.code",
                            className:'font-bold',
                            render : function(resp) {
                                if($rootScope.roleList.includes('operational.job_order.detail')) {
                                    return '<a ui-sref="operational.job_order.show({id:' + resp.id + '})">' + resp.code + '</a>'
                                } else {
                                    return resp.code
                                }
                            }
                        },
                        {
                            data:null,
                            name:'shipment_date',
                            searchable:false,
                            render: resp => $filter('fullDate')(resp.shipment_date)
                        },
                        {data:"customer_name",name:"contacts.name",className:"font-bold"},
                        {data:"service_name",name:"services.name"},
                        {data:"route_name",name:"routes.name"},
                        {data:"kpi_status_name",name:"kpi_statuses.name",className:""},
                ]
                if(using_branch == 1) {
                    columns.splice(2, 0, {
                        'data' : 'company_name',
                        'name' : 'companies.name'
                    })
                }


                var columnDefs =  [
                    { "title": "No. Job Order"},
                    { "title": "Date"},
                    { "title": "Customer"},
                    { "title": "Service"},
                    { "title": "Route"},
                    { "title": "Status"},
                ]
                if($scope.show_invoice == 1) {
                    columns.splice(2, 0, {
                        'data' : 'code_invoice',
                        'name' : 'invoices.code'
                    })
                    columnDefs.splice(2, 0, {
                        'title' : $rootScope.solog.label.invoice.code
                    })
                }
                if(using_branch == 1) {
                    columnDefs.splice(2, 0, {
                        'title' : 'Branch'
                    })
                }


                for(x in additional_fields) {
                    columns.push({
                        data : additional_fields[x].slug,
                        name : 'additional_job_orders.' + additional_fields[x].slug
                    })
                    columnDefs.push({
                        title : additional_fields[x].name
                    })
                }

                columns.push({
                    data:null,
                    className:"text-center",
                    orderable:false,
                    searchable:false,
                    render : function (item) {
                        var html = '' 
                        html += "<a ng-show=\"$root.roleList.includes('operational.job_order.detail')\" ui-sref='operational.job_order.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;"
                        if(!item.invoice_id) {
                            html += "<a ng-show=\"$root.roleList.includes('operational.job_order.edit')\" ui-sref='operational.job_order.edit({id:" + item.id + "})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                            html += "<a ng-show=\"$root.roleList.includes('operational.job_order.delete')\" ng-click='deletes(" + item.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>"
                        }

                        return html
                    }
                })
                columnDefs.push({ "title": ""})

                columnDefs = columnDefs.map((c, i) => {
                    c.targets = i
                    return c
                })

                oTable = $('#datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    scrollX:false,
                    dom: 'Blfrtip',
                    order:[[1, 'desc'], [0, 'desc']],
                    initComplete : null,
                    ajax: {
                        headers : {'Authorization' : 'Bearer '+authUser.api_token},
                        url : baseUrl+'/api/operational/job_order_datatable',
                        data: function(d){
                            d.customer_id = $scope.formData.customer_id;
                            d.is_done = $scope.formData.is_done;
                            d.start_date = $scope.formData.start_date;
                            d.end_date = $scope.formData.end_date;
                            d.service_id = $scope.formData.service;
                            // d.kpi_id = $scope.formData.status;
                            d.kpi_status_name = $scope.formData.kpi_status_name;
                            d.is_operational_done=0
                            d.show_invoice = $scope.show_invoice
                            if($scope.is_depo_service) {
                                d.is_depo_service = $scope.is_depo_service 
                            }
                        },
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
                            'filename' : 'Job Order - '+new Date(),
                            'sheetName' : 'Data',
                            'title' : 'Job Order'
                        },
                    ],

                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function(row, data, dataIndex) {
                        $compile(angular.element(row).contents())($scope);
                    }
                });
                oTable.buttons().container().appendTo( '#export_button' );
            })

            $scope.add = function() {
                $rootScope.insertBuffer()
                $state.go('operational.job_order.create')
            }

            $scope.disableArchive=true
            $scope.isCheck=function() {
                $scope.disableArchive=true
                angular.forEach($scope.checkData.detail, function(val,i) {
                    if (val.value) {
                        return $scope.disableArchive=false;
                    }
                })
            }

            $scope.submitArchive=function() {
                var cofs=confirm("Apakah anda yakin ?");
                if (!cofs) {
                    return null;
                }
                $http.post(baseUrl+'/operational/job_order/store_archive',$scope.checkData).then(function(data) {
                    $state.reload()
                    toastr.success("Job Order berhasil dipindahkan ke arsip");
                });
            }

            $scope.toggleFilter=function()
            {
                $scope.isFilter = !$scope.isFilter
            }
            $scope.serviceChange=function()
            {
                let id = $scope.formData.service
                // find service
                let service = $filter('filter')($scope.data.services, {id: id}, true)

                $scope.serviceStatus = service[0].kpi_statuses
                return
            }
            $scope.filterJobOrder=function()
            {
                $scope.formData.is_done = null
                oTable.ajax.reload()
                return
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
                $scope.formData.customer_id=null
                $scope.formData.is_done=null
                $scope.formData.start_date=null
                $scope.formData.end_date=null
                $scope.formData.service=null
                $scope.formData.status=null
                $scope.formData.kpi_status_name=null
                $('#cari_data').trigger('click');
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
        }
    }
});