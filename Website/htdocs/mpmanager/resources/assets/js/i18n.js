import en from '../locales/en.json'
import fr from '../locales/fr.json'
import VueI18n from 'vue-i18n'
import Vue from 'vue'
import enMessages from 'vee-validate/dist/locale/en'
import frMessages from 'vee-validate/dist/locale/fr'
import VeeValidate from 'vee-validate'

Vue.use(VueI18n)

export default new VueI18n({
    locale: localStorage.getItem('lang') || 'en',
    messages:{
        en: en,
        fr: fr
    }
})

const i18n = new VueI18n()
i18n.locale = 'en' // set a default locale (without it, it won't work)

Vue.use(VeeValidate, {
    i18n,
    dictionary: {
        en: {
            messages: enMessages.messages
        },
        fr: {messages: frMessages.messages}

    }
})
