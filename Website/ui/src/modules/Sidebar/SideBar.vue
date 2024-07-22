<template>
    <div class="sidebar" :data-color="sidebarItemColor" :style="sidebarStyle">
        <div class="logo">
            <div class="brand-column">
                <img class="logo" alt="logo" :src="imgLogo" />

                <div class="company-header">
                    {{ $store.getters['settings/getMainSettings'].companyName }}
                    <br />
                    <small>Powered by MPM</small>
                </div>
            </div>
        </div>

        <div class="sidebar-wrapper">
            <slot name="content"></slot>
            <md-list class="no-bg p-15" md-expand-single>
                <template v-for="menu in routes">
                    <template v-if="menu.meta?.sidebar?.enabled">
                        <!-- If the route has no children, then it should be a clickable menu item -->
                        <router-link
                            v-if="!hasSubMenu(menu)"
                            :to="route(menu.path)"
                            :exact-path="true"
                            :key="'menu' + menu.path"
                        >
                            <md-list-item>
                                <md-icon
                                    v-if="menu.meta?.sidebar?.icon"
                                    class="c-white icon-box"
                                >
                                    {{ menu.meta.sidebar.icon }}
                                </md-icon>
                                <span class="md-list-item-text c-white">
                                    {{
                                        $tc(
                                            'menu.' +
                                                menu.meta?.sidebar?.name ??
                                                menu.path,
                                        )
                                    }}
                                </span>
                                <md-icon
                                    v-if="protectedPages.includes(menu.path)"
                                    class="c-white password-protected-lock-icon"
                                >
                                    lock
                                </md-icon>
                            </md-list-item>
                        </router-link>

                        <!-- If the route has children, then it should be a nested, expanbable list with sub menues -->
                        <div v-else :key="'submenu' + menu.path">
                            <md-list-item md-expand>
                                <md-icon
                                    v-if="menu.meta?.sidebar?.icon"
                                    class="c-white icon-box"
                                >
                                    {{ menu.meta.sidebar.icon }}
                                </md-icon>
                                <span class="md-list-item-text c-white">
                                    {{ menu.meta?.sidebar?.name ?? menu.path }}
                                </span>
                                <md-list slot="md-expand" class="no-bg">
                                    <router-link
                                        v-for="sub in menu.children"
                                        :key="sub.path"
                                        :to="
                                            route(
                                                subMenuUrl(menu.path, sub.path),
                                            )
                                        "
                                        class="sub-menu"
                                        :exact-path="true"
                                    >
                                        <template
                                            v-if="sub.meta?.sidebar?.enabled"
                                        >
                                            <md-list-item>
                                                <span
                                                    class="md-list-item-text c-white"
                                                >
                                                    {{
                                                        $tc(
                                                            'menu.subMenu.' +
                                                                sub.meta
                                                                    ?.sidebar
                                                                    ?.name ??
                                                                sub.path,
                                                        )
                                                    }}
                                                </span>
                                                <md-icon
                                                    v-if="
                                                        protectedPages.includes(
                                                            subMenuUrl(
                                                                menu.path,
                                                                sub.path,
                                                            ),
                                                        )
                                                    "
                                                    class="c-white password-protected-lock-icon"
                                                >
                                                    lock
                                                </md-icon>
                                            </md-list-item>
                                        </template>
                                    </router-link>
                                </md-list>
                            </md-list-item>
                        </div>
                    </template>
                </template>
            </md-list>
        </div>
    </div>
</template>
<script>
import { translateItem } from '@/Helpers/TranslateItem'
import { EventBus } from '@/shared/eventbus'
import PasswordProtection from '@/shared/PasswordProtection'

