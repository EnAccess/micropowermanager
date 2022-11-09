/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap')
import router from './routes'
import App from './App'
import 'leaflet/dist/leaflet.css'
import store from './store/store'
import UserData from './shared/UserData'
import Default from './layouts/Default'
import i18n from './i18n'
import { MapSettingsService } from './services/MapSettingsService'
import { TicketSettingsService } from './services/TicketSettingsService'
import { MainSettingsService } from './services/MainSettingsService'
import Steamaco from '@/plugins/steama-meter/js/components/Overview/Credential'
import Spark from '@/plugins/spark-meter/js/components/Overview/Credential'
import Calin from '@/plugins/calin-meter/js/components/Overview/Credential'
import CalinSmart from '@/plugins/calin-smart-meter/js/components/Overview/Credential'
import Kelin from '@/plugins/kelin-meter/js/components/Overview/Credential'
import Stron from '@/plugins/stron-meter/js/components/Overview/Credential'
import Settings from '@/components/Settings/MainSettings'
import Viber from '@/plugins/viber-messaging/js/components/Overview/Credential'

Vue.component('default', Default)
Vue.component('Spark-Meter', Spark)
Vue.component('Steamaco-Meter', Steamaco)
Vue.component('Calin-Meter', Calin)
Vue.component('CalinSmart-Meter', CalinSmart)
Vue.component('Kelin-Meter', Kelin)
Vue.component('Stron-Meter', Stron)
Vue.component('Settings', Settings)
Vue.component('Viber-Messaging', Viber)

router.beforeEach((to, from, next) => {
    const authToken = store.getters['auth/getToken']
    const intervalId = store.getters['auth/getIntervalId']
    if (['login', 'forgot_password', 'welcome', 'register'].includes(to.name)) {
        return next()
    }
    if (authToken === undefined || authToken === '') {
        return next({ name: 'welcome' })
    }
    store.dispatch('auth/refreshToken', authToken, intervalId).then((result) => {
        return result ? next() : next({ name: 'login' })
    }).catch(() => {
        return next({ name: 'welcome' })
    })
})

/*eslint-disable */
const app = new Vue({
    el: '#app',
    components: {
        UserData
    },
    data () {
        return {
            mainSettingsService: new MainSettingsService(),
            mapSettingService: new MapSettingsService(),
            ticketSettingsService: new TicketSettingsService(),
            resolution: {
                width: window.innerWidth,
                height: window.innerHeight,
                isMobile: false
            }
        }
    },
    mounted () {
        this.handleResize()
        window.addEventListener('resize', this.handleResize)
        this.$el.addEventListener('click', this.onHtmlClick)
    },
    beforeDestroy () {
        window.removeEventListener('resize', this.handleResize)
    },
    methods: {
        handleResize () {
            this.resolution.width = window.innerWidth
            this.resolution.height = window.innerHeight
            if (this.resolution.width <= 960) {
                this.resolution.isMobile = true
            } else {
                this.resolution.isMobile = false
            }
            this.$store.dispatch('resolution/setResolution', this.resolution).then(() => {
            }).catch((err) => {
                console.log(err)
            })
        }

    },
    router: router,
    store: store,
    i18n,
    render: h => h(App),
})
