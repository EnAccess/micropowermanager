<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-header>
          <div class="md-title">TextBee SMS Gateway Configuration</div>
          <div class="md-subhead">
            Configure your TextBee API credentials to send SMS through your
            Android device.
            <a
              href="https://textbee.dev/"
              target="_blank"
              rel="noopener noreferrer"
            >
              Learn more about TextBee
            </a>
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.apiKey'),
                    }"
                  >
                    <label for="apiKey">
                      {{ $tc("phrases.apiKey") }}
                    </label>
                    <md-input
                      id="apiKey"
                      name="apiKey"
                      v-model="credentialService.credential.apiKey"
                      v-validate="'required|min:3'"
                      placeholder="Your TextBee API Key"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.apiKey") }}
                    </span>
                  </md-field>
                </div>

                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.deviceId'),
                    }"
                  >
                    <label for="deviceId">Device ID</label>
                    <md-input
                      id="deviceId"
                      name="deviceId"
                      v-model="credentialService.credential.deviceId"
                      v-validate="'required|min:3'"
                      placeholder="Your TextBee Device ID"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.deviceId") }}
                    </span>
                  </md-field>
                </div>
              </div>
            </div>

            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="info-box">
                <h3>How to get your credentials:</h3>
                <ol>
                  <li>
                    Visit
                    <a
                      href="https://textbee.dev/"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      TextBee.dev
                    </a>
                    and create an account
                  </li>
                  <li>
                    Download and install the TextBee Android app on your device
                  </li>
                  <li>Login to your TextBee account in the app</li>
                  <li>Generate an API key from the TextBee dashboard</li>
                  <li>Note your Device ID from the app or dashboard</li>
                </ol>

                <div class="pricing-info">
                  <p>
                    <strong>Pricing:</strong>
                    Free plan includes up to 300 messages/month
                  </p>
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
        EventBus.$emit("TextBee SMS Gateway")
      } catch (e) {
        this.alertNotify("error", "Failed to update credentials")
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100% !important;
}

.Credential-Form {
  height: 100% !important;
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
    margin-bottom: 1rem;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }

  a {
    color: #4caf50;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }

  .pricing-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;

    p {
      margin: 0;
      color: #666;
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
