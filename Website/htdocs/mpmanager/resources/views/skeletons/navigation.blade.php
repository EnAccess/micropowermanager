<aside id="left-panel">

    <!-- User info -->
    <user-data></user-data>
    <!-- end user info -->

    <!-- NAVIGATION : This navigation is also responsive-->
    <nav>
        <ul>
            <li>
                <a href="#" title="Dashboard"><i class="fa fa-lg fa-fw fa-home"></i> <span class="menu-item-parent">Dashboard</span></a>
                <ul>
                    <li>
                        <router-link to="/clusters"><i class="fa fa-user"></i>Clusters</router-link>
                    </li>
                    <li>
                        <router-link to="/dashboards/mini-grid/"><i class="fa fa-bank fa-lg fa-fw"></i>
                            <span class="menu-item-parent">MiniGrid</span>
                        </router-link>
                    </li>

                </ul>
            </li>
            <li>
                <router-link to="/people">
                    <i class="fa fa-user fa-lg fa-fw"></i>
                    <span class="menu-item-parent">Customers</span>
                </router-link>
            </li>

            <li>
                <router-link to="/meters"><i class="fa fa-bolt fa-lg fa-fw"></i>
                    <span class="menu-item-parent">Meter list</span>
                </router-link>
            </li>


            <li>
                <router-link to="/transactions"><i class="fa fa-bank fa-lg fa-fw"></i>
                    <span class="menu-item-parent">Transactions</span>
                </router-link>
            </li>


            <li>
                <a href="#"><i class="fa fa-bolt fa-lg fa-ticket"></i>
                    <span class="menu-item-parent">Tickets</span></a>
                <ul>
                    <li>
                        <router-link to="/tickets" active-class="active">List</router-link>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-cogs"></i> Settings</a>
                        <ul>
                            <li>
                                <router-link to="/tickets/settings/users"><i class="fa fa-user"></i>Users</router-link>
                            </li>
                            <router-link tag="li" to="/tickets/settings/categories" active-class="active"><a><i
                                            class="fa fa-sort-alpha-desc"></i>Categories</a>
                            </router-link>
                        </ul>

                    </li>


                </ul>
            </li>

            <router-link tag="li" to="/tariffs" active-class="active">
                <a>
                    <i class="fa fa-book"></i>
                    <span class="menu-item-parent">Tariffs</span>
                </a>
            </router-link>


            <router-link tag="li" to="/targets" active-class="active">
                <a>
                    <i class="fa fa-crosshairs"></i>
                    <span class="menu-item-parent">Targets</span>
                </a>
            </router-link>

            <router-link tag="li" to="/reports" active-class="active">
                <a>
                    <i class="fa fa-file-excel-o"></i>
                    <span class="menu-item-parent">Reports</span>
                </a>
            </router-link>

            <router-link tag="li" to="/sms" active-class="active">
                <a>
                    <i class="fa fa-comments"></i>
                    <span class="menu-item-parent">Sms</span>
                </a>
            </router-link>
            <router-link tag="li" to="/assets/types" active-class="active">
                <a>
                    <i class="fa fa-money"></i>
                    <span class="menu-item-parent">Asset Types</span>
                </a>
            </router-link>


            <router-link tag="li" to="/maintenance" active-class="active">
                <a>
                    <i class="fa fa-wrench"></i>
                    <span class="menu-item-parent">Maintenance</span>
                </a>
            </router-link>

        </ul>
    </nav>


    <span class="minifyme" data-action="minifyMenu">
				<i class="fa fa-arrow-circle-left hit"></i>
			</span>

</aside>
