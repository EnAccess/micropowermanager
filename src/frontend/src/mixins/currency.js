import store from "../store/store"
import { readable } from "./numbers.js"

export const currency = {
  methods: {
    readable,
    moneyFormat(amount) {
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: store.getters["settings/getMainSettings"].currency,
        minimumFractionDigits: 2,
      }).format(amount)
    },
  },
}
