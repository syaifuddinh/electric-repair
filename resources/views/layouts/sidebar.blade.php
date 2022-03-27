<ul class="nav metismenu" id="side-menu" ng-cloak>
    @include('layouts.profile')
    <li ui-sref-active="active">
        <a ui-sref="home"><i class="fa fa-home"></i> <span class="nav-label">Dashboard</span></a>
    </li>

    @include('layouts.sidebar.setting')
    

    @include('layouts.sidebar.contact')
    
</ul>
