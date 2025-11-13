<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.secretKey'),
                    }"
                  >
                    <label for="secretKey">
                      {{ $tc("phrases.secretKey") }}
                    </label>
                    <md-input
                      id="secretKey"
                      name="secretKey"
                      v-model="credentialService.credential.secretKey"
                      v-validate="'required|min:3'"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.secretKey") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.publicKey'),
                    }"
                  >
                    <label for="publicKey">
                      {{ $tc("phrases.publicKey") }}
                    </label>
                    <md-input
                      id="publicKey"
                      name="publicKey"
                      v-model="credentialService.credential.publicKey"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.publicKey") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
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
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.callbackUrl") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
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
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
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
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
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
              </div>
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

    <!-- Public URLs Section -->
    <md-card class="public-urls-card">
      <md-card-header>
        <div class="md-title">Public Payment URLs</div>
        <div class="md-subhead">
          Share these URLs with your customers for direct payments
        </div>
      </md-card-header>
      <md-card-content>
        <div class="url-section">
          <!-- Permanent Payment URL -->
          <div class="url-item permanent-url">
            <label class="url-label">
              <md-icon class="url-icon">bookmark</md-icon>
              Permanent Payment URL (Self-Service):
            </label>
            <div class="url-container">
              <md-input
                v-model="publicUrls.permanent_payment_url"
                readonly
                class="url-input"
              />
              <md-button
                class="md-icon-button md-primary"
                @click="copyToClipboard(publicUrls.permanent_payment_url)"
                :disabled="!publicUrls.permanent_payment_url"
              >
                <md-icon>content_copy</md-icon>
              </md-button>
            </div>
            <p class="url-description">
              <md-icon>info</md-icon>
              This URL never expires. Customers can bookmark it for regular
              payments.
            </p>
          </div>

          <div class="url-item callback-url">
            <label class="url-label">
              <md-icon class="url-icon">webhook</md-icon>
              Callback URL (Result Page):
            </label>
            <div class="url-container">
              <md-input
                v-model="callbackUrl"
                readonly
                class="url-input"
                placeholder="Loading callback URL..."
              />
              <md-button
                class="md-icon-button md-primary"
                @click="copyToClipboard(callbackUrl)"
                :disabled="!callbackUrl"
              >
                <md-icon>content_copy</md-icon>
              </md-button>
            </div>

            <div class="callback-instructions">
              <h4>Instructions:</h4>
              <ol>
                <li>Copy the callback URL above</li>
                <li>
                  Paste this URL in the
                  <strong>"Callback URL"</strong>
                  field
                </li>
                <li>Save your settings</li>
              </ol>

              <div class="warning-box">
                <md-icon class="warning-icon">warning</md-icon>
                <div class="warning-content">
                  <strong>Important:</strong>
                  This callback URL is required for payment verification.
                  Without it, customers won't see payment confirmation after
                  completing their transactions.
                </div>
              </div>
            </div>
          </div>

          <!-- Time-based URLs -->
          <div class="url-item time-based-url">
            <label class="url-label">
              <md-icon class="url-icon">schedule</md-icon>
              Time-based URLs (Expire in 24 hours):
            </label>

            <div class="url-sub-item">
              <label class="url-sublabel">Payment URL:</label>
              <div class="url-container">
                <md-input
                  v-model="publicUrls.time_based_payment_url"
                  readonly
                  class="url-input"
                />
                <md-button
                  class="md-icon-button md-primary"
                  @click="copyToClipboard(publicUrls.time_based_payment_url)"
                  :disabled="!publicUrls.time_based_payment_url"
                >
                  <md-icon>content_copy</md-icon>
                </md-button>
              </div>
            </div>

            <div class="url-sub-item">
              <label class="url-sublabel">Result URL:</label>
              <div class="url-container">
                <md-input
                  v-model="publicUrls.time_based_result_url"
                  readonly
                  class="url-input"
                />
                <md-button
                  class="md-icon-button md-primary"
                  @click="copyToClipboard(publicUrls.time_based_result_url)"
                  :disabled="!publicUrls.time_based_result_url"
                >
                  <md-icon>content_copy</md-icon>
                </md-button>
              </div>
            </div>

            <p class="url-description">
              <md-icon>warning</md-icon>
              These URLs expire in 24 hours. Use for temporary or
              agent-generated links.
            </p>
          </div>
        </div>

        <div class="url-actions">
          <md-button
            class="md-raised md-primary"
            @click="generateUrls"
            :disabled="loadingUrls"
          >
            <md-progress-spinner
              v-if="loadingUrls"
              md-diameter="20"
              md-stroke="2"
              style="margin-right: 8px"
            ></md-progress-spinner>
            {{ loadingUrls ? "Generating..." : "Generate URLs" }}
          </md-button>

          <md-button
            class="md-raised"
            @click="openPaymentPage"
            :disabled="!publicUrls.payment_url"
          >
            <md-icon>open_in_new</md-icon>
            Test Payment Page
          </md-button>
        </div>
      </md-card-content>
    </md-card>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
      loadingUrls: false,
      publicUrls: {
        permanent_payment_url: "",
        time_based_payment_url: "",
        time_based_result_url: "",
      },
      callbackUrl: "",
    }
  },
  mounted() {
    this.getCredential()
    this.generateUrls()
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
      this.loadingUrls = true
      try {
        const response = await this.credentialService.getPublicUrls()

        // Add frontend prefix to all URLs
        this.publicUrls = {
          permanent_payment_url: this.addFrontendPrefix(
            response.permanent_payment_url,
          ),
          time_based_payment_url: this.addFrontendPrefix(
            response.time_based_payment_url,
          ),
          time_based_result_url: this.addFrontendPrefix(
            response.time_based_result_url,
          ),
          company_id: response.company_id,
        }

        this.callbackUrl = this.addFrontendPrefix(
          response.permanent_payment_url.replace("/payment/", "/result/"),
        )
      } catch (error) {
        console.error("Error generating public URLs:", error)
        this.alertNotify("error", "Failed to generate public URLs")
      } finally {
        this.loadingUrls = false
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

<style scoped>
.Credential-Form {
  padding: 1rem;
}

.public-urls-card {
  margin-top: 2rem;
}

.url-section {
  margin-bottom: 1.5rem;
}

.url-item {
  margin-bottom: 1rem;
}

.url-label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #666;
}

.url-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.url-input {
  flex: 1;
}

.url-input .md-input {
  background-color: #f5f5f5;
}

.url-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.url-actions .md-button {
  min-width: 140px;
}

.permanent-url {
  border-left: 4px solid #4caf50;
  padding-left: 1rem;
  background-color: #f1f8e9;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.time-based-url {
  border-left: 4px solid #ff9800;
  padding-left: 1rem;
  background-color: #fff3e0;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.url-icon {
  margin-right: 8px;
  vertical-align: middle;
}

.url-sublabel {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #666;
  font-size: 14px;
}

.url-sub-item {
  margin-bottom: 1rem;
}

.url-description {
  display: flex;
  align-items: center;
  margin-top: 0.5rem;
  font-size: 12px;
  color: #666;
  font-style: italic;
}

.url-description .md-icon {
  margin-right: 4px;
  font-size: 16px;
}

/* Callback URL Section Styles */
.callback-url {
  border-left: 4px solid #4caf50;
  padding-left: 1rem;
  background-color: #e8f5e8;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.callback-instructions {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f5f5f5;
  border-radius: 4px;
  border: 1px solid #e0e0e0;
}

.callback-instructions h4 {
  margin: 0 0 1rem 0;
  color: #333;
  font-size: 16px;
  font-weight: 600;
}

.callback-instructions ol {
  margin: 0 0 1rem 0;
  padding-left: 1.5rem;
}

.callback-instructions li {
  margin-bottom: 0.5rem;
  color: #555;
  line-height: 1.4;
}

.callback-instructions strong {
  color: #333;
  font-weight: 600;
}

.warning-box {
  display: flex;
  align-items: flex-start;
  padding: 1rem;
  background-color: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 4px;
  margin-top: 1rem;
}

.warning-icon {
  color: #f39c12;
  margin-right: 0.75rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.warning-content {
  color: #856404;
  line-height: 1.4;
}

.warning-content strong {
  color: #856404;
  font-weight: 600;
}
</style>
