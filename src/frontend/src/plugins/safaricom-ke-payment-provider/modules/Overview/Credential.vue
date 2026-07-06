<template>
  <div>
    <widget color="primary" title="Safaricom M-PESA Credentials">
      <form
        @submit.prevent="submitCredentialForm"
        data-vv-scope="Credential-Form"
      >
        <md-card>
          <md-card-content>
            <p>
              Enter the keys from your Daraja portal to start accepting M-PESA
              payments via STK Push.
            </p>
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.consumerKey'),
                  }"
                >
                  <label for="consumerKey">Consumer Key</label>
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
                  <span
                    v-if="credentialService.credential.consumerKeySet"
                    class="md-helper-text"
                  >
                    Configured — leave blank to keep the current key.
                  </span>
                  <span class="md-error">
                    {{ errors.first("Credential-Form.consumerKey") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.consumerSecret'),
                  }"
                >
                  <label for="consumerSecret">Consumer Secret</label>
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
                  <span
                    v-if="credentialService.credential.consumerSecretSet"
                    class="md-helper-text"
                  >
                    Configured — leave blank to keep the current secret.
                  </span>
                  <span class="md-error">
                    {{ errors.first("Credential-Form.consumerSecret") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.passkey'),
                  }"
                >
                  <label for="passkey">STK Push Passkey</label>
                  <md-input
                    id="passkey"
                    name="passkey"
                    v-model="credentialService.credential.passkey"
                    v-validate="passkeyRules"
                    type="password"
                    :placeholder="passkeyPlaceholder"
                  />
                  <span
                    v-if="credentialService.credential.passkeySet"
                    class="md-helper-text"
                  >
                    Configured — leave blank to keep the current passkey.
                  </span>
                  <span v-else-if="isSandbox" class="md-helper-text">
                    Optional in sandbox — Daraja's public test passkey is used
                    when blank.
                  </span>
                  <span class="md-error">
                    {{ errors.first("Credential-Form.passkey") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.shortcode'),
                  }"
                >
                  <label for="shortcode">Shortcode (Paybill / Till)</label>
                  <md-input
                    id="shortcode"
                    name="shortcode"
                    v-model="credentialService.credential.shortcode"
                    v-validate="shortcodeRules"
                    :placeholder="shortcodePlaceholder"
                  />
                  <span
                    v-if="isSandbox && !credentialService.credential.shortcode"
                    class="md-helper-text"
                  >
                    Optional in sandbox — Daraja's test shortcode 174379 is used
                    when blank.
                  </span>
                  <span class="md-error">
                    {{ errors.first("Credential-Form.shortcode") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="environment">Environment</label>
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

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="resultUrl">STK Push Result URL</label>
                  <md-input
                    id="resultUrl"
                    name="resultUrl"
                    v-model="credentialService.credential.resultUrl"
                    placeholder="Auto-derived if left blank"
                  />
                  <span class="md-helper-text">
                    Daraja will POST STK Push results here. Leave blank to use
                    the auto-generated webhook URL.
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="confirmationUrl">C2B Confirmation URL</label>
                  <md-input
                    id="confirmationUrl"
                    name="confirmationUrl"
                    v-model="credentialService.credential.confirmationUrl"
                    placeholder="Optional"
                  />
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="validationUrl">C2B Validation URL</label>
                  <md-input
                    id="validationUrl"
                    name="validationUrl"
                    v-model="credentialService.credential.validationUrl"
                    placeholder="Optional"
                  />
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="timeoutUrl">Queue Timeout URL</label>
                  <md-input
                    id="timeoutUrl"
                    name="timeoutUrl"
                    v-model="credentialService.credential.timeoutUrl"
                    placeholder="Optional"
                  />
                </md-field>
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
    </widget>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Credential",
  mixins: [notify],
  components: { Widget },
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
