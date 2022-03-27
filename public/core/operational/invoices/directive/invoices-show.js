invoices.directive('invoicesShow', function () {
    return {
        restrict: 'E',
        scope: {
            type: '=type',
            hideType: '=',
            indexRoute: '=',
            editRoute: '='
        },
        templateUrl: '/core/operational/invoices/view/invoices-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, $compile, invoicesService, salesOrdersService, additionalFieldsService, unitsService) {
            $rootScope.pageTitle = "Detail Invoice Jual";
            $('.ibox-content').addClass('sk-loading');
            $scope.baseUrl = baseUrl;
            $scope.journal_status = 0;
            $scope.stateParams = $stateParams;
            var dt = {}
            $scope.status = [{
                id: 1,
                name: "Diajukan"
                },
                {
                id: 2,
                name: "Disetujui"
                },
                {
                id: 3,
                name: "Invoice"
                },
                {
                id: 4,
                name: "Terbayar Sebagian"
                },
                {
                id: 5,
                name: "Lunas"
                },
            ]
            $scope.type_bayar = [{
                id: 1,
                name: "Cash"
                },
                {
                id: 2,
                name: "Kredit"
                },
            ]
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
            $scope.discount_total = 0;

            $scope.namaImposition = function(val, stype) {
                if (stype == 2) {
                return "Kontainer"
                } else if (stype == 3) {
                return "Unit"
                } else {
                return $rootScope.findJsonId(val, $scope.imposition).name
                }
            }

            $scope.showDetail = function() {
                $http.get(baseUrl + '/operational/invoice_jual/' + $stateParams.id + '/detail').then(function(data) {
                    var detail = data.data
                    $scope.total_ppn = 0
                    angular.forEach(detail, function(val, i) {
                        $scope.discount_total += val.discount;
                        $scope.total_ppn += val.ppn;
                    })
                    $scope.detail1 = detail
                });
            }
            $scope.show = function() {
                $http.get(baseUrl + '/operational/invoice_jual/' + $stateParams.id).then(function(data) {
                dt = data.data;
                $scope.item = dt.item;
                $scope.tax_total = dt.taxes;
                $scope.addon = dt.addon

                $scope.showDetail()
                if ($scope.item.journal) {
                    $scope.journal_status = $scope.item.journal.status;
                }

                $('.ibox-content').removeClass('sk-loading');
                });
            }
            $scope.show()

            $scope.edit = function(){
                if($scope.editRoute) {
                    $state.go($scope.editRoute, { id: $scope.item.id })
                } else {
                    $state.go('operational.invoice_jual.edit', { id: $scope.item.id})
                }
            }

            $scope.cancelPosting = function() {
                var cofs = confirm("Apakah anda ingin membatalkan Posting Invoice ?");
                if (!cofs) {
                return null;
                }
                $scope.disBtn = true;
                $http.post(baseUrl + '/operational/invoice_jual/cancel_posting/' + $stateParams.id).then(function(data) {
                $timeout(function() {
                    $scope.show()
                }, 1000)
                toastr.success("Berhasil !");
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

            $scope.format = [{
                id: 2,
                name: 'INVOICE WO PERSATUAN'
                },
                {
                id: 3,
                name: 'INVOICE INTER ISLAND'
                },
                {
                id: 4,
                name: 'INVOICE TRUCKING'
                },
                {
                id: 5,
                name: 'INVOICE PROJECT'
                },
            ]
            $scope.printModal = function() {
                $scope.printData = {}
                $scope.printData.format = 2
                $scope.printData.show_ppn = 1
                $('#modalPrint').modal();
            }

            $scope.print = function() {
                var format = $scope.printData.format;
                window.open(baseUrl + '/operational/invoice_jual/print/' + $stateParams.id + '?format=' + format + '&show_ppn=' + ($scope.printData.show_ppn || 0));
            }

            $scope.disBtn = false;
            $scope.approve = function() {
                var confs = confirm("Apakah anda ingin menyetujui invoice ini ?");
                if (!confs) {
                return null;
                }
                $http.post(baseUrl + '/operational/invoice_jual/approve/' + $stateParams.id).then(function(data) {
                $state.reload();
                toastr.success("Invoice telah diposting!", "Berhasil!");
                });
            }

            $scope.openPosting = function() {
                $scope.postingData = {}
                $scope.postingData.journal_date = $filter('minDate')($scope.item.journal_date)
                $("#postingModal").modal();
            }

            $scope.posting = function() {
                var confs = confirm("Apakah anda yakin ?");
                if (!confs) {
                return null;
                }
                $scope.disBtn = true;
                $http.post(baseUrl + '/operational/invoice_jual/posting/' + $stateParams.id, $scope.postingData).then(function(data) {
                setTimeout(function() {
                    $("#postingModal").modal('hide');
                }, 1000)
                toastr.success("Invoice telah diposting!", "Berhasil!");
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
                }).then(function(e) {
                $scope.show()
                });
            }

            $scope.back = function(){
                if($scope.indexRoute) {
                    $state.go($scope.indexRoute)
                } else {
                    $state.go('operational.invoice_jual')
                }
            }
        }
    }
})