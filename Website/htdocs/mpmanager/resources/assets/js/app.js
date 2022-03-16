/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap')
import router from './routes'
import Appp from '../js/Appp'
import 'leaflet/dist/leaflet.css'
import store from './store/store'
import UserData from './shared/UserData'
import Default from './layouts/Default'
import i18n from './i18n'
import { MapSettingsService } from './services/MapSettingsService'
import { TicketSettingsService } from './services/TicketSettingsService'
import { MainSettingsService } from './services/MainSettingsService'

Vue.component('default', Default)

router.beforeEach((to, from, next) => {
    const authToken = store.getters['auth/getToken']
    const intervalId = store.getters['auth/getIntervalId']
    if (['login', 'forgot_password'].includes(to.name)) {
        return next()
    }
    if (authToken === undefined || authToken === '') {
        return next({ name: 'login' })
    }
    store.dispatch('auth/refreshToken', authToken, intervalId).then((result) => {
        return result ? next() : next({ name: 'login' })
    }).catch(() => {
        return next({ name: 'login' })
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
    render: h => h(Appp),
})
