<template>
  <div class="content-field">
    <div class="header">
      <h1 class="title">MicroPowerManager</h1>
      <h5 class="subtitle">{{ $tc("phrases.loginNotify", 1) }}</h5>
      <div class="title-divider">&nbsp;</div>
      <div class="description" v-if="authError">
        {{ $tc("phrases.loginNotify", 2) }}
      </div>
    </div>
    <div class="content">
      <form class="md-layout" @submit.prevent="validateUser">
        <md-card class="md-layout-item">
          <md-card-header>
            <div class="md-title"></div>
          </md-card-header>

          <md-card-content>
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.email')),
              }"
            >
              <label for="email">{{ $tc("words.email") }}</label>
              <md-input
                type="email"
                :name="$tc('words.email')"
                id="email"
                autocomplete="email"
                v-model="form.email"
                :disabled="sending"
                v-validate="'required|email'"
              />
              <span class="md-error">
                {{ errors.first($tc("words.email")) }}
              </span>
            </md-field>

            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.password')),
              }"
            >
              <label for="password">
                {{ $tc("words.password") }}
              </label>
              <md-input
                type="password"
                :name="$tc('words.password')"
                id="password"
                v-model="form.password"
                :disabled="sending"
                v-validate="'required|min:6|max:15'"
              />
              <span class="md-error">
                {{ errors.first($tc("words.password")) }}
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
              {{ $tc("phrases.signIn") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </form>
      <div class="forgot-password">
        <router-link :to="{ path: '/forgot-password' }" class="link">
          <md-icon>lock</md-icon>
          {{ $tc("phrases.forgotPassword") }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { AuthenticationService } from "@/services/AuthenticationService"

import { config } from "@/config"

export default {
  name: "login-card",
  data: () => ({
    authError: false,
    form: {
      email:
        config.mpmEnv !== "production"
          ? "demo_company_admin@example.com"
          : null,
      password: config.mpmEnv !== "production" ? "123123" : null,
    },

    userSaved: false,
    sending: false,
    service: new AuthenticationService(),
  }),
  mounted() {
    this.$store.dispatch("auth/logOut")
    this.$store.commit("registrationTail/SET_IS_WIZARD_SHOWN", false)
  },
  methods: {
    async authenticate() {
      this.sending = true
      try {
        let email = this.form.email
        let password = this.form.password
        await this.$store.dispatch("auth/authenticate", {
          email,
          password,
        })
        await this.$store.dispatch("registrationTail/getRegistrationTail")
        await this.$store.dispatch("protection/getProtectedPages")
        await this.$store.dispatch("protection/getProtectedPagePassword")

        this.sending = false
        this.$router.push("/")
      } catch (e) {
        this.sending = false
        this.authError = true
      }
    },
    async validateUser() {
      let validator = await this.$validator.validateAll()

      if (validator) {
        await this.authenticate()
      }
    },
  },
}
</script>

<style lang="scss">
@media screen and (max-width: 575px) {
  #rc-imageselect,
  .g-recaptcha {
    transform: scale(0.77);
    -webkit-transform: scale(0.77);
    transform-origin: 0 0;
    -webkit-transform-origin: 0 0;
  }
}

.mat-form-field {
  display: block !important;
}

.content-field {
  display: flex;
  flex-direction: column;
  word-wrap: break-word;
  background-clip: border-box;

  margin: 10rem auto;
  position: relative;
  /*box-shadow: 0 1px 5px 0 rgba(0, 0, 0, 0.1);
          border-radius: 3px;*/
  min-height: 524px;
  padding: 3% !important;
  max-width: 490px;
  /*background-color: white;*/
}

.title {
  text-align: center !important;
  font-size: x-large !important;
  padding: 1rem 1rem 0 1rem;
  margin-bottom: 0 !important;
  font-weight: bold !important;
}

.subtitle {
  text-align: center !important;
  color: #8c8c8c;
  margin-top: 5px;
  margin-bottom: 0;
}

.title-divider {
  border-bottom: solid 2px #f9b839;
  line-height: 2px;
  margin: 0.5rem 0 2rem 0;
}

.description {
  text-align: center;
  background-color: #ac2925;
  padding: 15px;
  color: white;
}

.lock-div {
  height: 28px;
  display: inline-block;
  border: 1px solid #8a8a8a;
  font-size: 12px;
  font-weight: 400;
  line-height: 26px;
  padding: 0 12px;
  margin: 3px 0 0 0;
  color: #2a2a2a;
  user-select: none;
}

.http {
  color: green;
}

.lock-icon {
  font-size: 14px;

  width: 20px;
  height: 16px !important;
  float: left;
  background-position: left center;
  background-size: 9px;
  border-right: 1px solid #8a8a8a;
  margin: 5px 8px 0 0;
  color: green;
}

.text-browser {
  font-size: 14px;
  font-weight: 500;
  padding-top: 1%;
}

.btn-log {
  background-color: #689f38 !important;

  color: white !important;
  width: 100%;
}

.btn-log2 {
  box-shadow: 0px 0px 0px 0px rgba(0, 0, 0, 0.2) !important;

  background-color: white !important;

  color: #424242 !important;
}

.row-a1 {
  margin: auto;
  padding-top: 2%;
  margin: auto;
  padding-top: 2%;
  margin-left: 3.2rem;
}

.content-bottom {
  margin-top: 2rem !important;
  display: flow-root;
  flex-direction: column;
  word-wrap: break-word;

  margin: 0 auto;
  position: relative;
  border-radius: 3px;

  max-width: 490px;
}

.md-checkbox {
  display: flex;
}

.md-progress-bar {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
}

.forgot-password {
  float: right;
  margin-top: 5px;
  color: #8c8c8c;

  a {
    color: #8c8c8c !important;
  }
}
</style>
