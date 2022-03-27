jobOrders.directive('jobOrdersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            isPallet : "=isPallet",
            is_merchandise : '=isMerchandise',
            is_sales_contract : '=isSalesContract',
            show_sale_price : '=showSalePrice',
            index_route : '=indexRoute'
        },
        templateUrl: '/core/operational/job_orders/view/job-orders-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, $compile, jobOrdersService, salesOrdersService, additionalFieldsService, unitsService) {
            $rootScope.pageTitle=$rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.general.job_order
            $('.ibox-content').addClass('sk-loading');
            $scope.formData = {};
            $scope.formData.additional = {};
            $scope.formTransit={};
            $scope.formData.transits = []
            $scope.priceOption = 1
            $scope.formData.total_append=0;
            $scope.detailData={
                is_warehouse:0
            };
            $scope.racks = []
            $scope.warehouse_receipts = []
            $scope.data = {}
            $scope.div_main=false;
            $scope.storeUrl = jobOrdersService.url.store()

            $scope.additional_fields = []
            additionalFieldsService.dom.get('jobOrder', function(list){
                $scope.additional_fields = list
            })
    
            $('[ng-model="formSave.npwp_induk"]').inputmask("99.999.999.9-999.999")
            $('[ng-model="formSave.npwp"]').inputmask("99.999.999.9-999.999")
    
            $scope.work_orders=[
                {id:0,name:"Buat WO Baru"}
            ];
            $scope.imposition=[
                {id:1,name:'Kubikasi'},
                {id:2,name:'Tonase'},
                {id:3,name:'Item'},
            ];

            $compile($('#modalWO'))($scope)

            $scope.btnTitle = 'Save Transit'

            $scope.downloadImportData = function() {
                window.open(jobOrdersService.url.downloadImportItem())
            }

            $scope.importItemWarehouse = function() {
                var fd = new FormData()
                fd.append('file', $('#import_item_warehouse_file')[0].files[0])
                jobOrdersService.api.importItemWarehouse(fd, function(dt){
                    var x
                    for(x in dt) {
                        $scope.detailData = dt[x]
                        $scope.appendRetail()
                    }
                    $compile($('#retail_append'))($scope)
                })
            }

            $scope.showWorkOrderDetail = function() {
                  if($stateParams.work_order_detail_id) {
                      var work_order_detail_id = $stateParams.work_order_detail_id
                      $http.get(baseUrl+'/marketing/work_order/detail/' + work_order_detail_id).then(function(data) {
                          $scope.formData.customer_id = parseInt(data.data.work_order_detail.customer_id)
                          $scope.changeCustomer($scope.formData.customer_id)
                          $scope.chooseWO(data.data.work_order_detail)
                          $timeout(function(){
                              $('#modalWO').modal('hide')
                          }, 800)
                      }, function(){
                      });
                  }
            }

            $scope.initSalesOrder = function() {
                if($attrs['salesOrderMode']) {
                    $scope.disable_job_order_mode = true
                    $rootScope.pageTitle=$rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.general.sales_order
                    $scope.storeUrl = salesOrdersService.url.store()
                    $scope.formData.shipment_date = $filter('minDate')(new Date())
                }

                if($scope.disable_job_order_mode) {
                    $scope.div_main = true
                    $scope.div_document = true
                    $scope.div_table_ftl = true
                    $scope.div_detail_ftl = true
                    $scope.detailData.is_warehouse = 1

                    $scope.$on('getContract', function(e, v){
                        $scope.formData.customer_id = parseInt(v.customer_id)
                    })
                }
            }
            $scope.initSalesOrder()

            $scope.saveTransit = function() {
                if(!$scope.formTransit.code) {
                    toastr.error('Code is required')
                    return false
                }
                if(!$scope.formTransit.route_name) {
                    toastr.error('Route is required')
                    return false
                }

                if(!$scope.formTransit.id) {
                    var id = Math.round(Math.random() * 99999999)
                    $scope.formTransit.id = id
                    $scope.formData.transits.push($scope.formTransit)
                } else {
                    var idx = $scope.formData.transits.findIndex(x => x.id == $scope.formTransit.id)
                    if(idx > -1) {
                        $scope.formData.transits[idx] = $scope.formTransit
                    }
                }
                $scope.btnTitle = 'Save Transit'
                $scope.formTransit = {}
            }

            $scope.deleteTransit = function(id) {
                $scope.formData.transits = $scope.formData.transits.filter(x => x.id != id)
            }

            $scope.editTransit = function(id) {
                $scope.btnTitle = 'Update'
                $scope.formTransit = $scope.formData.transits.find(x => x.id == id) 
            }

            $scope.adjustStyle = function() {
                $timeout(function() {
                var div
                    var chosen = $('.sender .chosen-container, .receiver .chosen-container')
                    var heightMain = $('#main-form').height()
                    if($scope.formData.service_type_id < 8) {
                        $('#detail-retail').css('height', heightMain + 'px')
                        $('#detail-retail').css('margin-top', '6mm')
                        $($('#detail-retail').find('div:first-child')[0]).css('width', '100%')
                        for(var x = 0;x < chosen.length;x++) {
                            div = $('<div class="chosen-container-master"></div>')
                            div.css('width', '112mm')
                            $(chosen[x]).before(div)
                            div.append($(chosen[x]))
                        }
                    } else {
                        $('#detail-retail').css('height', 'auto')
                    }
                }, 1100)
            }
    
            var wodTable = $('#wo_datatable').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
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
                        data:"action_choose",
                        className:"text-center",
                        orderable:false,
                        searchable:false,
                        sorting:false
                    },
                    {data:"code",name:"code"},
                    {data:"no_bl",name:"no_bl"},
                    {data:"aju_number",name:"aju_number"},
                    {data:"service",name:"service"},
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
    
            var priceListColumns = [
                    {
                        data:null,
                        searchable:false,
                        orderable:false,
                        className:'text-center',
                        render: function(e) {
                            var params = {
                                'id_wo' : -1,
                                'id_wod' : -1,
                                'pq_id' : e.id,
                                'code' : e.code,
                                'price_list_id' : e.id,
                                'type_tarif' : 2,
                                'service' : e.service.name,
                                'service_id' : e.service_id
                            }

                            params = JSON.stringify(params)
                            var button = '<a ng-click=\'chooseWO(' + params + ')\' class="btn btn-xs btn-success">Pilih</a>'
                            
                            return button
                        }
                    },
                    {data:"code",name:"code"},
                    {data:"name",name:"name"},
                    {data:"service.name"},
                    {
                        data:null,
                        name:"route.name",
                        render : function(resp) {
                            if(resp['route'] != null) {
                                return resp['route']['name'];
                            }
                            else {
                                return '';
                            }
                        }
                    },
                    {data:"commodity.name",name:"commodity.name"},
                    {
                        data:null,
                        name:"piece.name",
                        render : function(resp) {
                            if(resp['piece'] != null) {
                                return resp['piece']['name'];
                            }
                            else {
                                return '';
                            }
                        }
                    }
                ]

                var priceListColumnDefs =  [
                    { "title": ""},   
                    { "title": "Price List Code"},  
                    { "title": "Price List Name"}, 
                    { "title": "Service"},  
                    { "title": "Route"},    
                    { "title": "Commodity"},    
                    { "title": "Unit"}    
                ]

                if($rootScope.settings.job_order.show_price_in_job_order == 1) {
                    priceListColumns.push({
                        data:null,
                        name:"price_full",
                        className:"text-right",
                        render: function(e) {
                            var outp = ''
                            if(e.service.service_type_id == 1) {
                                outp += $filter('number')(e.price_tonase) + ' (kg)'
                                outp += '<br>'
                                outp += $filter('number')(e.price_volume) + ' (m<sup>3</sup>)'
                                outp += '<br>'
                                outp += $filter('number')(e.price_item) + ' (Item)'
                                outp += '<br>'
                                outp += $filter('number')(e.price_borongan) + ' (Borongan)'
                            }
                            else {
                                outp = $filter('number')(e.price_full)
                            }
                            
                            return outp
                        }
                    })
                    priceListColumnDefs.push({'title' : 'Price'})
                }

                priceListColumnDefs = priceListColumnDefs.map((c, i) => {
                    c.targets = i
                    return c
                })

            price_list_datatable = $('#price_list_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX:false,
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/marketing/price_list_datatable',
                },
                columns:priceListColumns,
                columnDefs:priceListColumnDefs,
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });
    
            var quotationDetailColumns = [
                {
                    data:null,
                    searchable:false,
                    orderable:false,
                    className:'text-center',
                    render:function(e) {
                        var params = {
                            'id_wo' : -1,
                            'id_wod' : -1,
                            'pq_id' : e.id,
                            'code' : e.code,
                            'quotation_detail_id' : e.id,
                            'type_tarif' : 1,
                            'service' : e.service,
                            'service_id' : e.service_id
                        }
                        params = JSON.stringify(params)
                        var button = '<a ng-click=\'chooseWO(' + params + ')\' class="btn btn-xs btn-success">Pilih</a>'
                        
                        return button
                    }
                },
                {data:"code",name:"quotations.no_contract",className:"font-bold"},
                {data:"service",name:"services.name"},
                {data:"route_name",name:"routes.name"},
                {data:"commodity_name",name:"commodities.name"},
                {data:"vehicle_type_name",name:"vehicle_types.name",className:""},
                {data:"container_type_name",name:"container_types.name",className:""},
            ]

            var quotationDetailColumnDefs =  [
                { "title": ""},   
                { "title": "Contract Code"},  
                { "title": "Service"}, 
                { "title": "Route"},    
                { "title": "Commodity"},    
                { "title": "Vehicle"},    
                { "title": "Container Type"}    
            ]

            if($rootScope.settings.job_order.show_price_in_job_order == 1) {
                quotationDetailColumns.push({
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:"text-right",
                    render: function(e) {
                        var outp = ''
                        if(e.service_type_id == 1) {
                            if(e.imposition == 1) {
                                outp += $filter('number')(e.price_contract_volume) + ' (m<sup>3</sup>)'
                            }
                            else if(e.imposition == 2) {
                                outp += $filter('number')(e.price_contract_tonase) + ' (kg)'
                            }
                            else if(e.imposition == 3) {
                                outp += $filter('number')(e.price_contract_item) + ' (Item)'
                            }
                            else {
                                outp += $filter('number')(e.price_contract_full) + ' (Borongan)'
                            }
                        }
                        else {
                            outp = $filter('number')(e.price_contract_full)
                        }
                        
                        return outp
                    }
                })
                quotationDetailColumnDefs.push({'title' : 'Price'})
            }

            quotationDetailColumnDefs = quotationDetailColumnDefs.map((c, i) => {
                c.targets = i
                return c
            })

            quotation_detail_datatable = $('#quotation_detail_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX:false,
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/marketing/quotation_detail_datatable',
                    data: function(d) {
                        d.customer_id=$scope.formData.customer_id;
                        d.is_contact = 1
                        d.is_actived_contract = 1
                        d.no_service_4=1;
                    }
                },
                columns:quotationDetailColumns,
                columnDefs:quotationDetailColumnDefs,
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });


            $scope.$on('chooseContract', function(e, v){
                $scope.quotation_items = v.quotation_item
            })
    
            $scope.chooseItem=function(json) {
                $scope.detailData.item_id=json.id

                if($scope.quotation_items){
                    var item_quot = $scope.quotation_items.find(x => x.item_id == json.id)
                }

                $scope.detailData.harga_jual=json.harga_jual
                if(item_quot){
                    $scope.detailData.harga_jual = item_quot.price
                }

                $scope.detailData.imposition=parseInt(json.imposition)
                $scope.detailData.item_name=json.name+' ('+json.code+')'
                $scope.detailData.item_code=json.code

                $scope.detailData.warehouse_receipt_id=json.warehouse_receipt_id
                $scope.detailData.rack_id=json.rack_id
                $scope.detailData.warehouse_id=json.warehouse_id

                $scope.detailData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
                $scope.detailData.item_warehouse = json;
                $scope.detailData.stock = parseInt(json.qty)
                $scope.detailData.long=json.long
                $scope.detailData.wide=json.wide
                $scope.detailData.high=json.height
                $scope.detailData.volume = parseInt(json.long) * parseInt(json.wide) * parseInt(json.height) / 1000000;
                $scope.detailData.weight=json.weight
                $scope.allowAppend()
                $scope.adjustSizeTotal()
            }

            $scope.$on('getItemWarehouse', function(e, v){
                $scope.chooseItem(v)
            })

            $scope.appendBtn = true
            $scope.adjustSizeTotal = function() {
                $scope.detailData.total_tonase = ($scope.detailData.weight || 0) * ($scope.detailData.total_item || 0)
                $scope.detailData.total_volume = ($scope.detailData.long || 0) * ($scope.detailData.high || 0) * ($scope.detailData.wide || 0) * ($scope.detailData.total_item || 0) / 1000000
                $scope.detailData.volumetric_weight = ($scope.detailData.long || 0) * ($scope.detailData.high || 0) * ($scope.detailData.wide || 0) * ($scope.detailData.total_item || 0) / 6000
                $scope.allowAppend()
            }
            
            $scope.allowAppend = function() {
                if(parseInt($scope.detailData.total_item) > parseInt($scope.detailData.stock) || ($scope.detailData.is_warehouse == 1 && !$scope.detailData.item_id)) {
                    $scope.appendBtn = true
                } else {
                    $scope.appendBtn = false
                }
            }
    
            $scope.showService = function() {
                $http.get(baseUrl+'/setting/general/service').then(function(data) {
                    $scope.data.services=data.data
                });
            }
            $scope.showService()
            
            $scope.showModa = function() {
                $http.get(baseUrl+'/setting/general/moda').then(function(data) {
                    $scope.data.moda=data.data
                });
            }
            $scope.showModa()
            
            $scope.showCommodity = function() {
                $http.get(baseUrl+'/setting/general/commodity').then(function(data) {
                    $scope.data.commodity=data.data
                });
            }
            $scope.showCommodity()
            
            $scope.showContainerType = function() {
                $http.get(baseUrl+'/setting/general/container').then(function(data) {
                    $scope.data.container_type=data.data
                });
            }
            $scope.showContainerType()
            
            $scope.getData = function() {
                $http.get(baseUrl+'/operational/job_order/create').then(function(data) {
                    $scope.data = Object.assign($scope.data, data.data);
                    $scope.showWarehouse()
                    $scope.showRack()
                    $scope.showWarehouseReceipt()
                    $scope.showWorkOrderDetail()
                }, function(){
                    $scope.getData()
                });
            }
            $scope.getData()
            
            $scope.showRack = function() {
                $http.get(baseUrl+'/operational_warehouse/receipt/rack').then(function(data) {
                    $scope.data.rack=data.data;
                }, function(){
                    $scope.showRack()
                });
            }
            
            $scope.showCustomer = function() {
                $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
                    $scope.customers=data.data;
                }, function(){
                    $scope.showCustomer()
                });
            }
            $scope.showCustomer()
            $scope.showWarehouse = function() {
                $http.get(baseUrl+'/operational_warehouse/receipt/warehouse').then(function(data) {
                    $scope.data.warehouse=data.data;
                }, function(){
                    $scope.showWarehouse()
                });
            }
            
            $scope.showWarehouseReceipt = function() {
                $http.get(baseUrl+'/operational_warehouse/receipt').then(function(data) {
                    $scope.data.warehouse_receipt=data.data;
                }, function(){
                    $scope.showWarehouseReceipt()
                });
            }
            
            $scope.changeWarehouseReceipt = function() {
                var warehouse_receipts = $scope.data.warehouse_receipt.filter(x => x.warehouse_id == $scope.detailData.warehouse_id && x.customer_id == $scope.formData.customer_id)
                $scope.warehouse_receipts = warehouse_receipts
            }
            
            $scope.changeRack = function() {
                $scope.changeWarehouseReceipt()
                var racks = $scope.data.rack.filter(x => x.warehouse_id == $scope.detailData.warehouse_id)
                $scope.racks = racks
            }
            
            $scope.create = function() {
                $http.get(baseUrl+'/contact/contact/create').then(function(data) {
                    $scope.data2=data.data;
                    $('.ibox-content').removeClass('sk-loading');
                }, function(){
                    $scope.create()
                });
            }
            $scope.create()
    
            $scope.submitSave=function() {
                $rootScope.disBtn=true;
                $scope.formSave.company_id = compId
                $scope.formSave.id = $scope.formData.customer_id
                $.ajax({
                    type: "post",
                    url: baseUrl+'/contact/contact?_token='+csrfToken,
                    data: $scope.formSave,
                    success: function(data){
                        $scope.$apply(function() {
                            $rootScope.disBtn=false;
                        });
                        $('#modalContact').modal('hide');
                        toastr.success("Data Berhasil Disimpan");
                        $scope.contact_address=[]
                        $http.get(baseUrl+'/operational/job_order/cari_address/'+$scope.formData.customer_id).then(function(data) {
                            angular.forEach(data.data.address,function(val,i) {
                                $scope.contact_address.push(
                                    {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                                    )
                                });
                            });
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
        
                $scope.addContact=function() {
                    var customer=$rootScope.findJsonId($scope.formData.customer_id,$scope.data.customer);
                    $scope.formSave={}
                    $scope.formSave.company_id=customer.company_id
                    $scope.formSave.job_order_customer_id=customer.id
                    $scope.formSave.is_pegawai=0
                    $scope.formSave.is_investor=0
                    $scope.formSave.is_pelanggan=0
                    $scope.formSave.is_asuransi=0
                    $scope.formSave.is_supplier=0
                    $scope.formSave.is_depo_bongkar=0
                    $scope.formSave.is_helper=0
                    $scope.formSave.is_driver=0
                    $scope.formSave.is_vendor=0
                    $scope.formSave.is_sales=0
                    $scope.formSave.is_kurir=0
                    $scope.formSave.is_pengirim=1
                    $scope.formSave.is_penerima=1
                    $scope.formSave.is_pkp=0
                    $('#modalContact').modal('show');
                }
        
                $scope.cariWO=function() {
                    if (!$scope.formData.customer_id) {
                        return toastr.error("Anda Harus Memilih Customer");
                    }
                    wodTable.ajax.reload(function() {
                        $('#modalWO').modal('show');
                    });
                    quotation_detail_datatable.ajax.reload()
                }
        
                $scope.chooseWO = function(jsn) {
                    $scope.formData.type_tarif=jsn.type_tarif;
                    $scope.formData.work_order_id=jsn.id_wo;
                    $scope.changeType(jsn.type_tarif,jsn.code+' - '+jsn.service,jsn.id_wo,jsn.id_wod);
                    if (jsn.type_tarif==1) {
                        $scope.formData.quotation_detail_id=jsn.pq_id;
                        $scope.changeTypeKontrak(jsn.pq_id);
                    } else {
                        $scope.formData.price_list_id=jsn.pq_id;
                        $scope.changeServiceDiv($rootScope.findJsonId(jsn.service_id,$scope.data.services).service_type_id,jsn.service_id)
                        $scope.cari_price_list(jsn.pq_id);
                    }
                    $scope.adjustStyle()
                    $timeout(function() {
                        $scope.retail_append_thead = $('#retail_append thead th').length
                    }, 400)
                    $('#modalWO').modal('hide');
                }
        
                $scope.cari_price_list=function(id) {
                    $http.get(baseUrl+'/operational/job_order/cari_price_list/'+id).then(function(data) {
                        var ikon=data.data;
                        $scope.formData.route_id=ikon.route_id;
                        $scope.formData.commodity_id=ikon.commodity_id;
                        $scope.formData.moda_id=ikon.moda_id;
                        $scope.formData.vehicle_type_id=ikon.vehicle_type_id;
                        $scope.formData.total_unit=1;
                        $scope.detailData.imposition=1;
                        $scope.formData.container_type_id=ikon.container_type_id;
                        $scope.formData.wo_customer='';
                        if(ikon.piece_id) {
                            $scope.detailData.piece_id = ikon.piece_id
                        }
                        $scope.formData.piece_id=ikon.piece_id;
                        
                        if($scope.formData.container_type_id != null) {
                            $scope.div_container=true;
                        } else {
                            $scope.div_container=false;
                        }
                        
                        if($scope.formData.vehicle_type_id != null) {
                            $scope.div_armada=true;
                        } else {
                            $scope.div_armada=false;
                        }
                    });
                }
        
        
                // trigger jika combobox jenis layanan diganti
                // jangan di inject data hanya show div saja
                $scope.changeServiceDiv=function(stype,service,fromIq) {
                    // if (stype!=1 || stype!=3) {
                    //   return toastr.error("Mohon Pilih Layanan dengan Benar!");
                    // }
                    $scope.div_main=true;
                    $scope.formData.service_id=service;
                    $scope.formData.service_type_id=stype;
                    if (!fromIq) {
                        $scope.formData.sender_id=null;
                        $scope.formData.receiver_id=null;
                        $scope.formData.moda_id=null;
                        $scope.formData.vehicle_type_id=null;
                        $scope.formData.commodity_id=null;
                        $scope.formData.container_type_id=null;
                        $scope.formData.wo_customer='-';
                        $scope.formData.shipment_date=dateNow;
                        $scope.formData.description='-';
                        $scope.formData.total_unit=1;
                    }
                    $scope.formData.shipment_date=dateNow;
                    $scope.formData.detail=[];
                    $scope.urut=0;
                    
                    $('#ftl_append tbody').html("");
                    $('#retail_append tbody').html("");
                    if (stype==1) {
                        // Pengirima Retail
                        $scope.div_sender=true;
                        $scope.div_receive=true;
                        $scope.div_moda=true;
                        
                        $scope.div_trayek=true;
                        $scope.div_commodity=true;
                        $scope.div_shipment_date=true;
                        $scope.div_unit=false;
                        
                        $scope.div_table_ftl=false;
                        $scope.div_detail_ftl=false;
                        $scope.div_detail_retail=true;
                        $scope.div_table_retail=true;
                        $scope.detailData.imposition=1
                        $scope.div_jasa=false;
                        $scope.div_document=false;
                        
                        $scope.resetDetailRetail()
                    } else if(stype==3) {
                        $scope.div_sender=true;
                        $scope.div_receive=true;
                        $scope.div_moda=false;
                        $scope.div_armada=true;
                        $scope.div_trayek=true;
                        $scope.div_commodity=true;
                        $scope.div_shipment_date=true;
                        $scope.div_unit=true;
                        $scope.div_container=false;
                        $scope.div_table_ftl=true;
                        $scope.div_detail_ftl=true;
                        $scope.div_detail_retail=false;
                        $scope.div_table_retail=false;
                        $scope.div_jasa=false;
                        $scope.div_document=false;
                        
                        $scope.resetDetailFTL()
                    } else if(stype==4) {
                        $scope.div_sender=true;
                        $scope.div_receive=true;
                        $scope.div_moda=false;
                        $scope.div_armada=true;
                        $scope.div_trayek=true;
                        $scope.div_commodity=true;
                        $scope.div_shipment_date=true;
                        $scope.div_unit=false;
                        $scope.div_container=false;
                        $scope.div_table_ftl=true;
                        $scope.div_detail_ftl=true;
                        $scope.div_detail_retail=false;
                        $scope.div_table_retail=false;
                        $scope.div_jasa=false;
                        $scope.div_document=false;
                        
                        $scope.resetDetailFTL()
                    } else if(stype==2) {
                        $scope.div_sender=true;
                        $scope.div_receive=true;
                        $scope.div_moda=false;
                        $scope.div_armada=false;
                        $scope.div_trayek=true;
                        $scope.div_commodity=true;
                        $scope.div_shipment_date=true;
                        $scope.div_unit=true;
                        $scope.div_container=true;
                        $scope.div_table_ftl=true;
                        $scope.div_detail_ftl=true;
                        $scope.div_detail_retail=false;
                        $scope.div_table_retail=false;
                        $scope.div_jasa=false;
                        $scope.div_document=false;
                        
                        $scope.resetDetailFTL()
                    } else if(stype==6) {
                        $scope.div_sender=false;
                        $scope.div_receive=true;
                        $scope.div_moda=false;
                        $scope.div_armada=false;
                        $scope.div_trayek=false;
                        $scope.div_commodity=false;
                        $scope.div_shipment_date=false;
                        $scope.div_unit=false;
                        $scope.div_container=false;
                        $scope.div_table_ftl=false;
                        $scope.div_detail_ftl=false;
                        $scope.div_detail_retail=false;
                        $scope.div_table_retail=false;
                        $scope.div_document=true;
                        $scope.div_jasa=false;
                    } else if(stype==7) {
                        $scope.div_sender=false;
                        $scope.div_receive=true;
                        $scope.div_moda=false;
                        $scope.div_armada=false;
                        $scope.div_trayek=false;
                        $scope.div_commodity=false;
                        $scope.div_shipment_date=false;
                        $scope.div_unit=false;
                        $scope.div_container=false;
                        $scope.div_table_ftl=false;
                        $scope.div_detail_ftl=false;
                        $scope.div_detail_retail=false;
                        $scope.div_table_retail=false;
                        $scope.div_document=false;
                        $scope.div_jasa=true;
                    } else if(stype==12 || stype==13 || stype==14 || stype==15) {
                        $scope.detailData.item_name = 'GENERAL CARGO'
                        $scope.div_sender=false;
                        $scope.div_receive=false;
                        $scope.div_moda=false;
                        $scope.div_armada=false;
                        $scope.div_trayek=false;
                        $scope.div_commodity=false;
                        $scope.div_shipment_date=false;
                        $scope.div_unit=false;
                        $scope.div_container=false;
                        $scope.div_table_ftl=false;
                        $scope.div_detail_ftl=false;
                        $scope.div_detail_retail=true;
                        $scope.div_table_retail=true;
                        $scope.div_document=false;
                        $scope.div_jasa=false;
                    }

                    $timeout(function() {
                        $('#import_item_warehouse_file').change(function(){
                            $scope.importItemWarehouse()
                        })
                    }, 300)
                }
        
                $scope.resetDetailRetail=function() {
                    $scope.detailData={}
                    $scope.detailData.is_warehouse=0;
                    $scope.detailData.imposition=1;
                    $scope.detailData.item_name='GENERAL CARGO';
                    $scope.detailData.total_item=1;
                    $scope.detailData.total_tonase=0;
                    $scope.detailData.total_volume=0;
                    $scope.detailData.description='-';
                }
                $scope.resetDetailFTL=function() {
                    $scope.detailData={}
                    $scope.detailData.is_warehouse=0;
                    $scope.detailData.reff_no='-';
                    $scope.detailData.manifest_no="-";
                    $scope.detailData.item_name="GENERAL CARGO";
                    $scope.detailData.total_item=1;
                    $scope.detailData.total_tonase=0;
                    $scope.detailData.total_volume=0;
                    $scope.detailData.description="-";
                }
        
        // trigger jika combobox tipe tarif digati
        $scope.changeType=function(id,code,id_wo,id_wod) {
            // console.log(id);
            if (!$scope.formData.customer_id) {
                toastr.error("Anda Belum Memilih Customer!","Maaf !");
                return null;
            }
            var cid=$scope.formData.customer_id;
            var fd = $scope.formData
            $scope.formData = {};
            $scope.formData.additional = fd.additional;
            $scope.formData.transits = []
            $scope.formData.customer_id=cid;
            $scope.formData.type_tarif=id;
            $scope.formData.work_order_name=code;
            $scope.formData.work_order_id=id_wo;
            $scope.formData.work_order_detail_id=id_wod;
            $scope.div_main=false;
            console.log($scope.formData.transits)
            if (id==1) {
                $scope.div_item_kontrak=true;
                $scope.div_type_layanan=false;
            } else {
                $scope.work_orders=[
                    {id:0,name:"Buat WO Baru"}
                ];
                $http.get(baseUrl+'/operational/job_order/cari_wo/'+$scope.formData.customer_id,{params:{type_tarif:$scope.formData.type_tarif,quotation_detail_id:$scope.formData.quotation_detail_id}}).then(function(data) {
                    angular.forEach(data.data.wo,function(val,i) {
                        $scope.work_orders.push(
                            {id:val.id,name:val.code}
                            );
                        })
                    });
                    
                    $scope.div_item_kontrak=false;
                    $scope.div_type_layanan=true;
                }
            }
            
            $scope.urut=0;
            $scope.formData.detail=[];
            $scope.appendFTL=function() {
                var html="";
                
                html+="<tr id='rows-"+$scope.urut+"'>";
                html+="<td>"+($scope.detailData.reff_no?$scope.detailData.reff_no:'-')+"</td>";
                html+="<td>"+($scope.detailData.manifest_no?$scope.detailData.manifest_no:'-')+"</td>";
                html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";
                html+="<td class='text-right'>"+($scope.detailData.total_item?$scope.detailData.total_item:0)+" "+$scope.detailData.piece_name+"</td>";
                html+="<td class='text-right'>"+($scope.detailData.total_volume?$scope.detailData.total_volume:0)+"</td>";
                html+="<td class='text-right'>"+($scope.detailData.total_tonase?$scope.detailData.total_tonase:0)+"</td>";
                html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
                html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
                html+="</tr>";
                
                $scope.formData.detail.push(
                    {
                        reff_no:($scope.detailData.reff_no?$scope.detailData.reff_no:'-'),
                        manifest_no:($scope.detailData.manifest_no?$scope.detailData.manifest_no:'-'),
                        item_name:($scope.detailData.item_name?$scope.detailData.item_name:'-'),
                        total_item:($scope.detailData.total_item?$scope.detailData.total_item:0),
                        total_volume:($scope.detailData.total_volume?$scope.detailData.total_volume:0),
                        total_tonase:($scope.detailData.total_tonase?$scope.detailData.total_tonase:0),
                        description:($scope.detailData.description?$scope.detailData.description:'-'),
                        piece_id:($scope.detailData.piece_id?$scope.detailData.piece_id:null),
                        item_id:$scope.detailData.item_id,
                        warehouse_receipt_detail_id:$scope.detailData.warehouse_receipt_detail_id,
                        rack_id:$scope.detailData.rack_id
                    }
                )
                $('#ftl_append tbody').append($compile(html)($scope));
                $scope.hitungAppend()
                $scope.urut++;
                
                $scope.detailData.is_warehouse=0
                $scope.detailData.reff_no=null
                $scope.detailData.manifest_no=null
                $scope.detailData.item_name="GENERAL CARGO"
                $scope.detailData.total_item=0
                $scope.detailData.total_tonase=0
                $scope.detailData.total_volume=0
                $scope.detailData.total_description=null
                if($scope.disable_job_order_mode) {
                    $scope.detailData.is_warehouse=1
                }
            }
            $scope.appendRetail=function() {
                const stype = $scope.formData.service_type_id
                const imp = $scope.detailData.imposition
                if (stype==1) {
                    if (imp==1&&parseFloat($scope.detailData.total_volume)<=0) {
                        toastr.warning("Total Volume Kosong!")
                        return false;
                    } else if (imp==2&&parseFloat($scope.detailData.total_tonase)<=0) {
                        toastr.warning("Total Tonase Kosong!")
                        return false;
                    } else if (imp==3&&parseFloat($scope.detailData.total_item)<=0) {
                        toastr.warning("Jumlah Item Kosong!")
                        return false;
                    }
                }
                var html="";
                var d = $scope.detailData
                html+="<tr id='rows-"+$scope.urut+"'>";
                html+="<td>"+($scope.detailData.item_name?$scope.detailData.item_name:'-')+"</td>";
                html+="<td class='text-right'>"+($scope.detailData.total_item?$scope.detailData.total_item:0)+" "+$scope.detailData.piece_name+"</td>";
                if($scope.formData.service_type_id == 1) {
                    html+="<td class='text-right'>" + d.long + ' x ' + d.wide + ' x ' + d.high + "</td>";
                }
                html+="<td class='text-right'>"+($scope.detailData.total_volume?$scope.detailData.total_volume:0)+"</td>";
                html+="<td class='text-right'>"+($scope.detailData.total_tonase?$scope.detailData.total_tonase:0)+"</td>";
                if($scope.formData.service_type_id == 1) {
                    html+="<td class='text-right'>"+($scope.detailData.volumetric_weight?$scope.detailData.volumetric_weight:0)+"</td>";
                }
                html+="<td>"+$rootScope.findJsonId($scope.detailData.imposition,$scope.imposition).name+"</td>";
                html+="<td>"+($scope.detailData.description?$scope.detailData.description:'-')+"</td>";
                html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.urut+")'><i class='fa fa-trash'></i></a></td>"
                html+="</tr>";
                $scope.formData.detail.push(
                    {
                        item_name:($scope.detailData.item_name?$scope.detailData.item_name:'-'),
                        total_item:($scope.detailData.total_item?$scope.detailData.total_item:0),
                        total_volume:($scope.detailData.total_volume?$scope.detailData.total_volume:0),
                        total_tonase:($scope.detailData.total_tonase?$scope.detailData.total_tonase:0),
                        description:($scope.detailData.description?$scope.detailData.description:'-'),
                        piece_id:($scope.detailData.piece_id?$scope.detailData.piece_id:null),
                        imposition:$scope.detailData.imposition,
                        item_id:$scope.detailData.item_id,
                        long:$scope.detailData.long,
                        wide:$scope.detailData.wide,
                        high:$scope.detailData.high,
                        volumetric_weight:$scope.detailData.volumetric_weight,
                        warehouse_receipt_detail_id:$scope.detailData.warehouse_receipt_detail_id,
                        rack_id:$scope.detailData.rack_id
                    }
                )
                $('#retail_append tbody').append($compile(html)($scope));
                $scope.hitungAppend()
                $scope.urut++;
                
                $scope.detailData.is_warehouse=0
                $scope.detailData.reff_no=null
                $scope.detailData.manifest_no=null
                $scope.detailData.item_name="GENERAL CARGO"
                $scope.detailData.total_item=0
                $scope.detailData.total_tonase=0
                $scope.detailData.total_volume=0
                $scope.detailData.total_description=null
            }
                
            $scope.deleteAppend=function(id) {
                $('#rows-'+id).remove()

                delete $scope.formData.detail[id]
                $scope.hitungAppend()
                $compile($('#retail_append'))($scope)
            }
            
            $scope.getUnit = function() {
                unitsService.api.show($scope.detailData.piece_id, function(dt){
                    $scope.detailData.piece_name = dt.name
                })
            }

            $scope.hitungAppend=function() {
                $scope.formData.total_append=0
                if ($scope.formData.detail) {
                    angular.forEach($scope.formData.detail, function(val,i) {
                        if (!val) {
                            return;
                        }
                        $scope.formData.total_append+=1
                    })
                }
            }
                
            //trigger jika combobox customer diganti
            $scope.changeCustomer=function(id) {
                var fd = $scope.formData
                $scope.formData = {};
                $scope.formData.additional = fd.additional;
                $scope.formData.detail=[];
                $scope.formData.customer_id=id;
                $scope.contact_address=[];
                $scope.quotation_details=[];
                if(!$scope.disable_job_order_mode) {
                    $scope.changeType(1,'',null,null);
                    $scope.cariWO()
                }

                $scope.initSalesOrder()

                //cari WO dan alamat kirim - terima
                $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
                    angular.forEach(data.data.address,function(val,i) {
                        $scope.contact_address.push(
                            {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                        )
                    });
                });
            }
                    
            //trigger jika item kontrak diganti
            $scope.changeTypeKontrak=function(id) {
                $scope.work_orders=[
                    {id:0,name:"Buat WO Baru"}
                ];
                $http.get(baseUrl+'/operational/job_order/cari_wo/'+$scope.formData.customer_id,{params:{type_tarif:$scope.formData.type_tarif,quotation_detail_id:$scope.formData.quotation_detail_id}}).then(function(data) {
                    angular.forEach(data.data.wo,function(val,i) {
                        $scope.work_orders.push(
                            {id:val.id,name:val.code}
                        );
                    })
                });
                
                $http.get(baseUrl+'/operational/job_order/detail_kontrak/'+id).then(function(data) {
                    var ikon=data.data;
                    $scope.changeServiceDiv(ikon.service_type_id,ikon.service_id,true);
                    $scope.formData.route_id=ikon.route_id;
                    $scope.formData.commodity_id=ikon.commodity_id;
                    $scope.formData.moda_id=ikon.moda_id;
                    $scope.formData.vehicle_type_id=ikon.vehicle_type_id;
                    $scope.formData.total_unit=1;
                    $scope.detailData.imposition=ikon.imposition;
                    $scope.formData.container_type_id=ikon.container_type_id;
                    $scope.formData.wo_customer=ikon.header.no_inquery;
                    if(ikon.piece_id) {
                        $scope.detailData.piece_id=ikon.piece_id
                    }
                    $scope.formData.piece_id=ikon.piece_id;
                });
            }

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $state.go('operational.job_order');
                    }
                }
            }
                    
            $rootScope.disBtn=false;
            $scope.submitForm=function() {
                $scope.disBtn=true;
                $http.post($scope.storeUrl,$scope.formData).then(function(data) {
                    toastr.success("Data Berhasil Disimpan!");
                    $scope.disBtn=false;
                    $scope.back()
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
                
            $scope.initJobOrder = function() {
                job_order_datatable = $('#job_order_datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    scrollX:false,
                    initComplete : null,
                    ajax: {
                        headers : {'Authorization' : 'Bearer '+authUser.api_token},
                        url : baseUrl+'/api/operational/job_order_datatable',
                        dataSrc: function(d) {
                            $('.ibox-content').removeClass('sk-loading');
                            return d.data;
                        }
                    },
                    
                    columns:[
                        {
                            data : null,
                            sortable:false,
                            orderable:false,
                            render : function(r) {
                                var btn = '<button class="btn btn-sm btn-success" ng-disabled="disBtn" ng-click="chooseJobOrder($event.currentTarget)">Pilih</button>'
                                return btn
                            }
                        },
                        {data:"code",name:"job_orders.code",className:"font-bold"},
                        {data:"customer.name",name:"customer.name",className:"font-bold"},
                        {data:"service.name",name:"service.name"},
                    ],
                    columnDefs : [
                        {
                            targets : 0,
                            width : '5px'
                        }
                    ],
                    createdRow: function(row, data, dataIndex) {
                        $compile(angular.element(row).contents())($scope);
                    }
                });
            }
            $scope.initJobOrder()
            
            $scope.browseJobOrder = function() {
                $('#modalJobOrder').modal();
            }

            $scope.chooseJobOrder = function(e) {
                var tr = $(e).parents('tr')
                var data = job_order_datatable.row(tr).data()
                var job_order_id = data.id
                $scope.disBtn = true
                $http.get(baseUrl+'/operational/job_order/'+ job_order_id  + '/detail').then(function(data) {
                    $scope.disBtn = false
                    var details = data.data
                    var unit
                    for(d in details) {
                        unit = details[d]
                        $scope.detailData.rack_id = unit.rack_id
                        $scope.detailData.item_id = unit.item_id
                        $scope.detailData.imposition = unit.imposition
                        $scope.detailData.item_name = unit.item_name
                        $scope.detailData.warehouse_receipt_detail_id = unit.warehouse_receipt_detail_id
                        $scope.detailData.warehouse_receipt_detail_id = unit.warehouse_receipt_detail_id
                        $scope.detailData.description = unit.description
                        $scope.detailData.total_item = unit.qty
                        $scope.detailData.total_volume = unit.volume
                        $scope.detailData.total_tonase = unit.weight
                        $scope.detailData.no_reff = unit.reff_no
                        $scope.detailData.no_manifest = unit.manifest_no
                        if($scope.formData.service_type_id == 1) {
                            $scope.appendRetail()
                        } else if($scope.formData.service_type_id == 2 || $scope.formData.service_type_id == 3) {
                            $scope.appendFTL()
                        }
                    }
                    $('#modalJobOrder').modal('hide');
                });
            }
                

        }
    }
});