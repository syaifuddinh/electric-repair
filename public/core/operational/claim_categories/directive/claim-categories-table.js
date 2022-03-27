operationalClaimCategories.directive('operationalClaimCategoriesTable', function () {
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
        templateUrl: '/core/operational/claim_categories/view/claim-categories-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, operationalClaimCategoriesService) {

            $('.ibox-content').addClass('sk-loading');

            $scope.formData = {};
            if($scope.source) {
                $scope.formData.source = $scope.source
            }

            var columnDefs = [
                {title : $rootScope.solog.label.claim_category.title},
            ]

            var columns = [
                {data:"name",name:"claim_categories.name"},
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
                        <a ng-click='edit($event.currentTarget)' ><span class='fa fa-edit'></span></a>&nbsp;
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
                  url : operationalClaimCategoriesService.url.datatable(),
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
                    var first = $(row).find('td:first-child');
                    var a, b
                    a = $('<a ng-click="chooseItem(' + data.id + ')">' + first.text() + '</a>')
                    first.empty()
                    first.append(a)
                    $compile(angular.element(row).contents())($scope)
                }
            }

            $scope.add = function() {
                $scope.modalKategoriKlaimTitle='Tambah Kategori Klaim';
                $scope.url = operationalClaimCategoriesService.url.store();
                $scope.method='post';
                $scope.formData.name='';
                $('#modalKategoriKlaim').modal('show');
            }

            $scope.edit=function(e) {
                var tr = $(e).parents('tr')
                var data = $scope.options.datatable.row(tr).data()

                $scope.modalKategoriKlaimTitle='Edit Kategori Klaim';
                $scope.url = operationalClaimCategoriesService.url.update(data.id);
                $scope.method='put';
                $scope.formData.name=data.name;
                $('#modalKategoriKlaim').modal('show');
            }

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    operationalClaimCategoriesService.api.destroy(ids, function(resp){
                        $scope.$broadcast('reload', 0)
                    })
                }
            }

            $scope.submitForm = function() {
                $scope.disBtn = true;
                $http[$scope.method]($scope.url, $scope.formData).then(function(data) {
                    $('#modalKategoriKlaim').modal('hide')
                    $scope.$broadcast('reload', 0)
                    toastr.success("Data Berhasil Disimpan!");
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