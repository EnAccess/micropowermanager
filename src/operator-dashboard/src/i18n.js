import Vue from "vue"
import VueI18n from "vue-i18n"

import en from "@/assets/locales/en.json"

Vue.use(VueI18n)

// English only for now: the audience is the platform host's own operations team,
// and machine-translating operator jargon nobody reviews would be worse than one
// honest locale. Copy still goes through $tc so adding a locale is a file drop.
const i18n = new VueI18n({
  locale: "en",
  fallbackLocale: "en",
  messages: { en },
})

export default i18n
