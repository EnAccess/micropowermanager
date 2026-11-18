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
            Sell electricity tokens for your Calin prepaid meters. When a
            customer pays, a kWh token is created automatically. You'll need the
            User ID and API key from Calin.
          </div>
        </md-card-header>
        <md-card-content>
          <md-field
            :class="{
              'md-invalid': errors.has('Credential-Form.userId'),
            }"
          >
            <label for="userId">User ID</label>
            <md-input
              id="userId"
              name="userId"
              v-model="credentialService.credential.userId"
              v-validate="'required'"
            />
            <md-icon class="field-help">
              help_outline
              <md-tooltip md-direction="left">
                The user ID Calin issued for the token API (user_id).
              </md-tooltip>
            </md-icon>
            <span class="md-error">
              {{ errors.first("Credential-Form.userId") }}
            </span>
          </md-field>
          <md-field
            :class="{
              'md-invalid': errors.has('Credential-Form.apiKey'),
            }"
          >
            <label for="apiKey">API key</label>
            <md-input
              id="apiKey"
              name="apiKey"
              type="password"
              v-model="credentialService.credential.apiKey"
              v-validate="'required'"
            />
            <md-icon class="field-help">
              help_outline
              <md-tooltip md-direction="left">
                The API key Calin issued together with your user ID. Calin sends
                it as the password of token requests.
              </md-tooltip>
            </md-icon>
            <span class="md-error">
              {{ errors.first("Credential-Form.apiKey") }}
            </span>
          </md-field>
          <div class="advanced-toggle" @click="showAdvanced = !showAdvanced">
            <md-icon>
              {{
                showAdvanced ? "keyboard_arrow_down" : "keyboard_arrow_right"
              }}
            </md-icon>
            <span>Advanced options</span>
          </div>
          <md-field
            v-if="showAdvanced"
            :class="{
              'md-invalid': errors.has('Credential-Form.apiUrl'),
            }"
          >
            <label for="apiUrl">API URL</label>
            <md-input
              id="apiUrl"
              name="apiUrl"
              v-model="credentialService.credential.apiUrl"
              v-validate="'required|url'"
              placeholder="http://api.calinhost.com/api"
            />
            <md-icon class="field-help">
              help_outline
              <md-tooltip md-direction="left">
                The token API address Calin gave you, without /tokennew. Most
                accounts use http://api.calinhost.com/api.
              </md-tooltip>
            </md-icon>
            <span class="md-error">
              {{ errors.first("Credential-Form.apiUrl") }}
            </span>
          </md-field>
          <div
            v-if="checkStatus !== 'idle'"
            :class="['check-status', checkStatus]"
          >
            <span v-if="checkStatus === 'loading'">Checking connection…</span>
            <span v-if="checkStatus === 'success'">
              Calin accepted these credentials.
            </span>
            <span v-if="checkStatus === 'error'">{{ checkStatusMessage }}</span>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button
            class="md-raised md-accent"
            type="button"
            :disabled="loading"
            @click="checkConnection"
          >
            Check connection
          </md-button>
          <md-button
            class="md-raised md-primary"
            type="submit"
            :disabled="loading"
          >
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
      loading: false,
      showAdvanced: false,
      checkStatus: "idle", // idle | loading | success | error
      checkStatusMessage: "",
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
        this.alertNotify("success", "Credentials saved")
        EventBus.$emit("CalinMeter")
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
    async checkConnection() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        this.checkStatus = "loading"
        await this.credentialService.checkCredential()
        this.checkStatus = "success"
      } catch (e) {
        this.checkStatus = "error"
        this.checkStatusMessage = e.message
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.md-field.md-has-password ::v-deep .md-toggle-password {
  right: 32px;
}

.advanced-toggle {
  display: flex;
  align-items: center;
  min-height: 48px;
  padding-top: 16px;
  margin: 4px 0 24px;
  font-size: 16px;
  cursor: pointer;
  user-select: none;

  .md-icon {
    margin: 0;
  }
}

.check-status {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  border-left: 4px solid #9e9e9e;
  background-color: #f8f9fa;

  &.success {
    border-left-color: #4caf50;
  }

  &.error {
    border-left-color: #f44336;
  }
}
</style>
