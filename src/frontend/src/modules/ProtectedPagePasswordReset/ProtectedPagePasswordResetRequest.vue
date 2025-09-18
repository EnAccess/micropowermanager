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
        @submit.prevent="sendResetRequest"
        data-vv-scope="form-reset"
      >
        <md-card class="md-layout-item">
          <md-card-header>
            <div class="">
              <div class="subtitle">
                {{ $tc("phrases.forgotProtectedPassword", 2) }}
              </div>
            </div>
          </md-card-header>
          <md-card-content>
            <md-field
              :class="{
                'md-invalid': errors.has('form-reset.email'),
              }"
            >
              <label>{{ $tc("words.email") }}</label>
              <md-input
                type="email"
                name="email"
                id="email"
                autocomplete="email"
                v-model="email"
                v-validate="'required|email'"
              />
              <span class="md-error">
                {{ errors.first("form-reset.email") }}
              </span>
            </md-field>
          </md-card-content>

          <md-progress-bar md-mode="indeterminate" v-if="sending" />

          <md-card-actions>
            <md-button
              type="submit"
              class="md-primary btn-log"
              :disabled="sending"
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
  name: "ProtectedPagePasswordResetRequest",
  mixins: [notify],
  data: () => ({
    email: null,
    sending: false,
    protectedPagePasswordResetService: new ProtectedPagePasswordResetService(),
  }),
  methods: {
    async sendResetRequest() {
      let validation = await this.$validator.validateAll("form-reset")
      if (!validation) {
        return
      }

      this.sending = true

      try {
        let response =
          await this.protectedPagePasswordResetService.sendResetEmail(
            this.email,
          )
        if (response.status_code === 200) {
          this.alertNotify("success", "Reset link has been sent to your email.")
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
