<template>
  <div class="content-field">
    <div class="header">
      <h1 class="title">MicroPowerManager</h1>
      <h5 class="subtitle">{{ $tc("phrases.forgotPassword") }}</h5>
      <div class="title-divider">&nbsp;</div>
    </div>
    <div class="content">
      <form
        class="md-layout"
        @submit.prevent="sendForgotPassword"
        data-vv-scope="form-forgot"
      >
        <md-card class="md-layout-item">
          <md-card-header>
            <div class="">
              <div class="subtitle">
                {{ $tc("phrases.forgotPassword", 2) }}
              </div>
            </div>
          </md-card-header>
          <md-card-content>
            <md-field
              :class="{
                'md-invalid': errors.has('form-forgot.email'),
              }"
            >
              <label>{{ $tc("words.email") }}</label>
              <md-input
                type="email"
                name="email"
                id="email"
                autocomplete="email"
                v-model="email"
                :v-validate="'required|email'"
              />
              <span class="md-error">
                {{ errors.first("form-forgot.email") }}
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
import { UserPasswordService } from "@/services/UserPasswordService"

export default {
  name: "ForgotPassword",
  mixins: [notify],
  data: () => ({
    email: null,
    sending: false,
    userPasswordService: new UserPasswordService(),
  }),
  methods: {
    async sendForgotPassword() {
      let validation = await this.$validator.validateAll("form-forgot")
      if (!validation) {
        return
      }
      try {
        let response = await this.userPasswordService.forgotPassword(this.email)
        if (response.status_code === 200) {
          this.alertNotify("success", "New password has sended to your email.")
          setTimeout(() => {
            this.$router.push("/")
          }, 1500)
        } else {
          this.alertNotify("error", response.message.email)
        }
        this.sending = false
      } catch (error) {
        if (error.status_code === 404) {
          this.alertNotify("error", "Email address not recognized")
        } else {
          this.alertNotify("error", error)
        }
        this.sending = false
      }
    },
  },
}
</script>

<style lang="css"></style>
