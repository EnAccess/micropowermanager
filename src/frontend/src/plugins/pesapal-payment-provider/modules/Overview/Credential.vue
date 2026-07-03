<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
    >
      <md-card>
        <md-card-header>
          <div class="md-title">PesaPal API Credentials</div>
          <div class="md-subhead">
            Enter the keys from your PesaPal merchant dashboard to start
            accepting payments.
          </div>
        </md-card-header>
        <md-card-content>
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
                      : 'Paste your PesaPal consumer key'
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
                      : 'Paste your PesaPal consumer secret'
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
                  'md-invalid': errors.has('Credential-Form.merchantName'),
                }"
              >
                <label for="merchantName">
                  {{ $tc("phrases.merchantName") }}
                </label>
                <md-input
                  id="merchantName"
                  name="merchantName"
                  v-model="credentialService.credential.merchantName"
                  v-validate="'required|min:2'"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.merchantName") }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.merchantEmail'),
                }"
              >
                <label for="merchantEmail">
                  {{ $tc("phrases.merchantEmail") }}
                </label>
                <md-input
                  id="merchantEmail"
                  name="merchantEmail"
                  v-model="credentialService.credential.merchantEmail"
                  v-validate="'required|email'"
                  type="email"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.merchantEmail") }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label for="currency">Currency</label>
                <md-select
                  id="currency"
                  name="currency"
                  v-model="credentialService.credential.currency"
                >
                  <md-option
                    v-for="currency in supportedCurrencies"
                    :key="currency"
                    :value="currency"
                  >
                    {{ currency }}
                  </md-option>
                </md-select>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label for="environment">
                  {{ $tc("phrases.environment") }}
                </label>
                <md-select
                  id="environment"
                  name="environment"
                  v-model="credentialService.credential.environment"
                >
                  <md-option value="test">{{ $tc("phrases.test") }}</md-option>
                  <md-option value="live">{{ $tc("phrases.live") }}</md-option>
                </md-select>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.callbackUrl'),
                }"
              >
                <label for="callbackUrl">
                  {{ $tc("phrases.callbackUrl") }}
                </label>
                <md-input
                  id="callbackUrl"
                  name="callbackUrl"
                  v-model="credentialService.credential.callbackUrl"
                  v-validate="'required'"
                  readonly
                />
                <span class="md-helper-text">
                  Generated automatically — no action needed.
                </span>
                <span class="md-error">
                  {{ errors.first("Credential-Form.callbackUrl") }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label for="ipnId">IPN ID</label>
                <md-input
                  id="ipnId"
                  name="ipnId"
                  :value="credentialService.credential.ipnId || ''"
                  readonly
                />
                <span class="md-helper-text">
                  Registered with PesaPal automatically when credentials are
                  saved.
                </span>
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

    <md-card>
      <md-card-header>
        <div class="md-title">Public Payment Link</div>
        <div class="md-subhead">
          Share this link with your customers so they can pay directly.
        </div>
      </md-card-header>
      <md-card-content>
        <md-field>
          <label>Permanent Payment URL (Self-Service)</label>
          <md-input :value="publicUrls.permanent_payment_url" readonly />
          <span class="md-helper-text">
            This URL never expires. Customers can bookmark it for regular
            payments.
          </span>
        </md-field>
      </md-card-content>
      <md-card-actions md-alignment="left">
        <md-button
          class="md-primary"
          @click="copyToClipboard(publicUrls.permanent_payment_url)"
          :disabled="!publicUrls.permanent_payment_url"
        >
          <md-icon>content_copy</md-icon>
          Copy
        </md-button>
        <md-button
          class="md-primary"
          @click="openPaymentPage"
          :disabled="!publicUrls.permanent_payment_url"
        >
          <md-icon>open_in_new</md-icon>
          Open test payment page
        </md-button>
      </md-card-actions>
    </md-card>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

const DEFAULT_SUPPORTED_CURRENCIES = ["KES", "UGX", "TZS", "USD"]

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
      supportedCurrencies: DEFAULT_SUPPORTED_CURRENCIES,
      publicUrls: {
        permanent_payment_url: "",
      },
    }
  },
  computed: {
    consumerKeyRules() {
      // Required only on first save — when keys are already stored, blank
      // means "keep current" and a typed value still has to be ≥3 chars.
      return this.credentialService.credential.consumerKeySet
        ? "min:3"
        : "required|min:3"
    },
    consumerSecretRules() {
      return this.credentialService.credential.consumerSecretSet
        ? "min:3"
        : "required|min:3"
    },
  },
  async mounted() {
    await this.getCredential()
    await this.generateUrls()
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
        EventBus.$emit("PesapalPaymentProvider")
      } catch (error) {
        this.alertNotify(
          "error",
          error.message || "Failed to update credential",
        )
      } finally {
        this.loading = false
      }
    },
    async generateUrls() {
      try {
        const response = await this.credentialService.getPublicUrls()
        this.publicUrls.permanent_payment_url = this.addFrontendPrefix(
          response.permanent_payment_url,
        )
        // Callback URL is the result page that PesaPal returns the customer to.
        this.credentialService.credential.callbackUrl = this.addFrontendPrefix(
          response.permanent_payment_url.replace("/payment/", "/result/"),
        )
      } catch (error) {
        console.error("Error generating public URLs:", error)
        this.alertNotify("error", "Failed to generate public URLs")
      }
    },
    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text)
        this.alertNotify("success", "URL copied to clipboard")
      } catch (error) {
        const textArea = document.createElement("textarea")
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand("copy")
        document.body.removeChild(textArea)
        this.alertNotify("success", "URL copied to clipboard")
      }
    },
    openPaymentPage() {
      if (this.publicUrls.permanent_payment_url) {
        window.open(this.publicUrls.permanent_payment_url, "_blank")
      }
    },
    addFrontendPrefix(url_path) {
      if (!url_path) return url_path
      const origin = window.location.origin
      return `${origin}/#${url_path}`
    },
  },
}
</script>

<style scoped lang="scss">
form + .md-card {
  margin-top: 16px;
}
</style>
