<li ng-show='settings.general.is_use_depo'>
    <a><i class="fa fa-tachometer"></i> <span class="nav-label">Depo Operational</span><span class="fa arrow"></span></a>
    <ul class="nav nav-second-level collapse" ui-sref-active="active">
        <li ui-sref-active="active" ng-show="roleList.includes('contact.contact')">
            <a><span class="nav-label">Setting</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse" ui-sref-active="active">
                <li ui-sref-active="active">
                    <a ui-sref="depo.container_part"><span class="nav-label">Container Part</span></a>
                </li>

                <li ui-sref-active="active">
                    <a ui-sref="depo.container_yard"><span class="nav-label">Container Yard</span></a>
                </li>

                <li ui-sref-active="active">
                    <a ui-sref="inventory.item_condition"><span class="nav-label">Container Condition</span></a>
                </li>
            </ul>
        </li>
        <li ui-sref-active="active">
            <a><span class="nav-label">Gate Activities</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse" ui-sref-active="active">
                <li ui-sref-active="active">
                    <a ui-sref="depo.gate_in_container"><span class="nav-label">Gate In Container</span></a>
                </li>
            </ul>
        </li>
        <li ui-sref-active="active">
            <a><span class="nav-label">MNR Container</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse" ui-sref-active="active">
                <li ui-sref-active="active">
                    <a ui-sref="depo.job_order"><span class="nav-label">Job Order</span></a>
                </li>
                <li ui-sref-active="active">
                    <a ui-sref="depo.container_inspection"><span class="nav-label">Container Inspection</span></a>
                </li>
            </ul>
        </li>
        <li ui-sref-active="active" ng-show="roleList.includes('contact.contact')">
            <a><span class="nav-label">Container Management</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse" ui-sref-active="active">
                <li ui-sref-active="active">
                    <a ui-sref="depo.movement_container"><span class="nav-label">Movement Container</span></a>
                </li>
                <li ui-sref-active="active">
                    <a ui-sref="operational.container"><span class="nav-label">Containers</span></a>
                </li>
                <li ui-sref-active="active">
                    <a ui-sref="depo.operator"><span class="nav-label">Operator</span></a>
                </li>
            </ul>
        </li>
    </ul>
</li>