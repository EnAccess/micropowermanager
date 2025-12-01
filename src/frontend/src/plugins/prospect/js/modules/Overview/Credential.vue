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
              class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
            >
              <md-field
                :class="{
                  'md-invalid':
                    submitted && errors.has('Credential-Form.apiUrl'),
                }"
              >
                <label for="apiUrl">
                  {{ $tc("phrases.apiEndpoint") }}
                </label>
                <md-input
                  id="apiUrl"
                  name="apiUrl"
                  v-model="baseUrl"
                  v-validate="'required'"
                  placeholder="https://demo.prospect.energy/api/v1/in"
                />
                <span class="md-error" v-if="submitted">
                  {{ errors.first("Credential-Form.apiUrl") }}
                </span>
              </md-field>
            </div>
          </div>

          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
            >
              <md-field>
                <label>Installations</label>
                <md-input value="Installations" disabled />
              </md-field>
            </div>
            <div
              class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
            >
              <md-field
                :class="{
                  'md-invalid':
                    submitted &&
                    errors.has('Credential-Form.installationsApiToken'),
                }"
              >
                <label for="installationsApiToken">
                  Secret
                </label>
                <md-input
                  id="installationsApiToken"
                  name="installationsApiToken"
                  type="password"
                  v-model="installationsCredential.apiToken"
                  v-validate="'required|min:3'"
                />
                <span class="md-error" v-if="submitted">
                  {{ errors.first("Credential-Form.installationsApiToken") }}
                </span>
              </md-field>
            </div>
          </div>

          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
            >
              <md-field>
                <label>Payments</label>
                <md-input value="Payments" disabled />
              </md-field>
            </div>
            <div
              class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
            >
              <md-field
                :class="{
                  'md-invalid':
                    submitted && errors.has('Credential-Form.paymentsApiToken'),
                }"
              >
                <label for="paymentsApiToken">Secret</label>
                <md-input
                  id="paymentsApiToken"
                  name="paymentsApiToken"
                  type="password"
                  v-model="paymentsCredential.apiToken"
                  v-validate="'required|min:3'"
                />
                <span class="md-error" v-if="submitted">
                  {{ errors.first("Credential-Form.paymentsApiToken") }}
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
      submitted: false,
      baseUrl: "",
      installationsCredential: {
        id: null,
        apiUrl: null,
        apiToken: null,
      },
      paymentsCredential: {
        id: null,
        apiUrl: null,
        apiToken: null,
      },
    }
  },
  mounted() {
    this.getCredential()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()

      const installations = this.credentialService.list.find(
        (c) => c.apiUrl && c.apiUrl.includes("/installations"),
      ) ||
        this.credentialService.list[0] || {
          id: null,
          apiUrl: null,
          apiToken: null,
        }

      const payments = this.credentialService.list.find(
        (c) => c.apiUrl && c.apiUrl.includes("/payments_ts"),
      ) ||
        this.credentialService.list[1] || {
          id: null,
          apiUrl: null,
          apiToken: null,
        }

      this.installationsCredential = { ...installations }
      this.paymentsCredential = { ...payments }

      const fullUrl = this.installationsCredential.apiUrl || ""
      this.baseUrl = this.extractBaseUrl(fullUrl)
    },
    extractBaseUrl(fullUrl) {
      if (!fullUrl) return ""

      const lastSlashIndex = fullUrl.lastIndexOf("/")
      return lastSlashIndex === -1
        ? fullUrl
        : fullUrl.substring(0, lastSlashIndex)
    },

    buildFullUrl(baseUrl, endpoint) {
      if (!baseUrl) return ""

      let url = baseUrl.trim()
      if (!url.startsWith("http://") && !url.startsWith("https://")) {
        url = `https://${url}`
      }

      return url.endsWith("/") ? `${url}${endpoint}` : `${url}/${endpoint}`
    },
    async submitCredentialForm() {
      this.submitted = true
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true

        const installationsUrl = this.buildFullUrl(
          this.baseUrl,
          "installations",
        )
        const paymentsUrl = this.buildFullUrl(this.baseUrl, "payments_ts")

        this.credentialService.list = [
          {
            id: this.installationsCredential.id,
            apiUrl: installationsUrl,
            apiToken: this.installationsCredential.apiToken,
          },
          {
            id: this.paymentsCredential.id,
            apiUrl: paymentsUrl,
            apiToken: this.paymentsCredential.apiToken,
          },
        ]

        await this.credentialService.updateCredential()
        this.alertNotify("success", "Updated successfully")
        EventBus.$emit("Prospect")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      } finally {
        this.loading = false
      }
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
</style>
