window._ = require('lodash')
window.Popper = require('popper.js').default
import 'babel-polyfill'

window.axios = require('axios')
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
// Add a request interceptor
window.axios.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers['Authorization'] = 'Bearer ' + token
        }
        // config.headers['Content-Type'] = 'application/json';
        return config
    },
    error => {
        Promise.reject(error)
    }
)

import {config} from './config'

Vue.prototype.appConfig = config


import Vue from 'vue'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import moment from 'moment'
import Notifications from 'vue-notification'

Vue.use(Vuex)
window.Vue = Vue
window.Vuex = Vuex

import VueGoogleCharts from 'vue-google-charts'

Vue.use(VueGoogleCharts)

window.moment = moment
Vue.use(VueRouter)

Vue.use(Notifications)

// import component and stylesheet
import AirbnbStyleDatepicker from 'vue-airbnb-style-datepicker'
import 'vue-airbnb-style-datepicker/dist/vue-airbnb-style-datepicker.min.css'

// see docs for available options
const datepickerOptions = {}

// make sure we can use it in our components
Vue.use(AirbnbStyleDatepicker, datepickerOptions)

import {resources} from './resources'

window.resources = resources

import Echo from 'laravel-echo'

/**
 * Pusher
 */
window.Pusher = require('pusher-js')
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '56e4b7fede4ce42e1b84',
    cluster: 'eu',
    encrypted: true
})

/**
 * Sweet Alert
 */
import VueSweetalert2 from 'vue-sweetalert2'

Vue.use(VueSweetalert2)

window.audio = new Audio('../notification/to-the-point.mp3')
window.onclick = function (e) {

    let target = e.target
    if (target.localName === 'a' || target.localName === 'i') {
        let className = target.getAttribute('class')
        let validClassNames = ['fa fa-compress', 'fa fa-expand', 'button-icon jarviswidget-fullscreen-btn']
        if (validClassNames.indexOf(className) > -1) {
            window.dispatchEvent(new Event('resize'))
        }
    }
}

import * as VueGoogleMaps from 'vue2-google-maps'

Vue.use(VueGoogleMaps, {
    load: {
        key: 'AIzaSyCiSUjcyWMpV8dAMjIQ-VUaLZZ9NEFIELo',
    },
})
import VueHtml2Canvas from 'vue-html2canvas'

Vue.use(VueHtml2Canvas)

import VueMaterial from 'vue-material'
import 'vue-material/dist/vue-material.min.css'
import 'vue-material/dist/theme/default.css' // This line here
Vue.use(VueMaterial)

import SidebarComponent from './components/Sidebar'

Vue.use(SidebarComponent)
import '../sass/mpm.scss'


import VeeValidate from 'vee-validate'

Vue.use(VeeValidate)


import Default from './layouts/Default.vue'

Vue.component('default-layout', Default)

