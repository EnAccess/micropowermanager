<template>
  <div>
    <sign-in v-if="!isAuthenticated" />
    <operator-shell v-else>
      <router-view />
    </operator-shell>
    <snackbar />
  </div>
</template>

<script>
import OperatorShell from "@/layouts/OperatorShell.vue"
import { EventBus } from "@/shared/eventbus.js"
import Snackbar from "@/shared/Snackbar.vue"
import SignIn from "@/views/SignIn.vue"

export default {
  name: "App",
  components: { OperatorShell, SignIn, Snackbar },
  computed: {
    isAuthenticated() {
      return this.$store.getters["session/isAuthenticated"]
    },
  },
  mounted() {
    EventBus.$on("operator.unauthenticated", this.signOut)
  },
  beforeDestroy() {
    EventBus.$off("operator.unauthenticated", this.signOut)
  },
  methods: {
    signOut() {
      this.$store.dispatch("session/signOut")
    },
  },
}
</script>

<style lang="scss" scoped></style>
