var warehouseReceipts = angular.module('warehouseReceipts', ['ui.router', 'branchs'], () => {})

warehouseReceipts.service('warehouseReceiptsService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.downloadImportItem = () => baseUrl + '/operational_warehouse/receipt/download_import_item'
    url.importItem = () => baseUrl + '/operational_warehouse/receipt/import_item'
    url.index = () => baseUrl + '/operational_warehouse/warehouse_receipt'
    url.show = (id) => baseUrl + '/operational_warehouse/warehouse_receipt/' + id,
    url.approve = (id) => baseUrl + '/operational_warehouse/receipt/approve/' + id,
    url.store = () => baseUrl + '/operational_warehouse/warehouse_receipt'
    url.destroy = (id) => baseUrl + '/operational_warehouse/warehouse_receipt/' + id,
    url.datatable = () => baseUrl + '/api/operational_warehouse/warehouse_receipt_datatable'
    this.url = url

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
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

    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.approve(id)).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
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

    api.importItem = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $rootScope.disBtn = true
        $.ajax({
            url: url.importItem() + '?_token=' + csrfToken,
            contentType: false,
            processData: false,
            type: 'POST',
            data: payload,
            beforeSend: function (request) {
                request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
            },
            success: function (resp) {
                $rootScope.disBtn = false;
                toastr.success(resp.message)
                fn(resp.data)
            },
            error: function (resp) {
                toastr.error(resp.responseJSON.message, "Error Has Found !");
            }
        });
    }

    api.update = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(baseUrl+'/setting/additional_field', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data)
        }, function(){
        });
    }

    api.index = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.index()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.index(payload, fn)
        });
    }

    this.api = api
})