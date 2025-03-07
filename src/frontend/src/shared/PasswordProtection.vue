<template>
  <div>
    <confirmation-box
      ref="passwordDialog"
      title="Password is not set"
      message="Please contact your administrator to set the password"
      :confirm-text="$tc('words.ok')"
    />

    <confirmation-box
      ref="passwordInputDialog"
      :title="$tc('phrases.passwordProtected')"
      :message="$tc('phrases.passwordProtected', 2)"
      :confirm-text="$tc('words.confirm')"
      :cancel-text="$tc('words.cancel')"
      input-type="password"
    />

    <confirmation-box
      ref="passwordErrorDialog"
      title="Wrong Password"
      message="The password you entered is incorrect."
      :confirm-text="$tc('words.ok')"
    />
  </div>
</template>

<script>
import { mapGetters } from "vuex"
import { EventBus } from "@/shared/eventbus"
import ConfirmationBox from "@/shared/ConfirmationBox.vue"

export default {
  name: "PasswordProtection",
  components: { ConfirmationBox },

  mounted() {
    EventBus.$on("checkPageProtection", (to) => {
      console.log("route changed")
      this.confirm(to.path)
    })
  },
  computed: {
    ...mapGetters({
      password: "protection/getPassword",
      protectedPages: "protection/getProtectedPages",
    }),
  },
  methods: {
    async confirm(path) {
      if (this.protectedPages.includes(path)) {
        if (!this.password) {
          const result = await this.$refs.passwordDialog.show()
          console.log(result)
          if (result.confirmed) {
            this.$router.replace("/")
          }
        } else {
          const result = await this.$refs.passwordInputDialog.show()
          console.log(result)

          if (result.confirmed && result.inputValue === this.password) {
            console.log("Access granted")
          } else {
            await this.$refs.passwordErrorDialog.show()
            this.$router.replace("/")
          }
        }
      }
    },
  },
}
</script>

<style scoped></style>
