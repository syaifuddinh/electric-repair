<li ng-show="roleList.includes('setting')">
    <a>
        <i class="fa fa-gears"></i>
        <span class="nav-label">Setting</span><span class="fa arrow"></span>
    </a>
    <ul class="nav nav-second-level collapse">
        <li
            ui-sref-active="active"
            ng-show="roleList.includes('setting.regional')"
        >
            <a ui-sref="setting.area">
                <span class="nav-label">Area</span>
            </a>
        </li>

        <li
            ui-sref-active="active"
        >
            <a ui-sref="setting.company">
                <span class="nav-label">Company / Branch</span>
            </a>
        </li>

        <li
            ui-sref-active="active"
        >
                <a ui-sref="setting.user">
                    <span class="nav-label">User Management</span>
                </a>
        </li>

        <li
            ui-sref-active="active"
        >
                <a ui-sref="setting.master_harga">
                    <span class="nav-label">Master Harga</span>
                </a>
        </li>
      
    </ul>
</li>