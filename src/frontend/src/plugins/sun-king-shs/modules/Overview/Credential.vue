<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-header>
          <div class="md-subhead">
            Configure your SunKing SHS API credentials to manage devices through
            MicroPowerManager.
            <a
              href="https://sunking.com/"
              target="_blank"
              rel="noopener noreferrer"
            >
              Learn more about SunKing SHS
            </a>
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.clientId'),
                }"
              >
                <label for="clientId">SunKing Client ID</label>
                <md-input
                  id="clientId"
                  name="clientId"
                  v-model="credentialService.credential.clientId"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.clientId") }}
                </span>
              </md-field>
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.clientSecret'),
                }"
              >
                <label for="clientSecret">SunKing Client Secret</label>
                <md-input
                  id="clientSecret"
                  type="password"
                  name="clientSecret"
                  v-model="credentialService.credential.clientSecret"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.clientSecret") }}
                </span>
              </md-field>
              <div
                class="advanced-toggle"
                @click="showAdvanced = !showAdvanced"
              >
                <md-icon
                  class="toggle-icon"
                  :class="{ expanded: showAdvanced }"
                >
                  {{
                    showAdvanced
                      ? "keyboard_arrow_down"
                      : "keyboard_arrow_right"
                  }}
                </md-icon>

                <span>Advanced Options</span>
              </div>

              <!-- Advanced Fields (Collapsible) -->
              <transition name="expand">
                <div v-if="showAdvanced" class="advanced-section">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.authUrl'),
                    }"
                  >
                    <label for="authUrl">SunKing SHS Auth URL</label>
                    <md-input
                      id="authUrl"
                      name="authUrl"
                      v-model="credentialService.credential.authUrl"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.authUrl") }}
                    </span>
                  </md-field>

                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.apiUrl'),
                    }"
                  >
                    <label for="apiUrl">SunKing SHS API URL</label>
                    <md-input
                      id="apiUrl"
                      name="apiUrl"
                      v-model="credentialService.credential.apiUrl"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.apiUrl") }}
                    </span>
                  </md-field>
                </div>
              </transition>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-raised md-primary" type="submit">
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      showAdvanced: false,
      loading: false,
    }
  },
  mounted() {
    this.getCredential()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()
    },
    async submitCredentialForm() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Authentication Successful")
        EventBus.$emit("SunKing SHS")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
@media (max-width: 960px) {
  .my-layout {
    flex-direction: column-reverse;
  }
}
</style>
