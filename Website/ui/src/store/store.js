import Vue from 'vue'
import Vuex from 'vuex'
import { Person } from '@/classes/person'
import { Meters } from '@/classes/person/meters'
import * as auth from '../store/modules/authentication'
import * as settings from '../store/modules/settings'
import * as resolution from '../store/modules/resolution'
import * as breadcrumb from '../store/modules/breadcrumb'
import * as clusterDashboard from '../store/modules/clusterDashboard'
import VuexPersist from 'vuex-persist'

Vue.use(Vuex)
const vuexLocalStorage = new VuexPersist({
    reducer: (state) => ({
        auth: {
            authenticateUser: state.auth.authenticateUser,
        },
        settings: {
            mainSettings: state.settings.mainSettings,
            ticketSettings: state.settings.ticketSettings,
            mapSettings: state.settings.mapSettings,
        },
        resolution: {
            width: state.resolution.width,
            height: state.resolution.height,
            isMobile: state.resolution.isMobile
        },
        breadcrumb: {
            breadcrumb: state.breadcrumb
        },
        clusterDashboard: {
            clustersCacheData: state.clusterDashboard.clustersCacheData

        }
    }),
    key: 'vuex',
    storage: window.localStorage,
})
export default new Vuex.Store({
    modules: {
        auth,
        settings,
        resolution,
        breadcrumb,
        clusterDashboard
    },
    plugins: [vuexLocalStorage.plugin],
    state: {
        person: new Person(),
        meters: new Meters(),
        search: {},
    },
    getters: {
        person: state => state.person,
        meters: state => state.meters,
        search: state => state.search,
        resolution: state => state.resolution,
        breadcrumb: state => state.breadcrumb
    }
})
