<template>
  <div class="safaricom-settings">
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
    >
      <md-card class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Safaricom M-PESA Credentials</h2>
          <p class="panel__subtitle">
            Enter the keys from your Daraja portal to start accepting M-PESA
            payments via STK Push.
          </p>
        </div>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="consumerKey">
                  Consumer Key
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.consumerKey'),
                  }"
                >
                  <md-input
                    id="consumerKey"
                    name="consumerKey"
                    v-model="credentialService.credential.consumerKey"
                    v-validate="consumerKeyRules"
                    type="password"
                    :placeholder="
                      credentialService.credential.consumerKeySet
                        ? '••••••••  (leave blank to keep current)'
                        : 'Paste your Daraja consumer key'
                    "
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.consumerKey") }}
                  </span>
                </md-field>
                <p
                  v-if="credentialService.credential.consumerKeySet"
                  class="field__note field__note--ok"
                >
                  <md-icon>check_circle</md-icon>
                  Configured — leave blank to keep the current key.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="consumerSecret">
                  Consumer Secret
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.consumerSecret'),
                  }"
                >
                  <md-input
                    id="consumerSecret"
                    name="consumerSecret"
                    v-model="credentialService.credential.consumerSecret"
                    v-validate="consumerSecretRules"
                    type="password"
                    :placeholder="
                      credentialService.credential.consumerSecretSet
                        ? '••••••••  (leave blank to keep current)'
                        : 'Paste your Daraja consumer secret'
                    "
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.consumerSecret") }}
                  </span>
                </md-field>
                <p
                  v-if="credentialService.credential.consumerSecretSet"
                  class="field__note field__note--ok"
                >
                  <md-icon>check_circle</md-icon>
                  Configured — leave blank to keep the current secret.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="passkey">
                  STK Push Passkey
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.passkey'),
                  }"
                >
                  <md-input
                    id="passkey"
                    name="passkey"
                    v-model="credentialService.credential.passkey"
                    v-validate="passkeyRules"
                    type="password"
                    :placeholder="passkeyPlaceholder"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.passkey") }}
                  </span>
                </md-field>
                <p
                  v-if="credentialService.credential.passkeySet"
                  class="field__note field__note--ok"
                >
                  <md-icon>check_circle</md-icon>
                  Configured — leave blank to keep the current passkey.
                </p>
                <p
                  v-else-if="isSandbox"
                  class="field__note field__note--info"
                >
                  <md-icon>info</md-icon>
                  Optional in sandbox — Daraja's public test passkey is used
                  when blank.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="shortcode">
                  Shortcode (Paybill / Till)
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.shortcode'),
                  }"
                >
                  <md-input
                    id="shortcode"
                    name="shortcode"
                    v-model="credentialService.credential.shortcode"
                    v-validate="shortcodeRules"
                    :placeholder="shortcodePlaceholder"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.shortcode") }}
                  </span>
                </md-field>
                <p
                  v-if="isSandbox && !credentialService.credential.shortcode"
                  class="field__note field__note--info"
                >
                  <md-icon>info</md-icon>
                  Optional in sandbox — Daraja's test shortcode 174379 is
                  used when blank.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="environment">Environment</label>
                <md-field>
                  <md-select
                    id="environment"
                    name="environment"
                    v-model="credentialService.credential.environment"
                  >
                    <md-option value="sandbox">Sandbox</md-option>
                    <md-option value="production">Production</md-option>
                  </md-select>
                </md-field>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="resultUrl">
                  STK Push Result URL
                </label>
                <md-field>
                  <md-input
                    id="resultUrl"
                    name="resultUrl"
                    v-model="credentialService.credential.resultUrl"
                    placeholder="Auto-derived if left blank"
                  />
                </md-field>
                <p class="field__note">
                  Daraja will POST STK Push results here. Leave blank to use
                  the auto-generated webhook URL.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="confirmationUrl">
                  C2B Confirmation URL
                </label>
                <md-field>
                  <md-input
                    id="confirmationUrl"
                    name="confirmationUrl"
                    v-model="credentialService.credential.confirmationUrl"
                    placeholder="Optional"
                  />
                </md-field>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="validationUrl">
                  C2B Validation URL
                </label>
                <md-field>
                  <md-input
                    id="validationUrl"
                    name="validationUrl"
                    v-model="credentialService.credential.validationUrl"
                    placeholder="Optional"
                  />
                </md-field>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="timeoutUrl">
                  Queue Timeout URL
                </label>
                <md-field>
                  <md-input
                    id="timeoutUrl"
                    name="timeoutUrl"
                    v-model="credentialService.credential.timeoutUrl"
                    placeholder="Optional"
                  />
                </md-field>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <div class="panel__actions">
          <md-button class="md-raised md-primary" type="submit">
            {{ $tc("words.save") }}
          </md-button>
        </div>
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
    }
  },
  computed: {
    isSandbox() {
      return this.credentialService.credential.environment === "sandbox"
    },
    consumerKeyRules() {
      return this.credentialService.credential.consumerKeySet
        ? "min:3"
        : "required|min:3"
    },
    consumerSecretRules() {
      return this.credentialService.credential.consumerSecretSet
        ? "min:3"
        : "required|min:3"
    },
    passkeyRules() {
      if (this.credentialService.credential.passkeySet) {
        return "min:3"
      }
      // In sandbox the backend falls back to Daraja's test passkey, so this
      // field is optional. Production requires it.
      return this.isSandbox ? "min:3" : "required|min:3"
    },
    shortcodeRules() {
      return this.isSandbox ? "min:3" : "required|min:3"
    },
    passkeyPlaceholder() {
      if (this.credentialService.credential.passkeySet) {
        return "••••••••  (leave blank to keep current)"
      }
      return this.isSandbox
        ? "Optional in sandbox — uses Daraja test passkey when blank"
        : "LNM passkey from Daraja"
    },
    shortcodePlaceholder() {
      return this.isSandbox ? "e.g. 174379 (test default)" : "e.g. 600999"
    },
  },
  async mounted() {
    await this.getCredential()
  },
  methods: {
    async getCredential() {
      try {
        await this.credentialService.getCredential()
      } catch (error) {
        this.alertNotify("error", "Failed to get credential")
      }
    },
    async submitCredentialForm() {
      const validation = await this.$validator.validateAll("Credential-Form")
      if (!validation) {
        return
      }

      this.loading = true
      try {
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Credential updated successfully")
        EventBus.$emit("credential-updated")
      } catch (error) {
        this.alertNotify(
          "error",
          error.message || "Failed to update credential",
        )
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped lang="scss">
.safaricom-settings {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.panel {
  border-radius: 10px;
}

.panel__head {
  padding: 1.25rem 1.5rem 0.25rem;
}

.panel__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: $brand-primary-dark;
}

.panel__subtitle {
  margin: 0.3rem 0 0;
  font-size: 0.825rem;
  color: #8a93a0;
}

.panel__actions {
  display: flex;
  justify-content: flex-end;
  padding: 0.5rem 1.5rem 1.25rem;
}

.field {
  margin-bottom: 1.1rem;
}

.field__label {
  display: block;
  margin-bottom: 0.1rem;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #8a93a0;
}

.field .md-field {
  margin: 0;
  min-height: 40px;
  padding-top: 4px;
}

.field__note {
  margin: 0.35rem 0 0;
  font-size: 11.5px;
  font-style: italic;
  color: #9aa3af;
}

.field__note--ok {
  color: $brand-accent-dark;
  font-style: normal;
  display: flex;
  align-items: center;
  gap: 4px;

  .md-icon {
    color: $brand-accent-dark !important;
    font-size: 16px !important;
    width: 16px;
    min-width: 16px;
    height: 16px;
  }
}

.field__note--info {
  color: $brand-primary;
  font-style: normal;
  display: flex;
  align-items: center;
  gap: 4px;

  .md-icon {
    color: $brand-primary !important;
    font-size: 16px !important;
    width: 16px;
    min-width: 16px;
    height: 16px;
  }
}
</style>
