import { EventBus } from "@/shared/eventbus"

export const notify = {
  methods: {
    alertNotify(type, message) {
      EventBus.$emit("show-snackbar", { type, message })
    },
  },
}