export default {
    name: 'SideBar',
    mixins: [PasswordProtection],

    data() {
        return {
            show_extender: false,
            admin: null,
            menus: this.$store.getters['settings/getSidebar'],
            translateItem: translateItem,
            routes: this.$router.options.routes,
        }
    },

    props: {
        title: {
            type: String,
            default: 'MicroPowerManager Open Source',
        },
        sidebarBackgroundImage: {
            type: String,
            default: null,
        },
        imgLogo: {
            type: String,
            default: require('@/assets/images/Logo1.png'),
        },
        sidebarItemColor: {
            type: String,
            default: 'green',
        },

        autoClose: {
            type: Boolean,
            default: true,
        },
    },
    provide() {
        return {
            autoClose: this.autoClose,
        }
    },

    mounted() {
        this.setSidebar()
        EventBus.$on('setSidebar', async () => {
            await this.$store.dispatch('settings/setSidebar')
            this.menus = this.$store.getters['settings/getSidebar']
        })
    },
    methods: {
        hasSubMenu(menu) {
            // We show a submenu if the menu has children and at least one of them has sidebar enabled
            if (menu.children && menu.children.length > 0) {
                for (let child of menu.children) {
                    if (child.meta?.sidebar?.enabled) {
                        return true
                    }
                }
            }
            return false
        },
        subMenuUrl(basePath, subPath) {
            return `${basePath}/${subPath}`
        },
        async setSidebar() {
            if (!this.menus.length) {
                await this.$store.dispatch('settings/setSidebar')

                this.menus = this.$store.getters['settings/getSidebar']
            }
        },
        translateMenuItem(name) {
            if (this.$tc('menu.' + name).search('menu') !== -1) {
                return name
            } else {
                return this.$tc('menu.' + name)
            }
        },
        route(routeUrl) {
            // In the backend/database these are sometimes stored as (for example)
            // /meters/page/1
            // but we actually need to convert that to query params
            if (routeUrl !== '') {
                if (routeUrl.includes('/page/1')) {
                    routeUrl = routeUrl.split('/page/1')[0]
                    return {
                        path: routeUrl,
                        query: { page: 1, per_page: 15 },
                    }
                } else {
                    return { path: routeUrl }
                }
            }
        },
    },
    computed: {
        adminName() {
            return this.$store.getters['auth/getAuthenticateUser'].name
        },
        sidebarStyle() {
            return {
                background: '#2b2b2b !important',
            }
        },
    },
}
</script>
<style>
.brand-column {
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex;
    overflow: visible;
    margin-top: 0px;
    -webkit-box-align: center;
    -webkit-align-items: center;
    -ms-flex-align: center;
    align-items: center;
    grid-auto-columns: 1fr;
    grid-column-gap: 16px;
    grid-row-gap: 16px;
    -ms-grid-columns: 1fr 1fr;
    grid-template-columns: 1fr 1fr;
    -ms-grid-rows: auto auto;
    grid-template-rows: auto auto;
    -o-object-fit: fill;
    object-fit: fill;
}

.brand-column {
    text-align: center;
    padding-left: 2rem !important;
}

@media screen and (min-width: 991px) {
    .brand-column {
        dtext-align: center;
        padding-left: 1rem !important;
    }
}

.md-list-item-text {
    font-size: 0.8rem !important;
    font-weight: 400 !important;
}

@media screen and (min-width: 991px) {
    .nav-mobile-menu {
        display: none;
    }
}

.company-header {
    color: white;
    font-weight: bold;
}

.active-link {
    background-color: rgba(32, 66, 32, 0.74);
}

.exact-active {
    background: #6b6a6a !important;
    position: relative;
    width: calc(100%) !important;
    border-right: 5px solid #9d302a;
}

.no-bg {
    background-color: transparent !important;
}

.md-icon.md-theme-default.md-icon-image svg {
    fill: #f5e8e8 !important;
}

.c-white {
    color: #f5e8e8 !important;
}

.sidebar-layout {
    position: absolute;
    height: 100%;
    width: 100%;
}

.icon-box {
    margin-right: 10px !important;
    width: 25px !important;
    height: 25px !important;
}

.md-icon.password-protected-lock-icon {
    font-size: 16px !important;
}

.sub-menu {
    width: 100% !important;
}

.c-gray {
    color: gray;
}

.app-style {
    width: calc(100% / 12 * 2);
    position: fixed;
}

.drawer-style {
    background-color: #2b2b2b !important;
    height: 100vh;
}

.p-15 {
    padding: 10px;
}
</style>
