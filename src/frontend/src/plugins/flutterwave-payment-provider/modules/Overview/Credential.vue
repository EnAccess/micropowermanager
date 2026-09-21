<template>
  <div>
    <div class="widget-line">
      <widget color="primary" title="Flutterwave API Credentials">
        <form
          @submit.prevent="submitCredentialForm"
          data-vv-scope="Credential-Form"
        >
          <md-card>
            <md-card-content>
              <p>
                Enter the keys from your Flutterwave dashboard to start
                accepting payments.
              </p>
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.publicKey'),
                    }"
                  >
                    <label for="publicKey">Public Key</label>
                    <md-input
                      id="publicKey"
                      name="publicKey"
                      v-model="credentialService.credential.publicKey"
                      v-validate="publicKeyRules"
                      type="password"
                      :placeholder="
                        credentialService.credential.publicKeySet
                          ? '••••••••  (leave blank to keep current)'
                          : 'Paste your Flutterwave public key'
                      "
                    />
                    <span
                      v-if="credentialService.credential.publicKeySet"
                      class="md-helper-text"
                    >
                      Configured — leave blank to keep the current key.
                    </span>
                    <span class="md-error">
                      {{ errors.first("Credential-Form.publicKey") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.secretKey'),
                    }"
                  >
                    <label for="secretKey">Secret Key</label>
                    <md-input
                      id="secretKey"
                      name="secretKey"
                      v-model="credentialService.credential.secretKey"
                      v-validate="secretKeyRules"
                      type="password"
                      :placeholder="
                        credentialService.credential.secretKeySet
                          ? '••••••••  (leave blank to keep current)'
                          : 'Paste your Flutterwave secret key'
                      "
                    />
                    <span
                      v-if="credentialService.credential.secretKeySet"
                      class="md-helper-text"
                    >
                      Configured — leave blank to keep the current key.
                    </span>
                    <span class="md-error">
                      {{ errors.first("Credential-Form.secretKey") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.encryptionKey'),
                    }"
                  >
                    <label for="encryptionKey">Encryption Key</label>
                    <md-input
                      id="encryptionKey"
                      name="encryptionKey"
                      v-model="credentialService.credential.encryptionKey"
                      v-validate="encryptionKeyRules"
                      type="password"
                      :placeholder="
                        credentialService.credential.encryptionKeySet
                          ? '••••••••  (leave blank to keep current)'
                          : 'Paste your Flutterwave encryption key'
                      "
                    />
                    <span
                      v-if="credentialService.credential.encryptionKeySet"
                      class="md-helper-text"
                    >
                      Configured — leave blank to keep the current key.
                    </span>
                    <span class="md-error">
                      {{ errors.first("Credential-Form.encryptionKey") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Credential-Form.webhookSecretHash',
                      ),
                    }"
                  >
                    <label for="webhookSecretHash">Webhook Secret Hash</label>
                    <md-input
                      id="webhookSecretHash"
                      name="webhookSecretHash"
                      v-model="credentialService.credential.webhookSecretHash"
                      v-validate="webhookSecretHashRules"
                      type="password"
                      :placeholder="
                        credentialService.credential.webhookSecretHashSet
                          ? '••••••••  (leave blank to keep current)'
                          : 'A value you choose — set the same one in Flutterwave'
                      "
                    />
                    <span
                      v-if="credentialService.credential.webhookSecretHashSet"
                      class="md-helper-text"
                    >
                      Configured — leave blank to keep the current value.
                    </span>
                    <span v-else class="md-helper-text">
                      Pick any secret string, then set the identical value in
                      your Flutterwave dashboard under Settings → Webhooks →
                      Secret Hash. Without it, Flutterwave sends webhooks
                      unsigned and MPM will reject them.
                    </span>
                    <span class="md-error">
                      {{ errors.first("Credential-Form.webhookSecretHash") }}
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
                    <label for="environment">
                      {{ $tc("phrases.environment") }}
                    </label>
                    <md-select
                      id="environment"
                      name="environment"
                      v-model="credentialService.credential.environment"
                    >
                      <md-option value="test">
                        {{ $tc("phrases.test") }}
                      </md-option>
                      <md-option value="live">
                        {{ $tc("phrases.live") }}
                      </md-option>
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

    <div class="widget-line">
      <widget color="primary" title="Public Payment Link">
        <md-card>
          <md-card-content>
            <p>Share this link with your customers so they can pay directly.</p>
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
      </widget>
    </div>

    <div class="widget-line">
      <widget color="primary" title="Flutterwave Webhook Link">
        <md-card>
          <md-card-content>
            <p>
              Flutterwave notifies MicroPowerManager about payment events
              through this webhook. In your Flutterwave dashboard, go to
              <strong>Settings &rarr; Webhooks</strong>
              and paste this URL into the Webhook URL field for the environment
              you are using (test or live). Without it, payments cannot be
              confirmed automatically.
            </p>
            <md-field>
              <label>Webhook URL</label>
              <md-input :value="publicUrls.webhook_url" readonly />
              <span class="md-helper-text">
                Unique to your company — keep it configured in Flutterwave at
                all times.
              </span>
            </md-field>
          </md-card-content>
          <md-card-actions md-alignment="left">
            <md-button
              class="md-primary"
              @click="copyToClipboard(publicUrls.webhook_url)"
              :disabled="!publicUrls.webhook_url"
            >
              <md-icon>content_copy</md-icon>
              Copy
            </md-button>
          </md-card-actions>
        </md-card>
      </widget>
    </div>
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
      publicUrls: {
        permanent_payment_url: "",
        webhook_url: "",
      },
    }
  },
  computed: {
    // Required only on first save — when a key is already stored, blank
    // means "keep current" and a typed value still has to be ≥3 chars.
    publicKeyRules() {
      return this.credentialService.credential.publicKeySet
        ? "min:3"
        : "required|min:3"
    },
    secretKeyRules() {
      return this.credentialService.credential.secretKeySet
        ? "min:3"
        : "required|min:3"
    },
    encryptionKeyRules() {
      return this.credentialService.credential.encryptionKeySet
        ? "min:3"
        : "required|min:3"
    },
    webhookSecretHashRules() {
      return this.credentialService.credential.webhookSecretHashSet
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
        EventBus.$emit("FlutterwavePaymentProvider")
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
        this.publicUrls.webhook_url = response.webhook_url
        // The callback URL is derived from the payment URL and stored
        // automatically, so operators never have to copy it by hand.
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
        // Fallback for older browsers
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
.widget-line + .widget-line {
  margin-top: 1rem;
}
</style>
