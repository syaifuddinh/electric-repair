app.controller('operationalJobOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, additionalFieldsService) {
    $rootScope.emptyBuffer()
});

app.controller('operationalJobOrderInvoice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, additionalFieldsService) {
});

app.controller('operationalJobOrderArchive', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Job Order";
    $('.ibox-content').addClass('sk-loading');
    $scope.isFilter = false;
    $scope.serviceStatus = [];
    $scope.formData = {}
    $http.get(baseUrl+'/operational/job_order').then(function(data) {
        $scope.data=data.data;
    });

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[10,'desc']],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/operational/job_order_datatable',
            data: function(d){
                d.customer_id = $scope.formData.customer_id;
                d.is_done = $scope.formData.is_done;
                d.start_date = $scope.formData.start_date;
                d.end_date = $scope.formData.end_date;
                d.service_id = $scope.formData.service;
                d.kpi_id = $scope.formData.status;
                d.is_operational_done=1
            },
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
            {data:"service.name",name:"service.name"},
            {data:"work_order.code",name:"work_order.code"},
            {data:"code",name:"code"},
            {data:"no_bl",name:"no_bl"},
            {data:"aju_number",name:"aju_number"},

            {data:"customer.name",name:"customer.name",className:"font-bold"},
            {data:"trayek.name",name:"trayek.name"},
            {
                data:null,
                searchable:false,
                name:"shipment_date",
                render:resp => $filter('fullDate')(resp.shipment_date)
            },
            {data:"no_po_customer",name:"no_po_customer",className:"font-bold"},
            {data:"kpi_status.name",name:"kpi_status.name",className:""},
            {
                data:null,
                name:"created_at",
                className:"text-center",
                render:function(resp) {
                    var action = resp.action;
                    action = action.replace(/(.+)roleList\.includes\('([a-z_\.]+)\.detail'\)(.+)/, "$1roleList.includes('operational.job_order_archive.detail')$3")
                    action = action.replace(/(.+)roleList\.includes\('([a-z_\.]+)\.edit'\)(.+)/, "$1roleList.includes('operational.job_order_archive.edit')$3")
                    action = action.replace(/(.+)roleList\.includes\('([a-z_\.]+)\.delete'\)(.+)/, "$1roleList.includes('operational.job_order_archive.delete')$3")

                    return action;
                }
            },
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('operational.job_order_archive.detail')) {
                $(row).find('td').attr('ui-sref', 'operational.job_order.show({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });
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

});

app.controller('operationalJobOrderCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.is_merchandise = null
    $rootScope.job_order.is_pallet = null
});

app.controller('operationalJobOrderShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, additionalFieldsService) {
    $rootScope.pageTitle="Detail Job Order";
    if ($state.current.name=="operational.job_order.show") {
        $state.go('operational.job_order.show.detail',{},{location:'replace'});
    }

    $rootScope.job_order.is_pallet = null

    additionalFieldsService.dom.get('jobOrder', function(list){
        $scope.additional_fields = list
    })

    $rootScope.is_merchandise = null
});

