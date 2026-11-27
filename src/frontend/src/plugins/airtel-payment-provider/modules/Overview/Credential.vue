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
            Accept Airtel Money payments from your customers. Airtel asks
            MicroPowerManager to confirm every payment, so both sides need the
            same secret.
            <a
              href="https://micropowermanager.io/integrations-guide/payment-provider/airtel-money.html"
              target="_blank"
              rel="noopener noreferrer"
            >
              Read the setup guide
            </a>
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.jwtSecret'),
                }"
              >
                <label for="jwtSecret">Secret</label>
                <md-input
                  id="jwtSecret"
                  name="jwtSecret"
                  type="password"
                  v-model="credentialService.credential.jwtSecret"
                  v-validate="'required'"
                />
                <span class="md-helper-text">
                  The secret you agreed with Airtel.
                </span>
                <span class="md-error">
                  {{ errors.first("Credential-Form.jwtSecret") }}
                </span>
              </md-field>
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.phonePrefix'),
                }"
              >
                <label for="phonePrefix">Country calling code</label>
                <md-input
                  id="phonePrefix"
                  name="phonePrefix"
                  v-model="credentialService.credential.phonePrefix"
                  v-validate="{ required: true, regex: /^\+\d{1,4}$/ }"
                />
                <span class="md-helper-text">
                  For example +255 for Tanzania or +256 for Uganda.
                </span>
                <span class="md-error">
                  {{ errors.first("Credential-Form.phonePrefix") }}
                </span>
              </md-field>

              <div class="advanced-toggle" @click="showUrls = !showUrls">
                <md-icon>
                  {{
                    showUrls ? "keyboard_arrow_down" : "keyboard_arrow_right"
                  }}
                </md-icon>
                <span>Addresses to give to Airtel</span>
              </div>
              <transition name="expand">
                <div v-if="showUrls" class="callback-urls">
                  <div
                    v-for="step in steps"
                    :key="step.name"
                    class="callback-url"
                  >
                    <div class="callback-url-label">{{ step.label }}</div>
                    <div class="callback-url-value">
                      <code>{{ callbackUrl(step.name) }}</code>
                      <md-button
                        class="md-icon-button md-dense"
                        type="button"
                        @click="copyUrl(step.name)"
                      >
                        <md-icon>content_copy</md-icon>
                        <md-tooltip>Copy</md-tooltip>
                      </md-button>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <div class="md-layout-item md-small-size-100">
              <div class="info-box">
                <h3>How to set up Airtel Money:</h3>
                <ol>
                  <li>
                    Ask your Airtel Money account manager for a merchant
                    (biller) integration.
                  </li>
                  <li>
                    Agree on a secret with Airtel and save it here, together
                    with your country calling code.
                  </li>
                  <li>
                    Give Airtel the three addresses listed under
                    <strong>Addresses to give to Airtel</strong>
                    .
                  </li>
                  <li>Ask Airtel for a test payment.</li>
                </ol>
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
  </div>
</template>

<script>
import { mapGetters } from "vuex"

import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { baseUrlExternal } from "@/repositories/Client/AxiosClient.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      showUrls: false,
      loading: false,
      steps: [
        { name: "validation", label: "Validation" },
        { name: "process", label: "Process" },
        { name: "enquiry", label: "Enquiry" },
      ],
    }
  },
  computed: {
    ...mapGetters({
      authUser: "auth/getAuthenticateUser",
    }),
  },
  mounted() {
    this.credentialService.getCredential()
  },
  methods: {
    callbackUrl(step) {
      return `${baseUrlExternal}/api/airtel-payment-provider/callback/${this.authUser.companyId}/${step}`
    },
    async copyUrl(step) {
      try {
        await navigator.clipboard.writeText(this.callbackUrl(step))
        this.alertNotify("success", "Address copied")
      } catch (e) {
        this.alertNotify("error", "Could not copy the address")
      }
    },
    async submitCredentialForm() {
      const isValid = await this.$validator.validateAll("Credential-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Credentials updated successfully")
        EventBus.$emit("airtel_payment_provider")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
// The registration wizard centres its content; this form reads left to right.
.Credential-Form {
  text-align: left;
}

@media (max-width: 960px) {
  .my-layout {
    flex-direction: column-reverse;
  }
}

.advanced-toggle {
  display: flex;
  align-items: center;
  margin-top: 1rem;
  justify-content: flex-start;
  cursor: pointer;
  font-weight: 500;

  .md-icon {
    flex: none;
    margin: 0 0.25rem 0 0;
  }
}

.callback-urls {
  padding: 0.5rem 0 0 1.5rem;
}

.callback-url {
  margin-top: 0.75rem;
}

.callback-url-label {
  font-size: 0.85rem;
  color: #666;
}

.callback-url-value {
  display: flex;
  align-items: center;

  code {
    word-break: break-all;
  }
}

.info-box {
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #4caf50;

  h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 1rem;
  }

  ol {
    padding-left: 1.5rem;
    margin-bottom: 0;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }
}

.md-subhead {
  margin-top: 0.5rem;
  font-size: 0.9rem;

  a {
    color: #4caf50;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
