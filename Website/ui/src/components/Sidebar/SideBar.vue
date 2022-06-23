<template>
    <div
        class="sidebar"
        :data-color="sidebarItemColor"
        :style="sidebarStyle"
    >
        <div class="logo">
            <div class="brand-column">

                <img class="logo" alt="logo" :src="imgLogo"/>

                <div class="company-header">{{ $store.getters['settings/getMainSettings'].companyName }}<br><small>Powered
                    by MPM</small></div>
            </div>


        </div>

        <div class="sidebar-wrapper">
            <slot name="content"></slot>
            <md-list class="no-bg p-15" md-expand-single>
                <component :is="menu.url_slug !== '' ? 'router-link' : 'div'" v-for="(menu,index) in menus" :key="index"
                           :md-expand="menu.sub_menu_items.length !== 0"
                           :to="route(menu.url_slug)"
                >
                    <md-list-item :md-expand="menu.sub_menu_items.length !== 0">
                        <!-- add icon if icon is defined -->
                        <md-icon v-if="menu.md_icon !== ''" class="c-white icon-box">{{ menu.md_icon }}</md-icon>
                        <span class="md-list-item-text c-white">{{ translateItem(menu.name) }}</span>
                        <md-list slot="md-expand" v-if="menu.sub_menu_items.length !== 0" class="no-bg">
                            <md-list-item v-for="(sub,index) in menu.sub_menu_items"
                                          :key="index"

                            >
                                <router-link
                                    :to="route(sub.url_slug)"
                                    class="sub-menu">
                                    <md-list-item class="md-inset c-white">
                                        <span
                                            class="md-list-item-text c-white"> {{ $tc('menu.subMenu.' + sub.name) }}</span>
                                    </md-list-item>
                                </router-link>
                            </md-list-item>
                        </md-list>
                    </md-list-item>
                </component>
            </md-list>

        </div>
    </div>
</template>
<script>

import { translateItem } from '@/Helpers/TranslateItem'

export default {
    name: 'SideBar',
    data () {
        return {
            show_extender: false,
            admin: null,
            menus: this.$store.getters['settings/getSidebar'],
            translateItem: translateItem
        }
    },

    props: {
        title: {
            type: String,
            default: 'MicroPowerManager Open Source'
        },
        sidebarBackgroundImage: {
            type: String,
            default: null
        },
        imgLogo: {
            type: String,
            default: require('../../assets/images/Logo1.png')
        },
        sidebarItemColor: {
            type: String,
            default: 'green',

        },

        autoClose: {
            type: Boolean,
            default: true
        }
    },
    provide () {
        return {
            autoClose: this.autoClose
        }
    },

    mounted () {

    },
    methods: {
        translateMenuItem (name) {
            if (this.$tc('menu.' + name).search('menu') !== -1) {
                return name
            } else {
                return this.$tc('menu.' + name)
            }

        },
        route (routeUrl) {
            if (routeUrl !== '') {
                if (routeUrl.includes('/page/1')) {
                    routeUrl = routeUrl.split('/page/1')[0]
                    return { path: routeUrl, query: { page: 1, per_page: 15 } }
                } else {
                    return { path: routeUrl }
                }
            }

        }

    },
    computed: {
        adminName () {
            return this.$store.getters['auth/getAuthenticateUser'].name
        },
        sidebarStyle () {
            return {

                background: '#2b2b2b !important'
            }
        }

    }
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
    padding-left: 2rem !important
}

@media screen and (min-width: 991px) {
    .brand-column {
        dtext-align: center;
        padding-left: 1rem !important
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
    left: -15px;
    width: calc(100% + 30px) !important;
}

/*  .md-list-item-text {
      color: #f5e8e8 !important;

  }*/

.no-bg {
    background-color: transparent !important;
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

