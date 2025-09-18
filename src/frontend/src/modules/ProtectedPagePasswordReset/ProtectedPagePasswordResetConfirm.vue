<template>
  <div class="content-field">
    <div class="header">
      <h1 class="title">MicroPowerManager</h1>
      <h5 class="subtitle">{{ $tc("phrases.forgotProtectedPassword") }}</h5>
      <div class="title-divider">&nbsp;</div>
    </div>
    <div class="content">
      <form
        class="md-layout"
        @submit.prevent="resetPassword"
        data-vv-scope="form-confirm"
      >
        <md-card class="md-layout-item">
          <md-card-header>
            <div class="">
              <div class="subtitle">Set a new Protected Pages Password</div>
              <div v-if="userEmail" class="email-info">
                For: {{ userEmail }}
              </div>
            </div>
          </md-card-header>
          <md-card-content>
            <md-field
              :class="{
                'md-invalid': errors.has('form-confirm.password'),
              }"
            >
              <label>New Protected Pages Password</label>
              <md-input
                type="password"
                name="password"
                id="password"
                v-model="password"
                v-validate="'required|min:5'"
                ref="password"
              />
              <span class="md-error">
                {{ errors.first("form-confirm.password") }}
              </span>
            </md-field>

            <md-field
              :class="{
                'md-invalid': errors.has('form-confirm.password_confirmation'),
              }"
            >
              <label>Confirm New Password</label>
              <md-input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                v-model="passwordConfirmation"
                v-validate="'required|confirmed:password'"
              />
              <span class="md-error">
                {{ errors.first("form-confirm.password_confirmation") }}
              </span>
            </md-field>
          </md-card-content>

          <md-progress-bar md-mode="indeterminate" v-if="sending" />

          <md-card-actions>
            <md-button
              type="submit"
              class="md-primary btn-log"
              :disabled="sending || !isValid"
            >
              {{ $tc("words.send") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </form>
    </div>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify"
import { ProtectedPagePasswordResetService } from "@/services/ProtectedPagePasswordResetService"

export default {
  name: "ProtectedPagePasswordResetConfirm",
  mixins: [notify],
  data: () => ({
    password: null,
    passwordConfirmation: null,
    sending: false,
    userEmail: "",
    token: "",
    protectedPagePasswordResetService: new ProtectedPagePasswordResetService(),
  }),
  computed: {
    isValid() {
      return (
        this.password &&
        this.password.length >= 5 &&
        this.passwordConfirmation === this.password &&
        this.token
      )
    },
  },
  async created() {
    this.token = this.$route.query.token
    if (!this.token) {
      this.alertNotify("error", "Invalid reset link. Please request a new one.")
      this.$router.push("/forgot-protected-password")
      return
    }

    // Validate token and get user email
    try {
      const response =
        await this.protectedPagePasswordResetService.validateToken(this.token)
      if (response.valid) {
        this.userEmail = response.email
      } else {
        this.alertNotify(
          "error",
          "Invalid or expired reset link. Please request a new one.",
        )
        this.$router.push("/forgot-protected-password")
      }
    } catch (e) {
      this.alertNotify("error", e.message)
      this.$router.push("/forgot-protected-password")
    }
  },
  methods: {
    async resetPassword() {
      let validation = await this.$validator.validateAll("form-confirm")
      if (!validation) {
        return
      }

      this.sending = true

      try {
        let response =
          await this.protectedPagePasswordResetService.resetPassword(
            this.token,
            this.password,
            this.passwordConfirmation,
          )
        if (response.status_code === 200) {
          this.alertNotify(
            "success",
            "Protected Pages Password has been reset successfully.",
          )
          setTimeout(() => {
            this.$router.push("/")
          }, 1500)
        } else {
          this.alertNotify("error", response.message)
        }
        this.sending = false
      } catch (error) {
        this.alertNotify("error", error.message)
        this.sending = false
      }
    },
  },
}
</script>

<style lang="css"></style>
