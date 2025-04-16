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
      protectedPages: "protection/getProtectedPages",
      mainSettings: "settings/getMainSettings",
    }),
  },
  methods: {
    confirm(path) {
      if (this.protectedPages.includes(path)) {
        this.$swal({
          type: "question",
          allowOutsideClick: false,
          allowEscapeKey: false,
          title: this.$tc("phrases.passwordProtected"),
          text: this.$tc("phrases.passwordProtected", 2),
          inputType: "password",
          input: "password",
          inputPlaceholder: this.$tc("words.password"),

          inputValidator: async (value) => {
            try {
              const result =
                await this.protectedPageService.compareProtectedPagePassword(
                  this.mainSettings.id,
                  value,
                )
              if (!result) {
                this.$swal({
                  type: "error",
                  text: this.$tc("phrases.wrongPassword"),
                  timer: 1000,
                }).then(() => {
                  this.$router.replace("/")
                })
              }
            } catch (e) {
              console.error(e)
              this.$router.replace("/")
            }
          },
        })
      }
    },
  },
}
</script>

<style scoped></style>
