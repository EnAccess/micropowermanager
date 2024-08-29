import en from "../src/assets/locales/en.json"
import fr from "../src/assets/locales/fr.json"
import bu from "../src/assets/locales/bu.json"
import VueI18n from "vue-i18n"
import Vue from "vue"

Vue.use(VueI18n)

const i18n = new VueI18n({
  locale: localStorage.getItem("lang") || "en",
  messages: {
    en: en,
    fr: fr,
    bu: bu,
  },
})

export default i18n
