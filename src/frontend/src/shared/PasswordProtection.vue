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
          showCancelButton: true,
          confirmButtonText: "Enter",
          cancelButtonText: "Cancel",
          footer:
            '<button type="button" id="forgot-ppp-btn" style="background: none; border: none; color: #4f4e94; text-decoration: none; cursor: pointer; font-size: inherit;">Forgot Protected Pages Password?</button>',

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
        }).then((result) => {
          if (result.dismiss === "cancel") {
            this.$router.replace("/")
          }
        })

        // Use Vue's $nextTick to ensure DOM is updated
        this.$nextTick(() => {
          const forgotBtn = document.getElementById("forgot-ppp-btn")
          if (forgotBtn) {
            forgotBtn.addEventListener("click", () => {
              this.$swal.close()
              this.$router.push("/forgot-protected-password")
            })
          }
        })
      }
    },
  },
}
</script>

<style scoped></style>
