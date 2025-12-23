<template>
  <div class="login">
    <div>
      <div class="header">
        <h1 class="title">MicroPowerManager</h1>
        <h5 class="subtitle">{{ $tc("phrases.loginNotify", 1) }}</h5>
        <div class="title-divider">&nbsp;</div>
        <div class="description" v-if="authError">
          {{ $tc("phrases.loginNotify", 2) }}
        </div>
      </div>
      <div class="content">
        <form class="md-layout login-card" @submit.prevent="validateUser">
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
                  v-validate="'required|min:6|max:128'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.password")) }}
                </span>
              </md-field>
            </md-card-content>

            <md-progress-bar md-mode="indeterminate" v-if="sending" />

            <md-card-actions>
              <md-button class="md-default md-raised" to="/forgot-password">
                <md-icon>lock</md-icon>
                {{ $tc("phrases.forgotPassword") }}
              </md-button>
              <md-button
                type="submit"
                class="md-primary md-raised"
                :disabled="sending"
              >
                <md-icon>login</md-icon>
                {{ $tc("phrases.signIn") }}
              </md-button>
            </md-card-actions>
          </md-card>
        </form>
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

<style lang="scss" scoped>
.login {
  background: linear-gradient(
    to right,
    $brand-background-dark,
    $brand-background
  );
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;

  width: 100vw;
  min-height: 100vh;
}

.title {
  text-align: center;
  font-size: x-large;
  padding: 1rem 1rem 0 1rem;
  margin-bottom: 0;
  font-weight: bold;
}

.subtitle {
  text-align: center;
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

.text-browser {
  font-size: 14px;
  font-weight: 500;
  padding-top: 1%;
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  text-align: center;
}

.login-card {
  width: 490px;
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
</style>
