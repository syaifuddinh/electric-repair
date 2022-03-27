operationalClaims.directive('operationalClaimsTable', function () {
    return {
        restrict: 'E',
        scope: {
            'manifest_id' :'=manifestId',
            'hide_type' :'=hideType',
            'code_column_name' :'=codeColumnName',
            'addRoute' : '=addRoute',
            'detail_route' : '=detailRoute',
            'source' :'=source'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/claims/view/claims-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, operationalClaimsService) {

            $('.ibox-content').addClass('sk-loading');

            $rootScope.pageTitle = "Klaim";
            $scope.isFilter = false;
            $scope.formData = {}
            $scope.data = {}

            $scope.showCustomer = function() {
                $http.get(baseUrl + '/contact/contact/customer').then(function(data) {
                $scope.data.customers = data.data;
                }, function() {
                $scope.showCustomer()
                });
            }
            $scope.showCustomer()

            $scope.showVendor = function() {
                $http.get(baseUrl + '/contact/contact/vendor').then(function(data) {
                $scope.data.vendors = data.data;
                }, function() {
                $scope.showVendor()
                });
            }
            $scope.showVendor()

            $scope.showCompany = function() {
                $http.get(baseUrl + '/setting/company/create').then(function(data) {
                $scope.data.company = data.data.company;
                }, function() {
                $scope.showCompany()
                });
            }
            $scope.showCompany()

            var columnDefs = [
                {title : $rootScope.solog.label.general.code},
                {title : $rootScope.solog.label.general.branch},
                {title : $rootScope.solog.label.claim.date},
                {title : $rootScope.solog.label.claim.code},
                {title : $rootScope.solog.label.claim.jo_so_date},
                {title : $rootScope.solog.label.general.customer},
                {title : $rootScope.solog.label.claim.collectible},
            ]

            var columns = [
                { data: "code", name: "claims.code" },
                { data: "company_name", name: "companies.name" },
                { data: null, name: 'claims.date_transaction', searchable:false, render: e => $filter('fullDate')(e.date_transaction) },
                { data: "jo_so_code", name: "jo_so_code" },
                { data: null, name: 'jo_so_date', searchable:false, render: e => $filter('fullDate')(e.jo_so_date) },
                { data: "customer_name", name: "customers.name" },
                { data: "collectible_name", name: "collects.name" },
                {
                    data: null,
                    searchable:false,
                    orderable:false,
                    className : 'text-center',
                    render : function(r) {
                        var color = 'warning'
                        if(r.status == 2) {
                            color = 'primary'
                        }
            
                        return '<span class="badge badge-' + color + '">' + r.status_name + '</span>'
                    }
                }
            ]

            if(!$attrs['hideAction']) {
                columnDefs.push({title : ''})
                columns.push({
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:"text-center",
                    render:function(e) {
                    let html = `
                        <a ui-sref='operational.claims.show({id:${e.id}})' ><span class='fa fa-folder-o' data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;
                        <a ui-sref='operational.claims.edit({id:${e.id}})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;
                        <a ng-click="deletes(${e.id})"><span class="fa fa-trash"></span></a>
                    `
                    return html
                  }})
            }


           var options = {
                order:[[0,'asc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : operationalClaimsService.url.datatable(),
                  data : e => Object.assign(e, $scope.formData),
                  dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                  }
                },
                dom: 'Blfrtip',
                buttons: [
                  {
                    'extend' : 'excel',
                    'enabled' : true,
                    'action' : newExportAction,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : $rootScope.solog.label.customer_order.title + ' - '+new Date(),
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
            }

            $scope.options = options

            if($attrs['inputMode']) {
                $scope.createdRow = function(row, data) {
                    $(row).find('td').attr('ui-sref', 'operational.claim.show({id:' + data.id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                    $compile(angular.element(row).contents())($scope);
                }
            }

            $scope.add = function() {
                if($scope.addRoute) {
                    $state.go($scope.addRoute)
                } else {
                    $state.go('operational.claims.create')
                }
            }

            $scope.edit=function(e) {
                if($scope.editRoute) {
                    $state.go($scope.editRoute, {'id' : id})
                } else {
                    $state.go('operational.claims.edit', {'id' : id})
                }
            }

            $scope.show=function(id) {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, {'id' : id})
                } else {
                    $rootScope.insertBuffer()
                    $state.go('operational.claims.show', {'id' : id})
                }
            }
            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    operationalClaimsService.api.destroy(ids, function(resp){
                        $scope.$broadcast('reload', 0)
                    })
                }
            }

            $scope.filter = function() {
                $scope.$broadcast('reload', 0)
            }

            $scope.resetFilter = function() {
                $scope.formData = {};
                $scope.$broadcast('reload', 0)
            }

        }
    }
});