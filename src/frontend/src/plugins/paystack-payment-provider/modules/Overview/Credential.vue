<template>
  <div class="paystack-settings">
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
    >
      <md-card class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Paystack API Credentials</h2>
          <p class="panel__subtitle">
            Enter the keys from your Paystack dashboard to start accepting
            payments.
          </p>
        </div>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="secretKey">
                  {{ $tc("phrases.secretKey") }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.secretKey'),
                  }"
                >
                  <md-input
                    id="secretKey"
                    name="secretKey"
                    v-model="credentialService.credential.secretKey"
                    v-validate="'required|min:3'"
                    type="password"
                    placeholder="sk_..."
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.secretKey") }}
                  </span>
                </md-field>
              </div>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="publicKey">
                  {{ $tc("phrases.publicKey") }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.publicKey'),
                  }"
                >
                  <md-input
                    id="publicKey"
                    name="publicKey"
                    v-model="credentialService.credential.publicKey"
                    v-validate="'required|min:3'"
                    placeholder="pk_..."
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.publicKey") }}
                  </span>
                </md-field>
              </div>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="merchantName">
                  {{ $tc("phrases.merchantName") }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.merchantName'),
                  }"
                >
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
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="merchantEmail">
                  {{ $tc("phrases.merchantEmail") }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.merchantEmail'),
                  }"
                >
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
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="environment">
                  {{ $tc("phrases.environment") }}
                </label>
                <md-field>
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
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="callbackUrl">
                  {{ $tc("phrases.callbackUrl") }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.callbackUrl'),
                  }"
                >
                  <md-input
                    id="callbackUrl"
                    name="callbackUrl"
                    v-model="credentialService.credential.callbackUrl"
                    v-validate="'required'"
                    readonly
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.callbackUrl") }}
                  </span>
                </md-field>
                <p class="field__note">
                  Generated automatically — no action needed.
                </p>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <div class="panel__actions">
          <md-button class="save-btn md-raised md-primary" type="submit">
            {{ $tc("words.save") }}
          </md-button>
        </div>
      </md-card>
    </form>

    <md-card class="panel">
      <div class="panel__head">
        <h2 class="panel__title">Public Payment Link</h2>
        <p class="panel__subtitle">
          Share this link with your customers so they can pay directly.
        </p>
      </div>
      <md-card-content>
        <div class="link-block">
          <label class="field__label">
            Permanent Payment URL (Self-Service)
          </label>
          <div class="link-row">
            <span class="link-row__icon">
              <md-icon>link</md-icon>
            </span>
            <input
              class="link-row__input"
              :value="publicUrls.permanent_payment_url"
              readonly
            />
            <md-button
              class="link-row__copy md-raised md-primary"
              @click="copyToClipboard(publicUrls.permanent_payment_url)"
              :disabled="!publicUrls.permanent_payment_url"
            >
              <md-icon>content_copy</md-icon>
              Copy
            </md-button>
          </div>
          <p class="link-block__note">
            <md-icon>info_outline</md-icon>
            This URL never expires. Customers can bookmark it for regular
            payments.
          </p>
        </div>

        <div class="panel__actions panel__actions--start">
          <md-button
            class="ghost-btn"
            @click="openPaymentPage"
            :disabled="!publicUrls.permanent_payment_url"
          >
            <md-icon>open_in_new</md-icon>
            Open test payment page
          </md-button>
        </div>
      </md-card-content>
    </md-card>
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
      publicUrls: {
        permanent_payment_url: "",
      },
    }
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
        EventBus.$emit("Paystack Payment Provider")
      } catch (error) {
        this.alertNotify("error", "Failed to update credential")
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
.paystack-settings {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Clip the pre-font-load ligature text inside every md-icon so its visible
   width stays fixed regardless of whether Material Icons has finished loading
   — without this, the literal text "info_outline" / "link" / etc. spills out
   of the icon box before the font arrives and visibly shifts the surrounding
   content once the ligature collapses to a glyph. */
.md-icon {
  overflow: hidden;
}

/* Panels */
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

.panel__actions--start {
  justify-content: flex-start;
  padding-top: 1rem;
}

/* Fields */
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

/* Buttons */
.save-btn {
  height: 44px;
  border-radius: 9px;
  padding: 0 1.75rem;
  font-weight: 600;
  text-transform: none;
}

.ghost-btn {
  height: 42px;
  border-radius: 9px;
  border: 1px solid #dde3e8;
  font-weight: 600;
  text-transform: none;
  color: $brand-primary-dark;
}

/* Public payment link block */
.link-block {
  background-color: $brand-background;
  border: 1px solid #e1e9f0;
  border-radius: 10px;
  padding: 1.1rem 1.25rem;
}

.link-row {
  display: flex;
  align-items: stretch;
  gap: 0.5rem;
  margin-top: 0.4rem;
}

.link-row__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  border-radius: 8px;
  background-color: rgba($brand-primary, 0.1);

  .md-icon {
    color: $brand-primary !important;
    font-size: 20px !important;
  }
}

.link-row__input {
  flex: 1;
  min-width: 0;
  height: 42px;
  padding: 0 0.85rem;
  font-size: 0.85rem;
  color: $brand-primary-dark;
  background-color: $brand-white;
  border: 1px solid #dde3e8;
  border-radius: 8px;
  outline: none;
}

.link-row__copy {
  height: 42px;
  margin: 0;
  border-radius: 8px;
  font-weight: 600;
  text-transform: none;
}

.link-block__note {
  margin: 0.75rem 0 0;
  font-size: 12px;
  font-style: italic;
  color: #6b7280;

  .md-icon {
    margin: 0 4px 0 0;
    vertical-align: middle;
    width: 16px;
    min-width: 16px;
    height: 16px;
    color: $brand-primary-light !important;
    font-size: 16px !important;
  }
}
</style>
