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
            Configure your Vodacom Mozambique M-Pesa OpenAPI credentials so
            MicroPowerManager can initiate payments.
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <div class="field-with-help">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.apiKey'),
                  }"
                >
                  <label for="apiKey">API Key</label>
                  <md-input
                    id="apiKey"
                    name="apiKey"
                    v-model="credentialService.credential.apiKey"
                    v-validate="'required|min:3'"
                    placeholder="API Key"
                    type="password"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.apiKey") }}
                  </span>
                </md-field>
                <md-icon class="field-help">
                  info_outline
                  <md-tooltip md-direction="top">
                    The API Key from the M-Pesa Developer Portal profile — a
                    ~32-character alphanumeric secret used to authenticate each
                    request.
                  </md-tooltip>
                </md-icon>
              </div>

              <div class="field-with-help">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.publicKey'),
                  }"
                >
                  <label for="publicKey">Public Key</label>
                  <md-textarea
                    id="publicKey"
                    name="publicKey"
                    v-model="credentialService.credential.publicKey"
                    v-validate="'required|min:3'"
                    spellcheck="false"
                    placeholder="Public Key (e.g. MIICIjANBgk...CAwEAAQ==)"
                    md-autogrow
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.publicKey") }}
                  </span>
                </md-field>
                <md-icon class="field-help">
                  info_outline
                  <md-tooltip md-direction="top">
                    The Public Key from the M-Pesa Developer Portal — a long
                    base64 string starting with "MII". Paste it exactly as
                    provided.
                  </md-tooltip>
                </md-icon>
              </div>

              <div class="field-with-help">
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Credential-Form.serviceProviderCode',
                    ),
                  }"
                >
                  <label for="serviceProviderCode">Service Provider Code</label>
                  <md-input
                    id="serviceProviderCode"
                    name="serviceProviderCode"
                    v-model="credentialService.credential.serviceProviderCode"
                    v-validate="'required|min:3'"
                    placeholder="Service Provider Code (e.g. 171717)"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.serviceProviderCode") }}
                  </span>
                </md-field>
                <md-icon
                  v-if="!credentialService.credential.live"
                  class="field-help"
                >
                  info_outline
                  <md-tooltip md-direction="top">
                    In the Sandbox environment this code is ignored — the test
                    short code 171717 is always used instead.
                  </md-tooltip>
                </md-icon>
              </div>

              <md-field>
                <label for="environment">Environment</label>
                <md-select
                  id="environment"
                  name="environment"
                  v-model="credentialService.credential.live"
                >
                  <md-option :value="false">Sandbox</md-option>
                  <md-option :value="true">Live</md-option>
                </md-select>
              </md-field>
            </div>

            <div class="md-layout-item md-small-size-100">
              <div class="info-box">
                <h3>How to get your credentials:</h3>
                <ol>
                  <li>
                    Log into the
                    <a
                      href="https://developer.mpesa.vm.co.mz/"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      M-Pesa Developer Portal
                    </a>
                    and open your profile.
                  </li>
                  <li>
                    Copy the
                    <strong>API Key</strong>
                    and
                    <strong>Public Key</strong>
                    from your profile.
                  </li>
                  <li>
                    The
                    <strong>Service Provider Code</strong>
                    is issued by Vodacom; on Sandbox use
                    <strong>171717</strong>
                    .
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-card-actions>
          <md-button class="md-raised md-primary" type="submit">
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
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
        this.alertNotify("success", "Credentials updated successfully")
        EventBus.$emit("VodacomMzPaymentProvider")
      } catch (e) {
        this.alertNotify("error", "Failed to update credentials")
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

.field-with-help {
  display: flex;
  align-items: center;

  .md-field {
    flex: 1;
  }

  .field-help {
    margin-left: 0.5rem;
    cursor: help;
    color: rgba(0, 0, 0, 0.4);
    font-size: 20px !important;
  }
}

.info-box {
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #e60000;

  h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 1rem;
  }

  ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }

  a {
    color: #e60000;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}

.md-subhead {
  margin-top: 0.5rem;
  font-size: 0.9rem;
}
</style>
