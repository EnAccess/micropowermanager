<template>
  <div></div>
</template>

<script>
import { mapGetters } from "vuex"
import { EventBus } from "@/shared/eventbus"
import { ProtectedPageService } from "@/services/ProtectedPageService"

export default {
  name: "PasswordProtection",

  data() {
    return {
      protectedPageService: new ProtectedPageService(),
    }
  },
  created() {
    this.confirm(this.$route.path)
  },

  mounted() {
    EventBus.$on("checkPageProtection", (to) => {
      console.log("route changed")
      this.confirm(to.path)
    })
  },

  beforeDestroy() {
    EventBus.$off("checkPageProtection")
  },

  computed: {
    ...mapGetters({
      password: "protection/getPassword",
      protectedPages: "protection/getProtectedPages",
    }),
  },
  methods: {
    confirm(path) {
      if (this.protectedPages.includes(path)) {
        if (this.password === "" || this.password === null) {
          this.$swal
            .fire(
              "Password is not set",
              "Please contact your administrator to set the password",
              "warning",
            )
            .then(() => {
              this.$router.replace("/")
            })
        } else {
          this.$swal({
            type: "question",
            allowOutsideClick: false,
            allowEscapeKey: false,
            title: this.$tc("phrases.passwordProtected"),
            text: this.$tc("phrases.passwordProtected", 2),
            inputType: "password",
            input: "password",
            inputPlaceholder: this.$tc("words.password"),
            inputValidator: (value) => {
              if (value !== this.password) {
                this.$swal({
                  type: "error",
                  text: this.$tc("phrases.wrongPassword"),
                  timer: 1000,
                }).then(() => {
                  this.$router.replace("/")
                })
              }
            },
          })
        }
      }
    },
  },
}
</script>

<style scoped></style>
