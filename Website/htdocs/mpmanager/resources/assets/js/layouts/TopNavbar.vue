<template>

    <md-toolbar md-elevation="0" style="background:#424040">
        <div class="md-toolbar-row">
            <div class="md-toolbar-section-start">
                <breadcrumb/>
            </div>

            <div class="md-toolbar-section-end">
                <div>
                    <md-menu
                        md-direction="bottom-end"
                        md-size="big"
                        class="menu-item">
                        <md-button  class=" md-dense nav-button md-raised" md-menu-trigger>
                            <md-tooltip md-direction="bottom">{{$tc('words.location',2)}}</md-tooltip>
                             <md-icon class="c-white">add_location_alt</md-icon>
                            <small>Locations</small>
                        </md-button>
                        <md-menu-content>
                            <md-menu-item disabled>
                                <span>{{$tc('words.location',2)}}</span>
                                <md-icon>add_location_alt</md-icon>
                            </md-menu-item>
                            <md-menu-item @click="replaceRoute('/locations/add-cluster')">
                                          {{$tc('menu.subMenu.Add Cluster')}}
                            </md-menu-item>
                            <md-menu-item @click="replaceRoute('/locations/add-mini-grid')">
                                {{$tc('menu.subMenu.Add MiniGrid')}}
                            </md-menu-item>
                            <md-menu-item @click="replaceRoute('/locations/add-village')">
                                {{$tc('menu.subMenu.Add Village')}}
                            </md-menu-item>
                        </md-menu-content>
                    </md-menu>
                    <md-menu
                        class="menu-item"
                        md-direction="bottom-end"
                        md-size="big">
                        <md-button class=" md-dense nav-button md-raised" md-menu-trigger>
                            <md-tooltip md-direction="bottom">Settings</md-tooltip>
                            <md-icon class="c-white">settings</md-icon>
                            <small>Settings</small>
                        </md-button>
                        <md-menu-content>
                            <md-menu-item @click="replaceRoute('/settings')">
                                <span>Config</span>
                                <md-icon>settings</md-icon>
                            </md-menu-item>
                            <md-divider></md-divider>
                            <md-menu-item disabled="">
                                <span>{{$tc('words.connection')}}</span>
                                <md-icon>cast</md-icon>
                            </md-menu-item>
                            <md-menu-item @click="replaceRoute('/connection-groups')">
                                {{$tc('words.group',2)}}</md-menu-item>
                            <md-menu-item @click="replaceRoute('/connection-types')">
                                {{$tc('words.type',2)}}
                            </md-menu-item>
                        </md-menu-content>
                    </md-menu>
                    <md-menu
                        class="menu-item"
                        md-direction="bottom-end"
                        md-size="big">
                        <md-button class="nav-button md-raised md-dense " md-menu-trigger>
                            <md-icon class="c-white">person</md-icon>
                            <small>{{ adminName }}</small>
                            <md-icon class="c-white">keyboard_arrow_down</md-icon>
                        </md-button>
                        <md-menu-content>
                            <div class="author-card">
                                <div class="md-layout">
                                    <md-icon>account_circle</md-icon>
                                </div>
                                <div class="md-layout md-alignment-center">
                                    {{ adminName }}
                                </div>
                                <hr>
                            </div>
                            <md-menu-item  @click="replaceRoute('/profile')">
                                {{$tc('words.profile')}}
                            </md-menu-item>
                            <md-menu-item @click="replaceRoute('/profile/management')">
                                {{$tc('phrases.userManagement')}}
                            </md-menu-item>
                            <md-menu-item @click="logout()">
                                Log Out
                            </md-menu-item>
                        </md-menu-content>
                    </md-menu>
                </div>


            </div>
        </div>
    </md-toolbar>
</template>

<script>
import Breadcrumb from '../shared/Breadcrumb'
export default {
    components: {
        Breadcrumb
    },
    data () {
        return {
            open: false,
            toggleCard: false

        }
    },
    methods: {
        logout () {
            this.$store.dispatch('auth/logOut').then(() => {
                this.$router.replace('/login')
            })

        },
        toggle () {
            this.toggleCard = !this.toggleCard
        },
        replaceRoute(route){
            this.$router.replace(route)
        }

    },
    computed: {
        adminName () {
            return this.$store.getters['auth/getAuthenticateUser'].name
        },
    }

}
</script>

<style lang="css">

.nav-button{
    background-color: #3d3d3d!important;
    color: white!important;
}
.menu-item{
    padding-right: 1vh
}
.author-card{
    min-width: 30%!important;
    text-align: center;
    color: #3d3d3d;
    font-size: 1rem;
    font-weight: 300;
    padding: 1vh;
    margin-top: 0;
}
.c-white{
    color: white;
}

</style>