app.controller('operationalJobOrderShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, additionalFieldsService) {
    $scope.pl = {}
    $rootScope.source = 'detail_jo'
    $rootScope.pageTitle="Detail Job Order";
    $('.sk-container').addClass('sk-loading');
    $scope.item = {}
    $scope.data = {}

    $scope.state=$state;
    $(".clockpick").clockpicker({
        placement:'right',
        autoclose:true,
        donetext:'DONE',
    });

    
    $scope.imposition=[
        {id:1,name:'Kubikasi'},
        {id:2,name:'Tonase'},
        {id:3,name:'Item'},
    ]
    $scope.type_cost=[
        {id:1,name:'Biaya Operasional'},
        {id:2,name:'Reimbursement'},
    ]

    additionalFieldsService.dom.get('manifest', function(list){
        $scope.additional_manifest_fields = list
    })

    $scope.deleteTransit = function(id) {
        $scope.formData.transits = $scope.formData.transits.filter(x => x.id != id)
    }

    $scope.saveTransit = function() {
        var method, url
        if(!$scope.formTransit.id) {
            method = 'post'
            url = '/operational/job_order/' + $stateParams.id + '/transit'
        } else {
            method = 'put'
            url = '/operational/job_order/' + $stateParams.id + '/transit/' + $scope.formTransit.id
        }
        $rootScope.disBtn=true;
        $http[method](url, $scope.formTransit).then(function(data) {
            toastr.success(data.data.message);
            $rootScope.disBtn=false;
            $scope.showTransits()
            $('#modalTransit').modal('hide')
        }, function(error) {
            $rootScope.disBtn=false;
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
    $('#modalTransit').on('hidden.bs.modal', () => {
        $scope.showTransits()
    })

    $scope.editTransit = function(id) {
        $scope.titleTransit = 'Update'
        var formTransit = $scope.transits.find(x => x.id == id)
        $scope.formTransit = formTransit
        $('#modalTransit').modal('show')
    }

    $scope.deleteTransit = function(id) {
        var conf = confirm('Are you sure ?')
        if(conf) {
            var method = 'delete'
            var url = '/operational/job_order/' + $stateParams.id + '/transit/' + id
            $http[method](url).then(function(data) {
            toastr.success(data.data.message);
            $rootScope.disBtn=false;
            $scope.showTransits()
            $('#modalTransit').modal('hide')
        }, function(error) {
            $rootScope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        })
        }
    }

    $scope.addTransit = function() {
        $scope.titleTransit = 'Insert'
        $scope.formTransit = {}
        $('#modalTransit').modal()
    }

    $scope.showTransits = function() {
        $http.get(baseUrl+'/operational/job_order/' + $stateParams.id + '/transits').then(function(data) {
            $scope.transits=data.data;
        }, function(){
            $scope.showTransits()
        });
    }
    $scope.showTransits()

    $scope.revision=function(jsn) {
        $scope.revisiData={}
        $scope.revisiData.cost_id=jsn.id
        $scope.revisiData.cost_type_f=jsn.cost_type
        $scope.revisiData.qty=jsn.qty
        $scope.revisiData.price=jsn.price
        $scope.revisiData.total_price=jsn.total_price
        $scope.revisiData.before_revision_cost=jsn.total_price
        $scope.revisiData.description=jsn.description
        $scope.revisiData.vendor_id=jsn.vendor_id
        $('#revisiModal').modal('show');
    }

    $scope.editService=function(item) {
        $scope.serviceChangeData={}
        $scope.serviceChangeData.old_work_order_detail_id=item.work_order_detail_id
        $('#modalService').modal()
    }

    $scope.changeServiceSubmit=function() {
        $rootScope.disBtn=true;
        $http.post(baseUrl+'/operational/job_order/change_service/'+$stateParams.id,$scope.serviceChangeData).then(function(data) {
            $('#modalService').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000)
            toastr.success("Data Berhasil Disimpan !");
            $rootScope.disBtn=false;
        }, function(error) {
            $rootScope.disBtn=false;
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

    $scope.deleteArmada=function(id) {
        var cofs=confirm("Apakah anda yakin ?");
        if (!cofs) {
            return null;
        }
        $http.delete(baseUrl+'/operational/job_order/delete_armada/'+id, {params: {jo_id: $stateParams.id}}).then(function(data) {
            $scope.show()
            $rootScope.job_order.showDetail()
            toastr.success("Armada telah dihapus !");
        })
    }

    $scope.submitRevisi=function() {
        $rootScope.disBtn=true;
        $http.post(baseUrl+'/operational/job_order/store_revision/'+$scope.revisiData.cost_id,$scope.revisiData).then(function(data) {
            // $state.go('operational.job_order');
            $('#revisiModal').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000)
            toastr.success("Biaya telah direvisi !");
            $rootScope.disBtn=false;
        }, function(error) {
            $rootScope.disBtn=false;
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

    $scope.saveSubmission=function(id) {
        var conf=confirm("Apakah anda ingin menyimpan di jurnal ?");
        if (conf) {
            $http.post(baseUrl+'/operational/job_order/store_submission/'+id).then(function(data) {
                $state.reload()
                toastr.success("Pengajuan Biaya berhasil disimpan!");
            });
        }
    }

    $scope.detail_approve=[];
    $scope.show = function() {
        $http.get(baseUrl+'/operational/job_order/'+$stateParams.id).then(function(data) {
            $scope.item=data.data.item;
            $rootScope.item=data.data.item;
            $rootScope.customer_id = data.data.item.customer_id
            $scope.hasInvoice = data.data.item.invoice_id
            $scope.hasLCL = data.data.item.service_type_id == 1 || data.data.item.service_type_id == 12 || data.data.item.service_type_id == 13
            $scope.isShipment = $rootScope.in_array(data.data.item.service_type_id,[1,2,3,12,13,15])
            $scope.durasi=data.data.durasi;
            $scope.manifest=data.data.manifest;
            $scope.cost_type=data.data.cost_type;
            $scope.cost_detail=data.data.cost_detail;
            $scope.receipt_detail=data.data.receipt_detail;
            $scope.wo_detail=data.data.wo_detail;
            $scope.jenis_tarif = $scope.item.quotation_id != null ? 'Tarif Kontrak' : 'Tarif Umum';
            $scope.jenis_tarif = $scope.item.is_customer_price == 1 ? 'Tarif Customer' : $scope.jenis_tarif;
            var payload = {}
            payload.is_invoice = 0
            payload.company_id = data.data.item.company_id
            $('.sk-container').removeClass('sk-loading');
        });
    }
    $scope.show()
    
    $scope.$on('showJobOrder', function(e, v){
        $scope.show()
    })

    $rootScope.job_order.showDetail($stateParams.id)

    $scope.formArmadaLCL={}

    $scope.addArmada=function() {
        $scope.armadaData={};
        $scope.armadaData.qty=1;
        $('#modalArmada').modal('show');
    }
    $scope.addArmadaLCL=function() {
        $scope.formArmadaLCL={}
        $scope.formArmadaLCL.detail=[]
        angular.forEach($rootScope.job_order.detail, function(val,i) {
            $scope.formArmadaLCL.detail.push({
                detail_id:val.id,
                leftover:val.leftover,
                angkut:0
            })
        })
        $('#modalArmadaLCL').modal('show')
    }

    $scope.getDesc=function(description) {
        if(description == null)
        {
            return ''
        }

        return ' : '+s.description
    }

    $scope.cekAngkutanLCL=function() {
        var init=0
        angular.forEach($scope.formArmadaLCL.detail, function(val,i) {
            if (parseFloat(val.angkut) > parseFloat(val.leftover)) {
                init=-1;
                return;
            }

            init+=parseFloat(val.angkut)
        })

        if (init>0)
        return false;

        return true;
    }


    $scope.addReceipt=function() {
        $scope.receiptData={};
        $scope.receiptData.date_receive=dateNow;
        $('#modalReceipt').modal('show');
    }

    $rootScope.disBtn=false;
    $scope.submitArmada=function() {
        $rootScope.disBtn=true;
        $http.post(baseUrl+'/operational/job_order/add_armada/'+$stateParams.id,$scope.armadaData).then(function(data) {
            $('#modalArmada').modal('hide');
            $scope.show()
            toastr.success("Armada berhasil ditambahkan!");
            $rootScope.disBtn=false;
        }, function(error) {
            $rootScope.disBtn=false;
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


    $scope.submitArmadaLCL = function() {
        var cfs = confirm("Apakah anda yakin ?")
        if (cfs) {
            $rootScope.disBtn=true;
            let post_url = baseUrl+'/operational/job_order/submit_armada_lcl/' + $stateParams.id;
            $scope.formArmadaLCL.additional = $scope.pl
            $http.post(post_url, $scope.formArmadaLCL)
            .then(function(data) {
                $scope.show()
                $rootScope.job_order.showDetail()
                $('#modalArmadaLCL').modal('hide')
                toastr.success(data.data.message);
                $rootScope.disBtn = false;
            }, function(error) {
                $rootScope.disBtn=false;
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

    $scope.submitReceipt=function() {
        $rootScope.disBtn=true;
        $http.post(baseUrl+'/operational/job_order/add_receipt/'+$stateParams.id,$scope.receiptData).then(function(data) {
            // $state.go('operational.job_order');
            $('#modalReceipt').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000)
            toastr.success("Item Barang berhasil ditambahkan!");
            $rootScope.disBtn=false;
        }, function(error) {
            $rootScope.disBtn=false;
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

    $scope.cetak = function() {
        var paramsObj = {uniqid: $scope.item.uniqid, id:$scope.item.id };
        var params = $.param(paramsObj);
        var url = baseUrl + '/shipment?';
        url += params;
        //location.href = url;
        window.open(url);
    }


});

app.controller('operationalJobOrderShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Job Order";
    $scope.urls=baseUrl;
    $('.sk-container').addClass('sk-loading');

    $http.get(baseUrl+'/operational/job_order/show_document/'+$stateParams.id).then(function(data) {
        $scope.detail=data.data.detail;
        $('.sk-container').removeClass('sk-loading');
    });
    $scope.formData={}
    $scope.formData.is_customer_view=0

    $rootScope.disBtn=false;
    $scope.submitForm=function() {
        $rootScope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/operational/job_order/upload_file/'+$stateParams.id+'?_token='+csrfToken,
            contentType: false,
            cache: false,
            processData: false,
            data: new FormData($('#uploadForm')[0]),
            success: function(data){
                $scope.$apply(function() {
                    $rootScope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.reload();
                $timeout(function() {
                    $state.reload();
                },1000)
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $rootScope.disBtn=false;
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

    $scope.delete_file=function(id) {
        var cof=confirm("Apakah Anda Ingin Menghapus File ini ?");
        if (cof) {
            $http.delete(baseUrl+'/operational/job_order/delete_file/'+id).then(function(data) {
                $state.reload()
                toastr.success("Berkas Berhasil Dihapus");
            })
        }
    }

});

app.controller('operationalJobOrderShowProses', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Job Order";
    $('.sk-container').addClass('sk-loading');
    $scope.urls=baseUrl;

    $rootScope.job_order_id = $stateParams.id
    $rootScope.job_order.showKpiLog()
    $rootScope.job_order.showKpiStatus()
    $rootScope.disBtn=false;
});

app.controller('operationalJobOrderEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Edit Job Order";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};
    $scope.contact_address=[];

    additionalFieldsService.dom.get('jobOrder', function(list){
        $scope.additional_fields = list
    })

    $http.get(baseUrl+'/operational/job_order/'+$stateParams.id+'/edit').then(function(data) {
        $scope.item=data.data.item;
        $scope.data=data.data;
        $scope.detail_jasa=data.data.detail_jasa;
        angular.forEach(data.data.address,function(val,i) {
            $scope.contact_address.push(
                {id:val.id,name:val.name+', '+val.address}
            )
        });

        var dt=data.data.item;
        var dj=data.data.detail_jasa;
        // $scope.formData.route_id=dt.route_id;
        $scope.formData.service_type_id=dt.service_type_id;
        $scope.formData.sender_id=dt.sender_id;
        $scope.formData.receiver_id=dt.receiver_id;
        $scope.formData.commodity_id=dt.commodity_id;
        $scope.formData.wo_customer=dt.no_po_customer;
        $scope.formData.shipment_date=$filter('minDate')(dt.shipment_date);
        $scope.formData.description=dt.description;
        $scope.formData.item_name=dj.item_name;
        $scope.formData.document_name=dt.document_name;
        $scope.formData.qty=dj.qty;
        $scope.formData.reff_no=dt.reff_no
        $scope.formData.docs_no=dt.docs_no
        $scope.formData.docs_reff_no=dt.docs_reff_no
        $scope.formData.no_bl=dt.no_bl
        $scope.formData.aju_number=dt.aju_number
        $scope.formData.vessel_name=dt.vessel_name
        $scope.formData.voyage_no=dt.voyage_no
        $scope.formData.moda_id=dt.moda_id
        $scope.formData.agent_name=dt.agent_name
        $scope.formData.awb_number=dt.awb_number
        $scope.formData.flight_code=dt.flight_code
        $scope.formData.flight_route=dt.flight_route
        $scope.formData.flight_date=$filter('minDate')(dt.flight_date)
        $scope.formData.cargo_ready_date=$filter('minDate')(dt.cargo_ready_date)
        $scope.formData.house_awb=dt.house_awb
        $scope.formData.hs_code=dt.hs_code
        $scope.formData.additional = JSON.parse(dt.additional)
        $('.ibox-content').removeClass('sk-loading');
    });

    $rootScope.disBtn=false;
    $scope.submitForm=function() {
        $rootScope.disBtn=true;
        $http.put(baseUrl+'/operational/job_order/'+$stateParams.id,$scope.formData).then(function(data) {
            if($rootScope.source != 'detail_jo') {
                $state.go('operational.job_order');
            } else {
                $state.go('operational.job_order.show', {id : $stateParams.id});

            }
            toastr.success("Data Berhasil Disimpan!");
            $rootScope.disBtn=false;
        }, function(error) {
            $rootScope.disBtn=false;
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

app.controller('operationalJobOrderShowVoyage', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
        $rootScope.pageTitle="Set Jadwal Kapal & Kontainer";
        $scope.formData={}
        $scope.formData.is_fcl=1
        $scope.div_voyage=true;
        $scope.div_container=true;
        $scope.formDataKapal={
            _token:csrfToken
        }

        $http.get(baseUrl+'/operational/job_order/set_voyage/'+$stateParams.manifest).then(function(data) {
            $scope.data=data.data;
        });

        $scope.submitFormKapal=function() {
            $.ajax({
                type: "post",
                url: baseUrl+'/setting/general/store_vessel',
                data: $scope.formDataKapal,
                success: function(data){
                    $('#modal_kapal').modal('hide');
                    toastr.success("Data Berhasil Disimpan!");
                    var vessel = {
                        id : data.id,
                        name : data.name
                    };

                    $scope.formDataKapal = {};
                    $scope.data.vessel.push(vessel);
                },
                error: function(xhr, response, status) {
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

        $scope.changeVoyage=function(jsn) {
            $scope.formData={}
            $scope.formData.voyage_schedule_id=jsn.voyage_schedule_id;
            $scope.formData.is_fcl=1
            if (jsn.voyage_schedule_id) {
                $scope.div_voyage=false;
                var dt=$rootScope.findJsonId(jsn.voyage_schedule_id,$scope.data.voyage_schedule)
                $scope.formData.vessel_id=dt.vessel_id
                $scope.formData.voyage=dt.voyage
                $scope.formData.pol_id=dt.pol_id
                $scope.formData.pod_id=dt.pod_id
                $scope.formData.etd_date=$filter('minDate')(dt.etd)
                $scope.formData.etd_time=$filter('aTime')(dt.etd)
                $scope.formData.eta_date=$filter('minDate')(dt.eta)
                $scope.formData.eta_time=$filter('aTime')(dt.eta)

                $http.get(baseUrl+'/operational/job_order/cari_container/'+dt.id).then(function(data) {
                    $scope.containers=data.data;
                });
            } else {
                $scope.containers=[]
                $scope.div_voyage=true;
            }
            $scope.changeContainer(0)
        }

        $scope.changeContainer=function(id) {
            $scope.formData.container_id=id
            if (id) {
                $scope.div_container=false;
                var dt=$rootScope.findJsonId(id,$scope.containers)
                if (dt.stripping) {
                    $scope.formData.stripping_date=$filter('minDate')(dt.stripping)
                    $scope.formData.stripping_time=$filter('aTime')(dt.stripping)
                }
                if (dt.stuffing) {
                    $scope.formData.stuffing_date=$filter('minDate')(dt.stuffing)
                    $scope.formData.stuffing_time=$filter('aTime')(dt.stuffing)
                }
                $scope.formData.booking_number=dt.booking_number
                $scope.formData.booking_date=$filter('minDate')(dt.booking_date)
                $scope.formData.container_no=dt.container_no
                $scope.formData.container_type_id=dt.container_type_id
                $scope.formData.is_fcl=dt.is_fcl
                $scope.formData.seal_no=dt.seal_no
                $scope.formData.commodity_id=dt.commodity_id
                $scope.formData.commodity=dt.commodity
            } else {
                $scope.div_container=true;
                delete $scope.formData.stripping_date
                delete $scope.formData.stripping_time
                delete $scope.formData.stuffing_date
                delete $scope.formData.stuffing_time
                delete $scope.formData.booking_number
                delete $scope.formData.booking_date
                delete $scope.formData.container_no
                delete $scope.formData.container_type_id
                $scope.formData.is_fcl=1
                delete $scope.formData.seal_no
                delete $scope.formData.commodity_id
                delete $scope.formData.commodity
            }
        }

        $rootScope.disBtn=false;
        $scope.submitForm=function() {
            $rootScope.disBtn=true;
            $http.post(baseUrl+'/operational/job_order/store_voyage_vessel/'+$stateParams.manifest,$scope.formData).then(function(data) {
                $state.go('operational.job_order.show',{id:$stateParams.id});
                toastr.success("Data Berhasil Disimpan!");
                $rootScope.disBtn=false;
            }, function(error) {
                $rootScope.disBtn=false;
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

app.controller('operationalJobOrderCreateNew',function($scope,$http,$rootScope,$state,$filter,$compile) {
    $rootScope.pageTitle="Tambah Job Order";
    $scope.customers=[]
    $scope.formData={}
    $scope.formData.is_new_wo=1
    $scope.formData.jns_tarif=1
    $scope.service_id=null
    $scope.$watch('formData.is_new_wo',function(newVal) {
        $scope.resetForm()
    })
    $scope.$watch('formData.customer_id',function(newVal) {
        $scope.resetForm()
    })
    $scope.$watch('formData.jns_tarif',function(newVal) {
        $scope.resetForm()
    })
    $http.get(`${baseUrl}/contact/contact`).then(function(e) {
        $scope.customers=e.data
    })
    $http.get(`${baseUrl}/operational/job_order/create`).then(function(e) {
        $scope.data=e.data
    })
    var wodTable = $('#wo_datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: false,
        order:[[1,'desc']],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/marketing/work_order_detail_datatable',
            data: function(d) {
                d.customer_id=$scope.formData.customer_id;
                d.is_done=0;
                d.is_operasional=1;
                d.filter_qty=1;
                d.company_id=compId;
            }
        },
        columns:[
            {
                data:null,
                className:"text-center",
                name:'id',
                orderable:false,
                searchable:false,
                sorting:false,
                render:function(e) {
                    let string = JSON.stringify({
                        name : `${e.code} - ${e.service}`
                    })
                    return `<button class="btn btn-xs btn-primary" ng-click='selectWO(${e.id_wod},${string})'>Pilih</button>`
                }
            },
            {data:"code",name:"code"},
            {data:"no_bl",name:"no_bl"},
            {data:"aju_number",name:"aju_number"},
            {data:null,name:"service",render:function(d) {
              return `${d.service} / ${d.name}`
            }},
            {data:"trayek",name:"trayek"},
            {data:"commodity",name:"commodity"},
            {
                data:null,
                orderable:false,
                searchable:false,
                render:function(resp) {
                    if(parseInt(resp.is_customer_price) == 1) {
                        return 'Tarif Customer';
                    }
                    else {
                        return resp.type_tarif_name;
                    }
                }
            },
            {data:"satuan",name:"satuan"},
            {data:"qty_leftover",name:"qty_leftover"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    $scope.cariWO=async function() {
        if (!$scope.formData.customer_id) {
            return toastr.warning(`Anda harus memilih customer dahulu!`);
        }
        await wodTable.ajax.reload()
        $('#modalWO').modal('show')
    }
    $scope.selectWO=function(id,str) {
        $http.get(`${baseUrl}/marketing/work_order/get_wo_detail_parameter/${id}`).then(function(res) {
            Object.assign($scope.formData,res.data)
            $scope.work_order_name = str.name
            $scope.service_id = res.data.service_type_id
            $('#modalWO').modal('hide')
        })
    }
    $scope.resetForm=async function() {
        const old = $scope.formData
        $scope.formData = {}
        $scope.formData.transits = old.transits
        $scope.formData.customer_id = old.customer_id
        $scope.formData.is_new_wo = old.is_new_wo
        $scope.formData.jns_tarif = old.jns_tarif
        $scope.formData.shipment_date = old.shipment_date
        $scope.formData.bl_no = old.bl_no
        $scope.formData.aju_number = old.aju_number
        $scope.work_order_name = ''
        $scope.tarif_umum_name = ''
        $scope.tarif_kontrak_name = ''
        $scope.service_id = null
    }
})

app.controller('operationalJobOrderShowSummary',function($scope,$http,$rootScope,$state,$filter,$compile) {

})

app.controller('operationalJobOrderCreateContainer',function($scope,$http,$rootScope,$state,$filter,$compile, $stateParams) {
    $scope.job_order_id = $stateParams.id
})
