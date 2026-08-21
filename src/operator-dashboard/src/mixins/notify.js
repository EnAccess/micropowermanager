import { EventBus } from "@/shared/eventbus.js"

export const notify = {
  methods: {
    alertNotify(type, message) {
      EventBus.$emit("show-snackbar", { type, message })
    },
  },
}
