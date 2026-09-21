import { EventBus } from "@/shared/eventbus.js"

export const tailWizard = {
  methods: {
    // Only closes the dialog - the registration-tail step must stay unadjusted
    // until credentials are actually saved on the overview page.
    closeTailWizardAndGoTo(route) {
      EventBus.$emit("tail-wizard.close")
      this.$router.push(route).catch((error) => {
        if (error.name !== "NavigationDuplicated") {
          throw error
        }
      })
    },
  },
}
