invoices.directive('invoicesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            type: '=type',
            hideType: '=',
            indexRoute: '='
        },
        templateUrl: '/core/operational/invoices/view/invoices-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, $compile, invoicesService, salesOrdersService, additionalFieldsService, unitsService, costTypesService ) {
            
                if ($stateParams.id != null) {
                    $rootScope.pageTitle = $rootScope.solog.label.general.edit
                } else {
                    $rootScope.pageTitle = $rootScope.solog.label.general.add
                }
                $('.ibox-content').addClass('sk-loading');
                $scope.formData = {}
                $scope.params = {}
                $scope.wo_jo = {}
                $scope.cost_types = []
                $scope.editDescription = {}
                $scope.formData.company_id = compId;
                if($scope.type){
                    $scope.formData.type = $scope.type;
                } else {
                    $scope.formData.type = 1;
                }
                $scope.formData.type_bayar = 2;
                $scope.formData.termin = 30;
                $scope.formData.is_ppn = 0;
                $scope.formData.date_invoice = dateNow;
                $scope.formData.journal_date = dateNow;
                $scope.formData.sub_total = 0;
                $scope.formData.discount_percent = 0;
                $scope.formData.discount_total = 0;
                $scope.formData.ppn_total = 0;
                $scope.formData.total_another_ppn = 0;
                $scope.formData.grand_total = 0;
                $scope.formData.sub_total_additional = 0;
                $scope.formData.discount_percent_additional = 0;
                $scope.formData.discount_total_additional = 0;
                $scope.formData.grand_total_additional = 0;
                $scope.formData.detail = [];
                $scope.data = {}

                $scope.termin = [
                    {
                        id: 1,
                        name: "Cash"
                    },
                    {
                        id: 2,
                        name: "Kredit"
                    }
                ];

                $scope.showTax = function() {
                    $http.get(baseUrl + '/setting/tax').then(function(data) {
                        $scope.data.tax = data.data
                    });
                }

                $scope.calculateTotalPrice=function() {
                    const { qty, price } = $scope.detailInv
                    $scope.detailInv.total_price = parseFloat(qty) * parseFloat(price)
                }

            $scope.showTermOfPayment = function() {
                $http.get(baseUrl + '/contact/contact/' + $scope.formData.customer_id + '/field/term_of_payment').then(function(data) {
                var termin = data.data.term_of_payment
                if (!termin) {
                    $scope.formData.termin = 30
                } else {
                    $scope.formData.termin = termin
                }
                });
            }

            $http.get(baseUrl + '/operational/invoice_jual/cari_default_akun').then(function(data) {
                var dt = data.data;
                $scope.formData.account_selling_id = dt.penjualan;
                $scope.formData.account_receivable_id = dt.piutang;
                $('.ibox-content').removeClass('sk-loading');
            });

            $scope.addTax = function() {
                $('#modalTax').modal('show');
            }
            $scope.showDefaultInvoice = function() {
                $http.get(baseUrl + '/setting/tax/default').then(function(data) {
                $scope.default_taxes = data.data;
                }, function() {
                $scope.showDefaultInvoice()
                });
            }
            $scope.showDefaultInvoice()

            $scope.showPPNInvoice = function() {
                $http.get(baseUrl + '/setting/tax/ppn').then(function(data) {
                $scope.default_ppn = data.data;
                }, function() {
                $scope.showPPNInvoice()
                });
            }
            $scope.showPPNInvoice()

            $scope.appendDefaultTaxes = function() {
                var detail = $scope.formData.detail
                for (x = 0; x < detail.length; x++) {
                for (y in $scope.default_taxes) {
                    if (!$scope.formData.detail[x].detail_tax[y].tax_id) {
                    $scope.formData.detail[x].detail_tax[y].tax_id = parseInt($scope.default_taxes[y].id)
                    $scope.hitungTaxSingle(x, y, $scope.formData.detail[x].detail_tax[y].tax_id)
                    }
                }
                }
            }

            $scope.show = function() {
                if ($stateParams.id != null) {
                $http.get(baseUrl + '/operational/invoice_jual/' + $stateParams.id).then(function(data) {
                    var detail = $scope.formData.detail
                    $scope.formData = data.data.item;

                    var date_invoice = new Date($scope.formData.date_invoice)
                    date_invoice = date_invoice.getDate() + '-' + (parseInt(date_invoice.getMonth()) + 1) + '-' + date_invoice.getFullYear()
                    $scope.formData.date_invoice = date_invoice

                    var journal_date = new Date($scope.formData.journal_date)
                    journal_date = journal_date.getDate() + '-' + (parseInt(journal_date.getMonth()) + 1) + '-' + journal_date.getFullYear()
                    $scope.formData.journal_date = journal_date

                    $scope.formData.cash_account_id = $scope.formData.account_cash_id
                    $scope.formData.detail = detail
                    if($scope.type){
                        $scope.formData.type = $scope.type;
                    } else {
                        $scope.formData.type = 1;
                    }

                    console.log('---', $scope.formData)
                }, function() {
                    $scope.show()
                });
                }
            }
            $scope.show()

            $scope.showDetail = function() {
                if ($stateParams.id != null) {
                $http.get(baseUrl + '/operational/invoice_jual/' + $stateParams.id + '/detail').then(function(data) {
                    $scope.formData.detail = []
                    var detail = data.data
                    var unit, dt1
                    for (x in detail) {
                        html = ''
                        $scope.counter = x
                        unit = detail[x]
                        if(detail[x].job_order) {
                            detail[x].job_order.service.name = detail[x].commodity_name
                        }
                        if (!unit.cost_type_id) {
                            dt1 = unit.job_order || unit
                            if (dt1.service_type_id == 2 || dt1.service_type_id == 3) {
                                $scope.formData.detail.push({
                                    job_order_id: dt1.id,
                                    job_order_detail_id: null,
                                    cost_type_id: null,
                                    work_order_id: null,
                                    price: dt1.price,
                                    total_price: dt1.price,
                                    imposition: 1,
                                    imposition_name: (dt1.service_type_id == 2 ? 'Kontainer' : 'Unit'),
                                    commodity_name: dt1.service.name,
                                    qty: 1,
                                    description: unit.description,
                                    is_other_cost: 0,
                                    type_other_cost: 0,
                                    manifest_id: unit.manifest_id,
                                    ppn: unit.ppn,
                                    discount: unit.discount,
                                    is_ppn: unit.is_ppn,
                                    detail_tax: unit.detail_tax,
                                    code: dt1.code,
                                    no_po: dt1.no_po_customer,
                                    trayek: dt1.trayek.name ?? '-',
                                    nopol: unit.nopol ?? '-',
                                    driver: unit.driver ?? '-',
                                    container_no: unit.container_no ?? '-',
                                    service: dt1.service.name ?? '-',
                                    price_table_satuan: dt1.price ?? '-',
                                    price_table_total: dt1.price ?? '-',
                                })
                                html = ''
                                html += '<tr id="rowD-' + $scope.counter + '">';
                                html += '<td>' + dt1.code + '</td>';
                                html += '<td>' + dt1.no_po_customer + '</td>';
                                html += '<td>' + (dt1.route_id ? dt1.trayek.name : '-') + '</td>';
                                html += '<td>' + (unit.nopol ? unit.nopol : '-') + '</td>';
                                html += '<td>' + (unit.driver ? unit.driver : '-') + '</td>';
                                html += '<td>' + (unit.container_no ? unit.container_no : '-') + '</td>';
                                html += '<td>' + (dt1.service.name) + '</td>';
                                html += '<td ng-click="editDescription(' + $scope.counter + ')"><% formData.detail[' + $scope.counter + '].description %></td>';
                                html += '<td>1</td>';
                                html += '<td>' + unit.imposition_name + '</td>';
                                html += '<td>' + $filter('number')(dt1.price) + '</td>';
                                html += '<td>' + $filter('number')(dt1.price) + '</td>';
                                html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                                html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a>&nbsp;<a ng-click="editDescription(' + $scope.counter + ')"><span class="fa fa-pencil"></span></a></td>';
                                html += '</tr>';
                                $scope.hitungTotal($scope.formData.detail.length-1);

                            // $('#tableDetail tbody').append($compile(html)($scope));
                            } else if (dt1.service_type_id == 1) {
                            $scope.formData.detail.push({
                                job_order_id: dt1.id,
                                job_order_detail_id: unit.job_order_detail_id,
                                cost_type_id: null,
                                work_order_id: unit.work_order_id,
                                price: unit.price,
                                total_price: unit.qty * unit.price,
                                imposition: unit.imposition,
                                imposition_name: $rootScope.findJsonId(unit.imposition, $scope.imposition).name,
                                commodity_name: dt1.service.name,
                                qty: unit.qty,
                                description: dt1.description,
                                is_other_cost: 0,
                                type_other_cost: 0,
                                manifest_id: unit.manifest_id,
                                ppn: unit.ppn,
                                discount: unit.discount,
                                is_ppn: unit.is_ppn,
                                detail_tax: unit.detail_tax,
                                code: dt1.code,
                                no_po: dt1.no_po_customer,
                                trayek: dt1.trayek ? dt1.trayek.name : '-',
                                nopol: unit ? unit.nopol : '-',
                                driver: unit ? unit.driver : '-',
                                container_no: unit ? unit.container_no : '-',
                                service: dt1.service.name,
                                price_table_satuan: unit.price,
                                price_table_total: unit.qty * unit.price,
                            })
                            html = ''
                            html += '<tr id="rowD-' + $scope.counter + '">';
                            html += '<td>' + dt1.code + '</td>';
                            html += '<td>' + dt1.no_po_customer + '</td>';
                            html += '<td>' + (dt1.route_id ? dt1.trayek.name : '-') + '</td>';
                            html += '<td>' + (unit.nopol ? unit.nopol : '-') + '</td>';
                            html += '<td>' + (unit.driver ? unit.driver : '-') + '</td>';
                            html += '<td>' + (unit.container_no ? unit.container_no : '-') + '</td>';
                            html += '<td>' + (dt1.service.name) + '</td>';
                            html += '<td ng-click="editDescription(' + $scope.counter + ')"><% formData.detail[' + $scope.counter + '].description %></td>';
                            html += '<td><input class="form-control" ng-change="hitungQty(formData.detail[' + $scope.counter + '],' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].qty"></td>';
                            html += '<td>' + $rootScope.findJsonId(unit.imposition, $scope.imposition).name + '</td>';
                            html += '<td>' + $filter('number')(unit.price) + '</td>';
                            html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';
                            html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                            html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a>&nbsp;<a ng-click="editDescription(' + $scope.counter + ')"><span class="fa fa-pencil"></span></a></td>';
                            html += '</tr>';

                            $scope.hitungTotal($scope.formData.detail.length-1);

                            // $('#tableDetail tbody').append($compile(html)($scope));
                            } else if (dt1.service_type_id == 6 || dt1.service_type_id == 7) {
                            var datas = data.data.manifest;
                            $scope.formData.detail.push({
                                job_order_id: unit.job_order_id,
                                job_order_detail_id: unit.job_order_detail_id,
                                cost_type_id: null,
                                work_order_id: null,
                                price: unit.price,
                                total_price: unit.total_price,
                                imposition: 1,
                                imposition_name: unit.imposition_name,
                                commodity_name: unit.commodity_name,
                                qty: unit.qty,
                                description: unit.description,
                                is_other_cost: 0,
                                type_other_cost: 0,
                                manifest_id: null,
                                ppn: unit.ppn,
                                discount: unit.discount,
                                is_ppn: unit.is_ppn,
                                detail_tax: unit.detail_tax,
                                code: dt1.code,
                                no_po: dt1.no_po_customer,
                                trayek: '-',
                                nopol: '-',
                                driver: '-',
                                container_no: '-',
                                service: dt1.service.name ?? '-',
                                price_table_satuan: unit.price,
                                price_table_total: unit.total_price,
                                payment_type: dt1.sales_order ? dt1.sales_order.customer_order.payment_type : null,
                            })

                            console.log('DETAIL', $scope.formData.detail)

                            html += '<tr id="rowD-' + $scope.counter + '">';
                            html += '<td>' + dt1.code + '</td>';
                            html += '<td>' + dt1.no_po_customer + '</td>';
                            html += '<td>' + '-' + '</td>';
                            html += '<td>' + '-' + '</td>';
                            html += '<td>' + '-' + '</td>';
                            html += '<td>' + '-' + '</td>';
                            html += '<td>' + (dt1.service.name) + '</td>';
                            html += '<td ng-click="editDescription(' + $scope.counter + ')"><% formData.detail[' + $scope.counter + '].description %></td>';
                            html += '<td><input class="form-control" ng-change="hitungQty(formData.detail[' + $scope.counter + '],' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].qty"></td>';
                            html += '<td>' + (unit.imposition_name) + '</td>';
                            html += '<td>' + $filter('number')(dt1.price) + '</td>';
                            html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';
                            html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                            html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a>&nbsp;<a ng-click="editDescription(' + $scope.counter + ')"><span class="fa fa-pencil"></span></a></td>';
                            html += '</tr>';

                            $scope.hitungTotal($scope.formData.detail.length-1);

                            // $('#tableDetail tbody').append($compile(html)($scope));
                            } else {
                            var datas = unit.job_order || unit;
                            var service_item = datas.service
                            var service = datas.service;
                            var job_order_detail = unit;
                            html = ''
                            if (service_item.is_packaging != 1) {
                                var imposition_name = '';
                                if (unit.imposition == 1) {
                                    imposition_name = 'Kubikasi';
                                } else if (unit.imposition == 2) {
                                    imposition_name = 'Berat';
                                } else if (unit.imposition == 3) {
                                    imposition_name = 'Item';
                                } else if (unit.imposition == 4) {
                                    imposition_name = 'Borongan';
                                }
                                $scope.formData.detail.push({
                                    job_order_id: datas.id,
                                    job_order_detail_id: unit.job_order_detail_id,
                                    cost_type_id: null,
                                    work_order_id: null,
                                    price: unit.price,
                                    total_price: unit.total_price,
                                    imposition: unit.imposition,
                                    imposition_name: imposition_name,
                                    commodity_name: datas.service.name,
                                    qty: unit.qty,
                                    description: unit.description,
                                    is_other_cost: 0,
                                    type_other_cost: 0,
                                    manifest_id: null,
                                    ppn: unit.ppn,
                                    discount: unit.discount,
                                    is_ppn: unit.is_ppn,
                                    detail_tax: unit.detail_tax,
                                    code: datas.code,
                                    no_po: '-',
                                    trayek: '-',
                                    nopol: '-',
                                    driver: '-',
                                    container_no: '-',
                                    service: datas.service.name ?? '-',
                                    price_table_satuan: unit.price,
                                    price_table_total: unit.total_price,
                                })

                                html += '<tr id="rowD-' + $scope.counter + '">';
                                html += '<td>' + datas.code + '</td>';
                                html += '<td></td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + (datas.service.name) + '</td>';
                                html += '<td ng-click="editDescription(' + $scope.counter + ')"><% formData.detail[' + $scope.counter + '].description %></td>';
                                html += '<td><input readonly class="form-control" ng-change="hitungQty(formData.detail[' + $scope.counter + '],' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].qty"></td>';
                                html += '<td>' + (imposition_name) + '</td>';
                                html += '<td>' + $filter('number')(unit.price) + '</td>';
                                html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';
                                html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                                html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a>&nbsp;<a ng-click="editDescription(' + $scope.counter + ')"><span class="fa fa-pencil"></span></a></td>';
                                html += '</tr>';

                                $scope.hitungTotal($scope.formData.detail.length-1);

                                // $('#tableDetail tbody').append($compile(html)($scope));
                            } else {
                                var imposition_name = '';
                                imposition_name = '-';
                                console.log('jod');
                                console.log(datas);

                                $scope.formData.detail.push({
                                    job_order_id: datas.id,
                                    job_order_detail_id: null,
                                    cost_type_id: null,
                                    work_order_id: null,
                                    price: datas.price,
                                    total_price: datas.total_price,
                                    imposition: '-',
                                    imposition_name: imposition_name,
                                    commodity_name: datas.service.name,
                                    qty: unit.qty,
                                    description: unit.description,
                                    is_other_cost: 0,
                                    type_other_cost: 0,
                                    manifest_id: null,
                                    ppn: unit.ppn,
                                    discount: unit.discount,
                                    is_ppn: unit.is_ppn,
                                    detail_tax: unit.detail_tax,
                                    code: datas.code,
                                    no_po: '-',
                                    trayek: '-',
                                    nopol: '-',
                                    driver: '-',
                                    container_no: '-',
                                    service: datas.service.name ?? '-',
                                    price_table_satuan: datas.price,
                                    price_table_total: datas.total_price,
                                })

                                html += '<tr id="rowD-' + $scope.counter + '">';
                                html += '<td>' + datas.code + '</td>';
                                html += '<td></td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + '-' + '</td>';
                                html += '<td>' + (datas.service.name) + '</td>';
                                html += '<td ng-click="editDescription(' + $scope.counter + ')"><% formData.detail[' + $scope.counter + '].description %></td>';
                                html += '<td>' + unit.qty + '</td>';
                                html += '<td>' + (imposition_name) + '</td>';
                                html += '<td>' + $filter('number')(datas.price) + '</td>';
                                html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';
                                html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                                html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a>&nbsp;<a ng-click="editDescription(' + $scope.counter + ')"><span class="fa fa-pencil"></span></a></td>';
                                html += '</tr>';

                                $scope.hitungTotal($scope.formData.detail.length-1);

                                // $('#tableDetail tbody').append($compile(html)($scope));

                            }



                            }
                        } else {
                            $scope.ctype = unit.cost_type
                            if (!unit.job_order_id) {
                                $scope.detailInv = unit
                                $scope.addInvoiceOther()
                            } else {
                                $http.get(baseUrl + '/operational/invoice_jual/jo_list', {
                                    params: {
                                    customer_id: $scope.formData.customer_id
                                    }
                                }).then(function(data) {
                                    $scope.jo_list = data.data
                                    $scope.rData = unit
                                    $scope.appendReimbursement()
                                }, function(error) {
                                    console.log(error)
                                })
                            }


                            $scope.formData.detail[x].discount = unit.discount
                            $scope.formData.detail[x].is_ppn = unit.is_ppn
                            $scope.formData.detail[x].ppn = unit.ppn
                            $scope.formData.detail[x].detail_tax = unit.detail_tax
                            $scope.hitungTotal(x)

                        }


                        $scope.hitungGrandTotal()
                    }

                })
                }
            }
            $scope.showDetail()


            $scope.editDescription = function(index) {
                var detail = $scope.formData.detail[index]
                $scope.formDescription = detail
                $('#modalDescription').modal()
            }

            $scope.submitFormDescription = function(index) {
                $scope.formData.detail[index] = $scope.formDescription
                $('#modalDescription').modal('hide')
            }


            $scope.changeQty = function(val, counter) {
                if (!val) {
                val = 1
                $scope.hitungTotal(counter);
                }
            }
            $scope.rData = {}
            $scope.addReimburse = function() {
                $scope.data.cost_types=[]
                $http.get(baseUrl + '/operational/invoice_jual/jo_list', {
                params: {
                    customer_id: $scope.formData.customer_id
                }
                }).then(function(data) {
                $scope.jo_list = data.data
                $('#modalReimbursement').modal()
                $scope.rData.job_order_id = null
                $scope.rData.cost_type_id = null
                $scope.rData.cost_type = null
                $scope.rData.qty = 1
                $scope.rData.price = 0
                $scope.rData.total_price = 0
                $scope.rData.description = '-'
                }, function(error) {
                console.log(error)
                })
            }

            $scope.changeCTre = function(id) {
                var jsn = $rootScope.findJsonId(id, $scope.data.cost_type);
                $scope.ctype = jsn;
                // console.log(jsn);
                $scope.rData.cost_type_id = $scope.rData.cost_type.id;
                var jsn = $scope.rData.cost_type;
                $scope.ctype = jsn;
                // console.log(jsn);
                $scope.rData.qty = jsn.qty;
                $scope.rData.price = jsn.price;
                $scope.rData.total_price = jsn.total_price;
            }

            $scope.appendReimbursement = function() {
                var html = "";
                var ctype = $scope.ctype;
                var jos = $scope.jo_list;
                html += '<tr id="rowD-' + $scope.counter + '">';
                html += '<td>' + $rootScope.findJsonId($scope.rData.job_order_id, jos).code + '</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>' + ctype.name + '</td>';
                html += '<td>' + $scope.rData.description + '</td>';
                html += '<td>' + $scope.rData.qty + '</td>';
                html += '<td>-</td>';
                html += '<td>' + $filter('number')($scope.rData.price) + '</td>';
                html += '<td>' + $filter('number')($scope.rData.total_price) + '</td>';
                html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" class="form-control text-right"></td>'
                html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                html += '</tr>';

                $scope.formData.detail.push({
                job_order_id: $scope.rData.job_order_id,
                job_order_detail_id: null,
                work_order_id: null,
                cost_type_id: ctype.id,
                price: $scope.rData.price,
                total_price: $scope.rData.total_price,
                imposition: null,
                imposition_name: '-',
                commodity_name: '-',
                qty: $scope.rData.qty,
                description: $scope.rData.description,
                is_other_cost: 1,
                type_other_cost: 1,
                manifest_id: null,
                ppn: 0,
                discount: 0,
                is_ppn: 0,
                detail_tax: [{
                    id: 1,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 2,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 3,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 4,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 5,
                    tax_id: null,
                    value: 0
                    },
                ],
                code: $rootScope.findJsonId($scope.rData.job_order_id, jos).code,
                no_po: '-',
                trayek: '-',
                nopol: '-',
                driver: '-',
                container_no: '-',
                service: `${ctype.name} (Reimburse)` ,
                price_table_satuan: $scope.rData.price,
                price_table_total: $scope.rData.total_price,
                })

                $scope.hitungTotal($scope.formData.detail.length-1);
                // $('#tableDetail tbody').append($compile(html)($scope));
                $scope.hitungSubTotalDetail()


                $('#modalReimbursement').modal('hide')
            }

            $scope.companyChange = function(id) {
                // $http.get(baseUrl+'/operational/invoice_jual/cari_customer_list/'+id).then(function(data) {
                //   $scope.customer=data.data.item;
                // });

                $scope.cost_types = []
                angular.forEach($scope.data.cost_type, function(val, i) {
                // console.log(val)
                if (id == val.company_id) {
                    $scope.cost_types.push({
                    id: val.id,
                    name: val.name,
                    parent: val.parent.name
                    })
                }
                });
            }

            $scope.get_job_order_costs = function() {
                var request = {
                job_order_id: $scope.rData.job_order_id,
                }

                $http.get(baseUrl + '/operational/invoice_jual/get_job_order_costs?' + $.param(request)).then(function(data) {
                $scope.data.cost_types = data.data.cost_type;
                });
            }

            $scope.get_work_order_costs = function() {
                $http.get(baseUrl + '/operational/invoice_jual/create').then(function(data) {
                $scope.data.cost_types = data.data.cost_type;
                });
            }

            $http.get(baseUrl + '/operational/invoice_jual/create').then(function(data) {
                $scope.data = Object.assign($scope.data, data.data)
                $scope.showTax()
                $scope.cost_type = data.data.cost_type;
                $scope.companyChange(compId);
            });

            $scope.imposition = [{
                id: 1,
                name: 'Kubikasi'
                },
                {
                id: 2,
                name: 'Tonase'
                },
                {
                id: 3,
                name: 'Item'
                },
            ];

            $scope.selectJO = function(id, code) {
                $scope.wo_jo.job_order_id = id;
                $scope.wo_jo.job_order_code = code;
                $scope.appendDetail()
                $('#modalJO').modal('hide');
            }
            $scope.selectWO = function(id, code) {
                $scope.wo_jo.work_order_id = id;
                $scope.wo_jo.work_order_code = code;
                $scope.appendDetail()
                $('#modalWO').modal('hide');
            }
            $scope.counter = 0;
            $scope.appendDetail = function() {
                var type = $scope.formData.type;
                if (type == 1) {
                var wo_jo = $scope.wo_jo.job_order_id;
                $http.get(baseUrl + '/operational/invoice_jual/cari_jo/' + wo_jo).then(function(data) {
                    if (data.data.manifest.length < 1) {
                    return toastr.warning("JO ini Belum Ada Manifest", "Maaf!");
                    }
                    var dt1 = data.data.jo;
                    var html = "";
                    if (dt1.service_type_id == 2 || dt1.service_type_id == 3) {
                    angular.forEach(data.data.manifest, function(val, i) {
                        html = ''
                        html += '<tr id="rowD-' + $scope.counter + '">';
                        html += '<td>' + dt1.code + '</td>';
                        html += '<td>' + dt1.no_po_customer + '</td>';
                        html += '<td>' + (dt1.route_id ? dt1.trayek.name : '-') + '</td>';
                        html += '<td>' + (val.nopol ? val.nopol : '-') + '</td>';
                        html += '<td>' + (val.driver ? val.driver : '-') + '</td>';
                        html += '<td>' + (val.container_no ? val.container_no : '-') + '</td>';
                        html += '<td>' + (val.service) + '</td>';
                        html += '<td ng-click="editDescription(' + $scope.counter + ')">' + (dt1.description ? dt1.description : '-') + '</td>';
                        html += '<td>1</td>';
                        html += '<td>' + data.data.imposition + '</td>';
                        html += '<td>' + $filter('number')(dt1.price) + '</td>';
                        html += '<td>' + $filter('number')(dt1.price) + '</td>';
                        html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                        html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                        html += '</tr>';

                        $scope.formData.detail.push({
                        job_order_id: dt1.id,
                        job_order_detail_id: null,
                        cost_type_id: null,
                        work_order_id: null,
                        price: dt1.price,
                        total_price: dt1.price,
                        imposition: 1,
                        imposition_name: (dt1.service_type_id == 2 ? 'Kontainer' : 'Unit'),
                        commodity_name: dt1.service,
                        qty: 1,
                        description: dt1.description,
                        is_other_cost: 0,
                        type_other_cost: 0,
                        manifest_id: val.id,
                        ppn: 0,
                        discount: 0,
                        is_ppn: 0,
                        detail_tax: [{
                            id: 1,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 2,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 3,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 4,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 5,
                            tax_id: null,
                            value: 0
                            },
                        ],
                        code: dt1.code,
                        no_po: dt1.no_po_customer,
                        trayek: dt1.trayek.name ?? '-',
                        nopol: val.nopol ?? '-',
                        driver: val.driver ?? '-',
                        container_no: val.container_no ?? '-',
                        service: val.service ?? '-',
                        price_table_satuan: dt1.price,
                        price_table_total: dt1.price,
                        })
                        $scope.hitungTotal($scope.formData.detail.length-1);

                        // $('#tableDetail tbody').append($compile(html)($scope));

                    });
                    } else if (dt1.service_type_id == 1) {
                    angular.forEach(data.data.manifest, function(val, i) {
                        $scope.formData.detail.push({
                        job_order_id: dt1.id,
                        job_order_detail_id: val.job_order_detail_id,
                        cost_type_id: null,
                        work_order_id: null,
                        price: val.price,
                        total_price: val.qty * val.price,
                        imposition: val.imposition,
                        imposition_name: $rootScope.findJsonId(val.imposition, $scope.imposition).name,
                        commodity_name: val.service,
                        qty: val.qty,
                        description: dt1.description,
                        is_other_cost: 0,
                        type_other_cost: 0,
                        manifest_id: val.manifest_id,
                        ppn: 0,
                        discount: 0,
                        is_ppn: 0,
                        detail_tax: [{
                            id: 1,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 2,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 3,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 4,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 5,
                            tax_id: null,
                            value: 0
                            },
                        ],
                        code: dt1.code,
                        no_po: dt1.no_po_customer,
                        trayek: dt1.trayek.name ?? '-',
                        nopol: val.nopol ?? '-',
                        driver: val.driver ?? '-',
                        container_no: val.container_no ?? '-',
                        service: val.service ?? '-',
                        price_table_satuan: val.price,
                        price_table_total: val.qty * val.price,
                        })
                        
                        $scope.hitungTotal($scope.formData.detail.length-1);
                    });
                    } else if (dt1.service_type_id == 6 || dt1.service_type_id == 7) {
                    var datas = data.data.manifest;
                    $scope.formData.detail.push({
                        job_order_id: datas.id,
                        job_order_detail_id: datas.job_order_detail_id,
                        cost_type_id: null,
                        work_order_id: null,
                        price: datas.price,
                        total_price: datas.total_price,
                        imposition: 1,
                        imposition_name: datas.piece_name,
                        commodity_name: datas.service,
                        qty: datas.qty,
                        description: datas.description,
                        is_other_cost: 0,
                        type_other_cost: 0,
                        manifest_id: null,
                        ppn: 0,
                        discount: 0,
                        is_ppn: 0,
                        detail_tax: [{
                            id: 1,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 2,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 3,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 4,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 5,
                            tax_id: null,
                            value: 0
                        },
                        ],
                        code: datas.code,
                        no_po: datas.no_po_customer,
                        trayek: '-',
                        nopol: '-',
                        driver: '-',
                        container_no: '-',
                        service: datas.service ?? '-',
                        price_table_satuan: datas.price,
                        price_table_total: datas.total_price,
                    })

                    html += '<tr id="rowD-' + $scope.counter + '">';
                    html += '<td>' + datas.code + '</td>';
                    html += '<td>' + datas.no_po_customer + '</td>';
                    html += '<td>' + '-' + '</td>';
                    html += '<td>' + '-' + '</td>';
                    html += '<td>' + '-' + '</td>';
                    html += '<td>' + '-' + '</td>';
                    html += '<td>' + (datas.service) + '</td>';
                    html += '<td ng-click="editDescription(' + $scope.counter + ')">' + (datas.description ? datas.description : '-') + '</td>';
                    html += '<td><input class="form-control" ng-change="hitungQty(formData.detail[' + $scope.counter + '],' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].qty"></td>';
                    html += '<td>' + (datas.piece_name) + '</td>';
                    html += '<td>' + $filter('number')(datas.price) + '</td>';
                    html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';
                    html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                    html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                    html += '</tr>';

                    $scope.hitungTotal($scope.formData.detail.length-1);

                    // $('#tableDetail tbody').append($compile(html)($scope));
                    } else if(dt1.service_type_id == 12 || dt1.service_type_id == 13 || dt1.service_type_id == 15) {
                        angular.forEach(data.data.manifest,function(val,i) {
                        var params = {
                            code : dt1.code,
                            service : val.service,
                            job_order_id: dt1.id,
                            job_order_detail_id: val.job_order_detail_id,
                            cost_type_id: null,
                            work_order_id: null,
                            price : val.price,
                            total_price: parseInt(val.total_price),
                            price_table_satuan : val.price,
                            price_table_total: val.total_price,
                            imposition: val.imposition,
                            imposition_name: $rootScope.findJsonId(val.imposition,$scope.imposition).name,
                            commodity_name: val.service,
                            qty: val.qty,
                            description: dt1.description,
                            is_other_cost: 0,
                            type_other_cost: 0,
                            manifest_id : val.manifest_id,
                            ppn:0,
                            discount:0,
                            is_ppn:$scope.pkp,
                            detail_tax:[
                            {id:1,tax_id:null,value:0},
                            {id:2,tax_id:null,value:0},
                            {id:3,tax_id:null,value:0},
                            {id:4,tax_id:null,value:0},
                            {id:5,tax_id:null,value:0},
                            ]
                        }
                        $scope.formData.detail.push(params)
                        $scope.hitungTotal($scope.formData.detail.length-1);
                        html = ''
                        $scope.counter++;

                        });
                    }
                    $scope.appendDefaultTaxes()
                    $scope.hitungGrandTotal()
                }, function error(data) {
                    toastr.error(data.data.message, "Error!");
                });
                } else {
                var wo_jo = $scope.wo_jo.work_order_id;
                $http.get(baseUrl + '/operational/invoice_jual/cari_wo/' + wo_jo, {
                    params: {
                    jo_list_append: $scope.jo_list_append()
                    }
                }).then(function(data) {
                    if (data.data.length < 1) {
                    return toastr.warning("tidak tersedia Job Order dalam WO ini / Job Order sudah Invoice", "Maaf!");
                    }
                    angular.forEach(data.data, function(val, i) {
                    $scope.formData.detail.push({
                        job_order_id: val.job_order_id,
                        job_order_detail_id: val.job_order_detail_id,
                        cost_type_id: null,
                        work_order_id: val.work_order_id,
                        price: val.price,
                        total_price: val.total_price,
                        imposition: val.imposition,
                        imposition_name: val.imposition_name,
                        commodity_name: val.commodity,
                        qty: val.qty,
                        description: val.description,
                        is_other_cost: 0,
                        type_other_cost: 0,
                        manifest_id: val.manifest_id,
                        ppn: 0,
                        discount: 0,
                        is_ppn: 0,
                        detail_tax: [{
                            id: 1,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 2,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 3,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 4,
                            tax_id: null,
                            value: 0
                        },
                        {
                            id: 5,
                            tax_id: null,
                            value: 0
                        },
                        ],
                        code: val.code,
                        no_po: val.no_po_customer ?? '-',
                        trayek: val.trayek ?? '-',
                        nopol: val.nopol ?? '-',
                        driver: val.driver ?? '-',
                        container_no: val.container_no ?? '-',
                        service: val.commodity,
                        price_table_satuan: val.price,
                        price_table_total: val.total_price,
                    })

                    var html = "";
                    html += '<tr id="rowD-' + $scope.counter + '">';
                    html += '<td>' + val.code + '</td>';
                    html += '<td>' + val.no_po_customer ?? `-` + '</td>';
                    html += '<td>' + val.trayek + '</td>';
                    html += '<td>' + val.nopol + '</td>';
                    html += '<td>' + val.driver + '</td>';
                    html += '<td>' + val.container_no + '</td>';
                    html += '<td>' + val.commodity + '</td>';
                    html += '<td ng-click="editDescription(' + $scope.counter + ')">' + val.description + '</td>';
                    html += `<td>${$filter('number')(val.qty)}</td>`
                    html += '<td>' + val.imposition_name + '</td>';
                    html += '<td>' + $filter('number')(val.price) + '</td>';
                    html += '<td><span ng-bind="formData.detail[' + $scope.counter + '].total_price|number"></span></td>';

                    html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" jnumber2 only-num class="form-control text-right"></td>'
                    html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                    html += '</tr>';

                    $scope.hitungTotal($scope.formData.detail.length-1);

                    // $('#tableDetail tbody').append($compile(html)($scope));
                    $scope.hitungGrandTotal()

                    })

                    $scope.appendDefaultTaxes()
                });
                }
                $scope.wo_jo = {}
            }

            $scope.hitungQty = function(dt, i) {
                if (dt.imposition != 4) {
                dt.total_price = Math.round(dt.qty * dt.price)
                } else {
                dt.total_price = dt.price
                }
                if (dt.is_ppn) {
                dt.ppn = dt.total_price * 10 / 100
                } else {
                dt.ppn = 0
                }
                angular.forEach($scope.formData.detail[i].detail_tax, function(val, x) {
                $scope.hitungTaxSingle(i, x, val.tax_id);
                })
            }

            $scope.hitungTotal = function(row) {
                let detail = $scope.formData.detail[row];
                var totMinDisc = parseFloat(detail.price_table_total) - parseFloat(detail.discount);
                var tot2minPpn = totMinDisc + parseFloat(detail.ppn)
                var totalTaxDisc = -parseFloat(detail.discount);

                var total_price = detail.price_table_total
                for (var i = 0; i < detail.detail_tax.length; i++) {
                let val = detail.detail_tax[i]
                if(!val.tax_id) continue;
                tot2minPpn += parseFloat(val.value);
                totalTaxDisc += parseFloat(val.value);
                if($scope.data.tax) {
                    let taxDetail = $scope.data.tax.find(e => e.id==val.tax_id)
                    if (taxDetail.pemotong_pemungut==1) {
                            total_price-=parseFloat(val.value)
                    }
                }
                }
                Object.assign($scope.formData.detail[row],{
                total_price: total_price
                })
                detail.total_with_discount = totMinDisc;
                detail.total_discount_tax = totalTaxDisc;
                $scope.hitungGrandTotal()
            }

            $scope.hitungGrandTotal = function() {
                $scope.formData.sub_total = 0;
                $scope.formData.discount_total = 0;
                $scope.formData.ppn_total = 0;
                $scope.formData.total_another_ppn = 0;
                $scope.total_ppn_default = 0
                for (var i = 0; i < $scope.formData.detail.length; i++) {
                    let val = $scope.formData.detail[i]
                    // console.log(val)
                    $scope.formData.sub_total+=parseInt(val.total_price)
                    $scope.formData.discount_total+=parseInt(val.discount)
                    for (var ii = 0; ii < val.detail_tax.length; ii++) {
                        let tax = val.detail_tax[ii]
                        if(!tax.tax_id)continue;
                        $scope.formData.total_another_ppn+=parseInt(tax.value)
                    }
                }
                var gt = $scope.formData.sub_total - $scope.formData.discount_total + $scope.formData.total_another_ppn;
                $scope.formData.grand_total = Math.round(gt)
                $scope.formData.grand_total -= $scope.total_ppn_default
            }


                $scope.fillAdditional = function() {
                    var detail = $scope.formData.detail
                    detail = detail.map(function(v){
                        if(!v.discount) {
                            v.discount = 0
                        }
                        if(!v.ppn) {
                            v.ppn = 0
                        }

                        v.price_table_total = v.total_price
                        v.commodity_name = v.service
                        return v
                    })

                    $scope.formData.detail = detail
                }

                $scope.fillTax = function() {
                    var detail = $scope.formData.detail
                    detail = detail.map(function(v){
                        if(!v.detail_tax) {
                            v.detail_tax =  [{
                            id: 1,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 2,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 3,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 4,
                            tax_id: null,
                            value: 0
                            },
                            {
                            id: 5,
                            tax_id: null,
                            value: 0
                            },
                        ]
                        }
                        return v
                    })

                    $scope.formData.detail = detail
                }

                $scope.$on('getSalesOrder', function(e, v) {
                    salesOrdersService.api.show(v.id, function(data){
                        salesOrdersService.api.showDetail(v.id, function(dt){
                            var items = dt
                            items = items.map(function(val){
                                var res = {}
                                res.service = val.item_name
                                res.code = data.code
                                res.qty = val.qty
                                res.description = val.description
                                res.job_order_id = data.job_order_id
                                res.price = val.price
                                res.total_price = val.total_price
                                res.job_order_detail_id = val.job_order_detail_id
                                res.sales_order_status_id = data.status_id
                                res.payment_type = data.payment_type
                                
                                return res
                            })
                            console.log('---',items)
                            items.forEach(function(val){
                                var exist = $scope.formData.detail.findIndex(x => x.service == val.service && x.job_order_id == val.job_order_id )
                                if(exist == -1) {
                                    $scope.formData.detail.push(val)
                                }
                            })
                            $scope.fillTax()
                            $scope.fillAdditional()
                            $scope.hitungGrandTotal()
                        })
                    })
                })

            $scope.hitungTaxSingle = function(row, i, id) {
                let detail = $scope.formData.detail[row]
                if (!id) {
                detail.detail_tax[i].value = 0;
                return $scope.hitungTotal(row);
                }
                let jsn = $rootScope.findJsonId(id, $scope.data.tax);
                if (jsn.pemotong_pemungut == 1) {
                var tst = (detail.price_table_total-detail.discount) * jsn.npwp / 100;
                } else {
                var tst = detail.total_with_discount * jsn.npwp / 100;
                }
                $scope.formData.detail[row].detail_tax[i].value = tst;
                $scope.hitungTotal(row);
            }

            $scope.taxCheck = function(row, value) {
                var kenaPajak = parseFloat($scope.formData.detail[row].total_price) - parseFloat($scope.formData.detail[row].discount);
                var outp;
                if (value) {
                var outp = kenaPajak * 0.10;
                outp = parseFloat(outp).toFixed(2);
                $scope.formData.detail[row].ppn = outp;
                } else {
                $scope.formData.detail[row].ppn = 0
                }
                $scope.formData.detail[row].is_ppn = value;
                $scope.hitungTotal(row);
            }

            $scope.ppnSet = function(row) {
                $scope.hitungTotal(row)
                var base = "";
                base += '<div class="form-group">'
                base += '<label class="col-md-4">Diskon</label>'
                base += '<div class="col-md-7">'
                base += '<input type="text" ng-change="hitungTotal(' + row + ')" class="form-control" jnumber2 only-num ng-model="formData.detail[' + row + '].discount">'
                base += '</div>'
                base += '</div>'
                // base+='<div class="form-group">'
                // base+='<label class="col-md-4">PPN 10%</label>'
                // base+='<div class="col-md-7">'
                // base+='<div class="input-group">'
                // base+='<span class="input-group-addon">'
                // base+='<input type="checkbox" ng-change="taxCheck('+row+',formData.detail['+row+'].is_ppn)" ng-model="formData.detail['+row+'].is_ppn" ng-true-value="1" ng-false-value="0">'
                // base+='</span>'
                // base+='<input type="text" readonly class="form-control" jnumber2 only-num ng-model="formData.detail['+row+'].ppn">'
                // base+='</div>'
                // base+='</div>'
                // base+='</div>'

                base += '<table class="table table-borderless">'
                base += '<tbody>'
                angular.forEach($scope.formData.detail[row].detail_tax, function(val, i) {
                base += '<tr>'
                base += '<td>'
                base += '<select class="form-control" ng-change="hitungTaxSingle(' + row + ',' + i + ',formData.detail[' + row + '].detail_tax[' + i + '].tax_id)" data-placeholder-text-single="\'-- Pilih Pajak --\'" chosen allow-single-deselect="true" ng-model="formData.detail[' + row + '].detail_tax[' + i + '].tax_id" ng-options="s.id as s.name for s in data.tax">'
                base += '<option value=""></option>'
                base += '</select>'
                base += '</td>'
                base += '<td>'
                base += '<input type="text" class="form-control" jnumber2 only-num readonly ng-model="formData.detail[' + row + '].detail_tax[' + i + '].value">'
                base += '</td>'
                base += '</tr>'
                });
                base += '</tbody>'
                base += '</table>'
                base += '<div class="form-group">'
                base += '<label class="col-md-4">Total Pajak & Diskon</label>'
                base += '<div class="col-md-7">'
                base += '<input type="text" readonly class="form-control" jnumber2 only-num ng-model="formData.detail[' + row + '].total_discount_tax">'
                base += '</div>'

                $('#detailHtml').html($compile(base)($scope))
                $compile( $('#detailHtml'))($scope)
                $('#modalTax').modal('show');
            }

            $scope.taxInput = [{
                id: 1
                },
                {
                id: 2
                },
                {
                id: 3
                },
                {
                id: 4
                },
                {
                id: 5
                },
            ]
            $scope.taxData = []
            angular.forEach($scope.taxInput, function(val, i) {
                $scope.taxData.push({
                tax: null,
                value: 0
                })
            });
            $scope.hitungTax = function(i, id) {
                $scope.formData.total_another_ppn = 0;
                var jsn = $rootScope.findJsonId(id, $scope.data.tax);
                if (jsn.pemotong_pemungut == 1) {
                var tst = -(($scope.formData.sub_total - parseFloat($scope.formData.discount_total)) * jsn.npwp / 100);
                } else {
                var tst = ($scope.formData.sub_total - parseFloat($scope.formData.discount_total)) * jsn.npwp / 100;
                }
                // console.log(tst);
                $scope.taxData[i].value = (id ? tst : 0);
                angular.forEach($scope.taxData, function(val, i) {
                $scope.formData.total_another_ppn += parseFloat(val.value);
                })
                $scope.hitungSubTotalDetail()
            }

            $scope.deleteDetail = function(id) {
                // $('#rowD-' + id).remove();
                // delete $scope.formData.detail[id];
                $scope.formData.detail.splice(id, 1)
                $scope.hitungGrandTotal()
            }
            $scope.deleteK = function(id) {
                $('#rowK-' + id).remove();
                delete $scope.formData.detail[id];
                $scope.hitungSubTotalAdditional()
            }

            $scope.resetDetail = function() {
                $scope.formData.detail = [];
                $('#tableDetail tbody').html('');
                $scope.counter = 0;
                $scope.hitungSubTotalDetail()
                $scope.showIncludedTaxes()
            }

            $scope.hitungSubTotalDetail = function() {
                $scope.formData.sub_total = 0;
                angular.forEach($scope.formData.detail, function(val, i) {
                if (val) {
                    if (val.type_other_cost != 2) {
                    $scope.formData.sub_total += parseFloat(val.total_price);
                    }
                }
                })
                // $scope.formData.total_another_ppn=$scope.formData.sub_total-parseFloat($scope.formData.discount_total)+$scope.formData.ppn_total;
                var gt = $scope.formData.sub_total - parseFloat($scope.formData.discount_total) + parseFloat($scope.formData.ppn_total) + parseFloat($scope.formData.total_another_ppn);
                $scope.formData.grand_total = Math.round(gt)
            }
            $scope.hitungSubTotalAdditional = function() {
                $scope.formData.sub_total_additional = 0;
                angular.forEach($scope.formData.detail, function(val, i) {
                if (val) {
                    if (val.type_other_cost == 2) {
                    $scope.formData.sub_total_additional += parseFloat(val.total_price);
                    }
                }
                })
                // $scope.formData.total_another_ppn=$scope.formData.sub_total-parseFloat($scope.formData.discount_total)+$scope.formData.ppn_total;
                $scope.formData.grand_total_additional = $scope.formData.sub_total_additional - parseFloat($scope.formData.discount_total_additional);
            }

            $scope.changeDetailPpn = function(value) {
                if (value) {
                var disc = 10 / 100 * ($scope.formData.sub_total - parseFloat($scope.formData.discount_total));
                $scope.formData.ppn_total = Math.round(disc)
                } else {
                $scope.formData.ppn_total = 0;
                }
                $scope.hitungSubTotalDetail()
            }

            $scope.detailInv = {}
            $scope.ctype = {}
            $scope.addDetail = function() {
                $scope.is_using_ppn = true;
                $scope.detailInv = {}
                $scope.detailInv.description = ""
                $scope.ctype = {}
                $scope.costList = []
                $http.post(baseUrl + '/operational/invoice_jual/cari_jo_cost', $scope.formData.detail).then(function(data) {
                angular.forEach(data.data, function(val, i) {
                    $scope.costList.push(val)
                })
                angular.forEach($scope.cost_type, function(val, i) {
                    $scope.costList.push(val)
                })
                $('#invoiceAddModal').modal('show');
                })
                if ($scope.costList.length > 0) {
                $scope.detailInv.cost_type_id = $scope.costList[0].id
                $scope.changeCT($scope.costList[0].id)
                }
            }
            $scope.addAdditional = function() {
                $scope.is_using_ppn = false;
                $scope.detailInv = {}
                $scope.ctype = {}
                $('#invoiceAddModal').modal('show');
            }

            $scope.changeCT = function(id) {
                var jsn = $rootScope.findJsonId(id, $scope.costList);
                $scope.ctype = jsn;
                // console.log(jsn);
                $scope.detailInv.qty = jsn.qty;
                $scope.detailInv.price = jsn.price;
                $scope.detailInv.total_price = jsn.total_price;
            }

            $scope.jo_list_append = function() {
                var jo = "";
                angular.forEach($scope.formData.detail, function(val, i) {
                if (!val || !val.job_order_id) {
                    return;
                }
                jo += val.job_order_id + ','
                })
                // console.log(jo)
                return jo.substring(0, jo.length - 1);
            }

            $scope.addInvoiceOther = function() {
                var html = "";
                var ctype = $scope.ctype;
                html += '<tr id="rowD-' + $scope.counter + '">';
                html += '<td>' + (ctype.job_order_id ? ctype.parent : "-") + '</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>-</td>';
                html += '<td>' + ctype.name + '</td>';
                html += '<td>' + $scope.detailInv.description + '</td>';
                html += '<td>' + $scope.detailInv.qty + '</td>';
                html += '<td>-</td>';
                html += '<td>' + $filter('number')($scope.detailInv.price) + '</td>';
                html += '<td>' + $filter('number')($scope.detailInv.total_price) + '</td>';
                html += '<td><input readonly ng-click="ppnSet(' + $scope.counter + ')" jnumber2 only-num ng-model="formData.detail[' + $scope.counter + '].total_discount_tax" class="form-control text-right"></td>'
                html += '<td><a ng-click="deleteDetail(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                html += '</tr>';

                $scope.formData.detail.push({
                job_order_id: ctype.job_order_id,
                job_order_detail_id: null,
                work_order_id: null,
                cost_type_id: ctype.id,
                price: $scope.detailInv.price,
                total_price: $scope.detailInv.total_price,
                imposition: null,
                imposition_name: '-',
                commodity_name: '-',
                qty: $scope.detailInv.qty,
                description: $scope.detailInv.description,
                is_other_cost: 1,
                type_other_cost: 1,
                manifest_id: null,
                ppn: 0,
                discount: 0,
                is_ppn: 0,
                detail_tax: [{
                    id: 1,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 2,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 3,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 4,
                    tax_id: null,
                    value: 0
                    },
                    {
                    id: 5,
                    tax_id: null,
                    value: 0
                    },
                ],
                code: ctype.job_order_id ? ctype.parent : "-",
                no_po: '-',
                trayek: '-',
                nopol: '-',
                driver: '-',
                container_no: '-',
                service: ctype.name,
                price_table_satuan: $scope.detailInv.price,
                price_table_total: $scope.detailInv.total_price,
                })

                $scope.hitungTotal($scope.formData.detail.length-1);
                // $('#tableDetail tbody').append($compile(html1)($scope));
                $scope.hitungSubTotalDetail()


                $('#invoiceAddModal').modal('hide');
            }
            $scope.addInvoiceAdditional = function() {
                var html = "";
                var ctype = $scope.ctype;
                html += '<tr id="rowK-' + $scope.counter + '">';
                html += '<td>' + ctype.code + ' - ' + ctype.name + '</td>';
                html += '<td>' + $scope.detailInv.description + '</td>';
                html += '<td>' + $scope.detailInv.qty + '</td>';
                html += '<td>-</td>';
                html += '<td>' + $filter('number')($scope.detailInv.price) + '</td>';
                html += '<td>' + $filter('number')($scope.detailInv.total_price) + '</td>';
                html += '<td><a ng-click="deleteK(' + $scope.counter + ')"><span class="fa fa-trash"></span></a></td>';
                html += '</tr>';

                $scope.formData.detail.push({
                job_order_id: null,
                job_order_detail_id: null,
                work_order_id: null,
                cost_type_id: ctype.id,
                price: $scope.detailInv.price,
                total_price: $scope.detailInv.total_price,
                imposition: null,
                imposition_name: '-',
                commodity_name: '-',
                qty: $scope.detailInv.qty,
                description: $scope.detailInv.description,
                is_other_cost: 1,
                type_other_cost: 2,
                manifest_id: null
                })

                $('#tableAdditional tbody').append($compile(html)($scope));
                $scope.hitungSubTotalAdditional()


                $('#invoiceAddModal').modal('hide');
            }

            $scope.cariJO = function(id) {
                if (id) {
                $scope.params.customer_id = id;
                jo_datatable.ajax.reload(function() {
                    $('#modalJO').modal('show');
                });
                } else {
                toastr.error("Customer Belum Dipilih!", "Maaf!");
                }
            }
            $scope.wo_collectible = [];
            $scope.cariWO = function(id) {
                if (id) {
                    $scope.params.customer_id = id;
                    $http.get(baseUrl + '/operational/invoice_jual/cari_wo_collectible/' + id).then(function(data) {
                        $scope.wo_collectible = [];
                        angular.forEach(data.data, function(val, i) {
                        $scope.wo_collectible.push(val.wo_id);
                        })
                    });
                    wo_datatable.ajax.reload(function() {
                        $('#modalWO').modal('show');
                    });
                } else {
                    toastr.error("Customer Belum Dipilih!", "Maaf!");
                }
            }

            var jo_datatable = $('#jo_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                order: [
                [2, 'desc'],
                [1, 'desc'],
                ],
                ajax: {
                headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                },
                url: baseUrl + '/api/operational/job_order_datatable',
                data: function(d) {
                    d.customer_id = $scope.params.customer_id;
                    d.service_not_in = [4];
                    d.not_invoice = true;
                    d.is_done = 1;
                    d.exclude_borongan = 1;
                    d.company_id = $scope.formData.company_id;
                    d.jo_list_append = $scope.jo_list_append()
                }
                },
                columns: [{
                    data: "action_choose",
                    name: "created_at",
                    className: "text-center",
                    orderable: false,
                    searchable: false
                },
                {
                    data: "code",
                    name: "job_orders.code"
                },
                {
                    data: null,
                    name : 'job_orders.shipment_date',
                    searchable: false,
                    render: resp => $filter('fullDate')(resp.shipment_date)
                },
                {
                    data: "no_po_customer",
                    name: "no_po_customer",
                    className: "font-bold"
                },
                {
                    data: "service_name",
                    name: "services.name"
                },
                {
                    data: "service_type_name",
                    name: "service_types.name",
                    className: ""
                },
                {
                    data: "route_name",
                    name: "routes.name"
                },
                {
                    data: "customer_name",
                    name: "contacts.name",
                    className: "font-bold"
                },
                {
                    data: "receiver_name",
                    name: "receivers.name",
                    className: ""
                },
                {
                    data: "sender_name",
                    name: "senders.name",
                    className: ""
                },
                ],
                createdRow: function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
                }
            });
            var wo_datatable = $('#wo_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                order: [
                [6, 'desc']
                ],
                ajax: {
                headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                },
                url: baseUrl + '/api/marketing/work_order_datatable',
                data: function(d) {
                    d.customer_id = $scope.formData.customer_id;
                    d.company_id = $scope.formData.company_id;
                    d.wo_done = 1;
                    d.jo_list_append = $scope.jo_list_append()
                    d.is_invoice = "0";
                    d.is_not_invoice = true
                    // d.id_list_collectible=$scope.wo_collectible;
                }
                },
                columns: [{
                    data: "action_choose",
                    name: "action_choose",
                    className: "text-center"
                },
                {
                    data: "code",
                    name: "code"
                },
                {
                    data: "aju_number",
                    name: "aju_number"
                },
                {
                    data: "no_bl",
                    name: "no_bl"
                },
                {
                    data: "po_customer",
                    name: "jo.po_customer"
                },
                {
                    data: "total_jo",
                    name: "total_jo",
                    orderable: false,
                    searchable  : false,
                    className: "font-bold"
                },
                {
                    data: "status",
                    name: "created_at"
                },
                ],
                createdRow: function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
                }
            });

            $scope.showIncludedTaxes = function() {
                if(!$stateParams.id) {
                    var payload = {}
                    payload.is_auto_invoice = 1
                    costTypesService.api.index(payload, function(list){
                        for(l in list) {
                            costTypesService.api.show(list[l].id, function(dt) {
                                $scope.ctype = {}
                                $scope.ctype.id = dt.id
                                $scope.ctype.name = dt.name
                                $scope.detailInv = {}
                                $scope.detailInv.qty = 1
                                $scope.detailInv.price = dt.cost
                                $scope.detailInv.total_price = dt.cost
                                $scope.addInvoiceOther()
                            })
                        }
                    })
                }
            }

            $scope.back = function(){
                if($scope.indexRoute) {
                    $state.go($scope.indexRoute)
                } else {
                    $state.go('operational.invoice_jual')
                }
            }

            $scope.disBtn = false;
            $scope.submitForm = function() {
                $scope.disBtn = true;
                var method, url
                if ($stateParams.id == null) {
                    method = 'post'
                    url = baseUrl + '/operational/invoice_jual'
                } else {
                    method = 'put'
                    url = baseUrl + '/operational/invoice_jual/' + $stateParams.id
                }
                $http[method](url, $scope.formData).then(function(data) {
                $scope.back();
                toastr.success("Data Berhasil Disimpan.", "Berhasil!");
                $scope.disBtn = false;
                }, function(error) {
                $scope.disBtn = false;
                if (error.status == 422) {
                    var det = "";
                    angular.forEach(error.data.errors, function(val, i) {
                    det += "- " + val + "<br>";
                    });
                    toastr.warning(det, error.data.message);
                } else {
                    toastr.error(error.data.message, "Error Has Found !");
                }
                });
            }
        }
    }
});